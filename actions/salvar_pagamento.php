<?php
//error_reporting(E_ALL & ~E_NOTICE);
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/seguranca.php';
require_once '../config/controle_acesso.php';
require_once '../config/app.php'; // For BASE_URL
require_once 'Financeiro.php'; // Include Financeiro logic

$atendimento_id = $_POST['atendimento_id'] ?? null;
$paciente_id = $_POST['paciente_id'] ?? null;

// Acesso permitido para os perfis: proprietario (admin), recepcionista e dentista.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (!is_admin() && !is_recepcionista() && !is_dentista())) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'erro' => 'Acesso negado ou método inválido.']);
    exit;
}

$pagamentos = $_POST['pagamentos'] ?? [];

if (!$atendimento_id || !$paciente_id || empty($pagamentos['valor'])) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Dados essenciais não foram fornecidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Inserir os registros de pagamento e calcular taxas
    $totalTaxaCartao = 0.0;
    $totalPago = 0.0;
    $int_atendimento_id = (int)$atendimento_id;

    $stmtPagamento = $pdo->prepare(
        "INSERT INTO atendimento_pagamentos (id_atendimento, forma_pagamento, valor, qtd_parcelas) 
         VALUES (?, ?, ?, ?)"
    );

    foreach ($pagamentos['valor'] as $index => $valor) {
        // Garante que o valor seja um float, independentemente de ser vírgula ou ponto
        $valorPago = filter_var(str_replace(',', '.', $valor), FILTER_VALIDATE_FLOAT);

        if ($valorPago !== false && $valorPago > 0) {
            $forma = $pagamentos['forma'][$index];
            $parcelas = ($forma === 'credito') ? (int)($pagamentos['parcelas'][$index] ?? 1) : 1;
            
            $stmtPagamento->execute([$int_atendimento_id, $forma, $valorPago, $parcelas]);
            
            $resMaquininha = Financeiro::calcularLiquidoMaquininha($valorPago, $forma, $parcelas);
            $totalTaxaCartao += (float)$resMaquininha['valor_taxa'];
            $totalPago += $valorPago;
        }
    }

    // 2. Buscar informações do atendimento para recalcular o valor líquido
    $stmtAtendimento = $pdo->prepare("SELECT valor_total, comissao_dentista, custo_auxiliar FROM atendimentos WHERE id = ?");
    $stmtAtendimento->execute([$int_atendimento_id]);
    $atendimento = $stmtAtendimento->fetch(PDO::FETCH_ASSOC);

    if (!$atendimento) {
        throw new Exception("Atendimento não encontrado.");
    }
    
    $valorTotalAtendimento = (float)$atendimento['valor_total'];
    if (abs($totalPago - $valorTotalAtendimento) > 0.01) {
        throw new Exception("A soma dos pagamentos (R$ ".number_format($totalPago, 2, ',', '.').") não corresponde ao valor total do atendimento (R$ ".number_format($valorTotalAtendimento, 2, ',', '.').").");
    }

    // --- INÍCIO: Recálculo da comissão no momento do pagamento ---
    // 1. Obter faturamento bruto do mês (sem este atendimento)
    $data_inicio_mes = date('Y-m-01 00:00:00');
    $data_fim_mes = date('Y-m-t 23:59:59');
    $stmtFaturamento = $pdo->prepare(
        "SELECT SUM(ap.valor_procedimento) as total
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.data_atendimento BETWEEN ? AND ? AND a.status_pagamento = 'pago' AND ap.status_execucao = 'feito'"
    );
    $stmtFaturamento->execute([$data_inicio_mes, $data_fim_mes]);
    $faturamentoBrutoMensal = $stmtFaturamento->fetchColumn() ?? 0;

    // 2. Faturamento para cálculo da comissão inclui o valor do atendimento atual
    $faturamentoParaCalculo = $faturamentoBrutoMensal + $valorTotalAtendimento;

    // 3. Buscar procedimentos do atendimento para obter categoria e valores individuais
    $stmtProcedimentosAtendimento = $pdo->prepare(
        "SELECT ap.valor_procedimento, ap.custo_auxiliar, ap.natureza, p.categoria
         FROM atendimento_procedimentos ap
         JOIN procedimentos p ON ap.id_procedimento = p.id
         WHERE ap.id_atendimento = ? AND ap.status_execucao = 'finalizado'"
    );
    $stmtProcedimentosAtendimento->execute([$int_atendimento_id]);
    $procedimentosDoAtendimento = $stmtProcedimentosAtendimento->fetchAll(PDO::FETCH_ASSOC);

    // 4. Recalcular a comissão total com base no faturamento atualizado
    $novaComissaoTotal = 0.0;
    foreach ($procedimentosDoAtendimento as $proc) {
        $resComissao = Financeiro::calcularComissao($proc['valor_procedimento'], $proc['categoria'], $faturamentoParaCalculo, $proc['custo_auxiliar'], $proc['natureza']);
        $novaComissaoTotal += $resComissao['dentista'];
    }
    // --- FIM: Recálculo da comissão ---

    // 3. Calcular novo valor líquido e ATUALIZAR ATENDIMENTO para 'pago' com a comissão correta
    $valorLiquidoClinica = $valorTotalAtendimento - $totalTaxaCartao - $novaComissaoTotal - (float)$atendimento['custo_auxiliar'];

    $stmtUpdateAtendimento = $pdo->prepare(
        "UPDATE atendimentos SET status_pagamento = 'pago', taxa_cartao = ?, comissao_dentista = ?, valor_liquido_clinica = ? WHERE id = ?"
    );
    $stmtUpdateAtendimento->execute([$totalTaxaCartao, $novaComissaoTotal, $valorLiquidoClinica, $int_atendimento_id]);

    // 4. ATUALIZAR PROCEDIMENTOS para 'feito'
    $stmtUpdateProcedimentos = $pdo->prepare(
        "UPDATE atendimento_procedimentos SET status_execucao = 'feito' WHERE id_atendimento = ? AND status_execucao = 'finalizado'"
    );
    $stmtUpdateProcedimentos->execute([$int_atendimento_id]);
    
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['sucesso' => true, 'mensagem' => 'Pagamento confirmado com sucesso!']);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro em salvar_pagamento.php: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar o pagamento: ' . $e->getMessage()]);
    exit;
}
?>