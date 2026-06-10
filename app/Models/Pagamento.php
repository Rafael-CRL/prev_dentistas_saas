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
        // 1. Inserir pagamentos e calcular taxas do cartão
        $totalTaxaCartao = 0.0;
        $totalPago = 0.0;

        $stmtPagamento = $this->pdo->prepare(
            "INSERT INTO atendimento_pagamentos (clinica_id, id_atendimento, forma_pagamento, valor, qtd_parcelas) 
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($pagamentos['forma'] as $index => $forma) {
            $valorPago = filter_var(str_replace(',', '.', $pagamentos['valor'][$index]), FILTER_VALIDATE_FLOAT);

            if ($valorPago !== false && $valorPago > 0) {
                $parcelas = ($forma === 'credito') ? (int)($pagamentos['parcelas'][$index] ?? 1) : 1;
                
                $stmtPagamento->execute([
                    $this->clinica_id,
                    $atendimento_id,
                    $forma,
                    $valorPago,
                    $parcelas
                ]);
                
                $resMaquininha = $financeiroService->calcularLiquidoMaquininha($valorPago, $forma, $parcelas);
                $totalTaxaCartao += (float)$resMaquininha['valor_taxa'];
                $totalPago += $valorPago;
            }
        }

        // 2. Buscar detalhes do atendimento atual
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
        if (abs($totalPago - $valorTotalAtendimento) > 0.01) {
            throw new \Exception("A soma dos pagamentos (R$ " . number_format($totalPago, 2, ',', '.') . ") não corresponde ao valor total do atendimento (R$ " . number_format($valorTotalAtendimento, 2, ',', '.') . ").");
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
