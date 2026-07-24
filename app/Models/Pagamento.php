<?php

namespace App\Models;

use PDO;

class Pagamento
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Processa a finalização de pagamento completa de forma lógica e parametrizada (SaaS)
     */
    public function confirmarPagamentoCompleto(
        int $atendimento_id, 
        array $pagamentos, 
        \App\Services\FinanceiroService $financeiroService,
        \App\Models\Atendimento $atendimentoModel
    ): void {
        // Buscar detalhes do atendimento atual
        $stmtAtend = $this->pdo->prepare("
            SELECT valor_total, custo_auxiliar 
            FROM atendimentos 
            WHERE id = ? AND clinica_id = ?
        ");
        $stmtAtend->execute([$atendimento_id, $this->clinica_id]);
        $atendimento = $stmtAtend->fetch(PDO::FETCH_ASSOC);

        if (!$atendimento) {
            throw new \Exception("Atendimento não encontrado ou acesso negado.");
        }

        $valorTotalAtendimento = (float)$atendimento['valor_total'];

        // Buscar pagamentos já salvos para este atendimento
        $stmtExistingPags = $this->pdo->prepare("
            SELECT * FROM atendimento_pagamentos 
            WHERE id_atendimento = ? AND clinica_id = ?
        ");
        $stmtExistingPags->execute([$atendimento_id, $this->clinica_id]);
        $existingPags = $stmtExistingPags->fetchAll(PDO::FETCH_ASSOC);

        $hasPendingFiado = false;
        $pendingFiadoId = null;
        $pendingFiadoValor = 0.0;
        $totalPagoAnterior = 0.0;
        
        foreach ($existingPags as $pag) {
            if ($pag['forma_pagamento'] === 'fiado' && $pag['status'] === 'pendente') {
                $hasPendingFiado = true;
                $pendingFiadoId = (int)$pag['id'];
                $pendingFiadoValor = (float)$pag['valor'];
            }
            if ($pag['status'] === 'pago') {
                $totalPagoAnterior += (float)$pag['valor'];
            }
        }

        // Se o atendimento já tem um Fiado pendente, o que está sendo pago agora é o saldo devedor
        $totalPagoNovo = 0.0;
        foreach ($pagamentos['forma'] as $index => $forma) {
            $valorPago = filter_var(str_replace(',', '.', $pagamentos['valor'][$index]), FILTER_VALIDATE_FLOAT);
            if ($valorPago !== false && $valorPago > 0) {
                $totalPagoNovo += $valorPago;
            }
        }

        if ($hasPendingFiado) {
            if (abs($totalPagoNovo - $pendingFiadoValor) > 0.01) {
                throw new \Exception("O valor pago (R$ " . number_format($totalPagoNovo, 2, ',', '.') . ") não corresponde ao saldo pendente do Fiado (R$ " . number_format($pendingFiadoValor, 2, ',', '.') . ").");
            }
            // Deleta o registro do Fiado pendente para podermos inserir a forma real do pagamento da quitação
            $stmtDeletePending = $this->pdo->prepare("
                DELETE FROM atendimento_pagamentos 
                WHERE id = ? AND clinica_id = ?
            ");
            $stmtDeletePending->execute([$pendingFiadoId, $this->clinica_id]);
        }

        // 1. Inserir pagamentos novos e calcular valor total pago nesta etapa
        $totalPago = 0.0;

        $stmtPagamento = $this->pdo->prepare(
            "INSERT INTO atendimento_pagamentos (clinica_id, id_atendimento, forma_pagamento, valor, qtd_parcelas, status) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $hasFiadoInput = false;
        foreach ($pagamentos['forma'] as $index => $forma) {
            $valorPago = filter_var(str_replace(',', '.', $pagamentos['valor'][$index]), FILTER_VALIDATE_FLOAT);

            if ($valorPago !== false && $valorPago > 0) {
                $parcelas = ($forma === 'credito') ? (int)($pagamentos['parcelas'][$index] ?? 1) : 1;
                if ($forma === 'fiado') {
                    $hasFiadoInput = true;
                }
                
                $stmtPagamento->execute([
                    $this->clinica_id,
                    $atendimento_id,
                    $forma,
                    $valorPago,
                    $parcelas,
                    'pago'
                ]);
                
                $totalPago += $valorPago;
            }
        }

        // O total pago acumulado (incluindo o que já foi pago anteriormente, se houver)
        $totalPagoAcumulado = $totalPagoAnterior + $totalPago;

        // Se houver Fiado ativo no fluxo e houver saldo pendente
        $saldoPendente = $valorTotalAtendimento - $totalPagoAcumulado;

        if ($hasFiadoInput && $saldoPendente > 0.01) {
            // Cria a 2ª parcela (o saldo pendente do Fiado) para pagamento posterior
            $stmtPagamento->execute([
                $this->clinica_id,
                $atendimento_id,
                'fiado',
                $saldoPendente,
                1,
                'pendente'
            ]);

            // Enquanto houver saldo pendente, o atendimento continua pendente, e a clínica não recebe nada
            $stmtUpdateAtend = $this->pdo->prepare("
                UPDATE atendimentos 
                SET status_pagamento = 'pendente', 
                    taxa_cartao = 0.00, 
                    comissao_dentista = 0.00, 
                    valor_liquido_clinica = 0.00 
                WHERE id = ? AND clinica_id = ?
            ");
            $stmtUpdateAtend->execute([$atendimento_id, $this->clinica_id]);
            return; // Retorna cedo para não processar a comissão e repasse ainda
        }

        // Se não for Fiado e o total pago não bater com o total do atendimento, dá erro
        if (!$hasFiadoInput && abs($totalPagoAcumulado - $valorTotalAtendimento) > 0.01) {
            throw new \Exception("A soma dos pagamentos (R$ " . number_format($totalPagoAcumulado, 2, ',', '.') . ") não corresponde ao valor total do atendimento (R$ " . number_format($valorTotalAtendimento, 2, ',', '.') . ").");
        }

        // Recarregar todos os pagamentos salvos com status 'pago' para recalcular a taxa_cartao acumulada
        $stmtAllPags = $this->pdo->prepare("
            SELECT * FROM atendimento_pagamentos 
            WHERE id_atendimento = ? AND clinica_id = ? AND status = 'pago'
        ");
        $stmtAllPags->execute([$atendimento_id, $this->clinica_id]);
        $allPaidPags = $stmtAllPags->fetchAll(PDO::FETCH_ASSOC);

        $totalTaxaCartao = 0.0;
        foreach ($allPaidPags as $pag) {
            $resMaquininha = $financeiroService->calcularLiquidoMaquininha((float)$pag['valor'], $pag['forma_pagamento'], (int)$pag['qtd_parcelas']);
            $totalTaxaCartao += (float)$resMaquininha['valor_taxa'];
        }

        // 3. Obter faturamento bruto do mês (excluindo este atendimento)
        $data_inicio_mes = date('Y-m-01 00:00:00');
        $data_fim_mes = date('Y-m-t 23:59:59');
        
        $stmtFaturamento = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento) as total
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.data_atendimento BETWEEN ? AND ? 
            AND a.status_pagamento = 'pago' 
            AND ap.status_execucao = 'feito'
            AND a.clinica_id = ?
        ");
        $stmtFaturamento->execute([$data_inicio_mes, $data_fim_mes, $this->clinica_id]);
        $faturamentoBrutoMensal = (float)($stmtFaturamento->fetchColumn() ?: 0.0);

        // O faturamento bruto acumulado para calcular comissão deve incluir este novo faturamento
        $faturamentoParaCalculo = $faturamentoBrutoMensal + $valorTotalAtendimento;

        // 4. Buscar os procedimentos finalizados deste atendimento para recalcular a comissão com a meta
        $stmtProcedimentos = $this->pdo->prepare("
            SELECT ap.valor_procedimento, ap.custo_auxiliar, ap.natureza, p.categoria
            FROM atendimento_procedimentos ap
            JOIN procedimentos p ON ap.id_procedimento = p.id
            WHERE ap.id_atendimento = ? 
            AND ap.status_execucao = 'finalizado'
            AND ap.clinica_id = ?
        ");
        $stmtProcedimentos->execute([$atendimento_id, $this->clinica_id]);
        $procedimentosDoAtendimento = $stmtProcedimentos->fetchAll(PDO::FETCH_ASSOC);

        // 5. Recalcular a comissão do dentista baseada na regra de meta de faturamento mensal
        $novaComissaoTotal = 0.0;
        foreach ($procedimentosDoAtendimento as $proc) {
            $resComissao = $financeiroService->calcularComissao(
                $proc['valor_procedimento'],
                $proc['categoria'],
                $faturamentoParaCalculo,
                $proc['custo_auxiliar'],
                $proc['natureza']
            );
            $novaComissaoTotal += $resComissao['dentista'];
        }

        // 6. Calcular o valor líquido final da clínica
        $valorLiquidoClinica = $valorTotalAtendimento - $totalTaxaCartao - $novaComissaoTotal - (float)$atendimento['custo_auxiliar'];

        // 7. Atualizar Atendimento
        $stmtUpdateAtend = $this->pdo->prepare("
            UPDATE atendimentos 
            SET status_pagamento = 'pago', 
                taxa_cartao = ?, 
                comissao_dentista = ?, 
                valor_liquido_clinica = ? 
            WHERE id = ? AND clinica_id = ?
        ");
        $stmtUpdateAtend->execute([
            $totalTaxaCartao,
            $novaComissaoTotal,
            $valorLiquidoClinica,
            $atendimento_id,
            $this->clinica_id
        ]);

        // 8. Atualizar procedimentos do atendimento para status 'feito'
        $stmtUpdateProcs = $this->pdo->prepare("
            UPDATE atendimento_procedimentos 
            SET status_execucao = 'feito' 
            WHERE id_atendimento = ? 
            AND status_execucao = 'finalizado'
            AND clinica_id = ?
        ");
        $stmtUpdateProcs->execute([$atendimento_id, $this->clinica_id]);
    }
}
