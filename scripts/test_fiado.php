<?php
/**
 * scripts/test_fiado.php
 * Script de teste automatizado para a funcionalidade de Fiado
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

use App\Models\Atendimento;
use App\Models\Pagamento;
use App\Services\FinanceiroService;
use App\Models\Config;

echo "=== INICIANDO TESTE DA FUNCIONALIDADE FIADO ===\n\n";

try {
    $clinica_id = 1;
    $pdo->beginTransaction();

    // 1. Criar um paciente temporário
    $stmtPaciente = $pdo->prepare("INSERT INTO pacientes (clinica_id, nome, cpf, telefone) VALUES (?, 'Paciente Teste Fiado', '00000000000', '999999999')");
    $stmtPaciente->execute([$clinica_id]);
    $pacienteId = (int)$pdo->lastInsertId();
    echo "[OK] Paciente temporário criado ID: $pacienteId\n";

    // 2. Criar um atendimento pendente com valor total R$ 400,00
    $atendimentoModel = new Atendimento($pdo, $clinica_id);
    $idAtendimento = $atendimentoModel->criarAtendimento([
        'paciente_id' => $pacienteId,
        'id_dentista' => 1, // Dentista padrão
        'valor_total' => 400.00,
        'comissao_dentista' => 0.00,
        'custo_auxiliar' => 0.00,
        'valor_liquido_clinica' => 0.00,
        'status_pagamento' => 'pendente'
    ]);
    echo "[OK] Atendimento temporário criado ID: $idAtendimento\n";

    // Criar procedimento associado (finalizado)
    $stmtProc = $pdo->prepare("
        INSERT INTO atendimento_procedimentos 
        (clinica_id, id_atendimento, id_procedimento, quantidade, valor_procedimento, status_execucao, natureza) 
        VALUES (?, ?, 1, 1, 400.00, 'finalizado', 'clinico')
    ");
    $stmtProc->execute([$clinica_id, $idAtendimento]);
    echo "[OK] Procedimento associado criado.\n";

    // 3. Simular Pagamento Inicial (1ª parcela do Fiado de R$ 200,00)
    $pagamentoModel = new Pagamento($pdo, $clinica_id);
    $config = Config::getInstance($pdo, $clinica_id);
    $financeiroService = new FinanceiroService($config);

    echo "\nSimulando pagamento da 1ª parcela (Fiado - R$ 200,00)...\n";
    $pagamentoModel->confirmarPagamentoCompleto($idAtendimento, [
        'forma' => ['fiado'],
        'valor' => ['200.00'],
        'parcelas' => [1]
    ], $financeiroService, $atendimentoModel);

    // Validar status pós 1ª parcela
    $stmtCheckAtend = $pdo->prepare("SELECT status_pagamento, valor_liquido_clinica, comissao_dentista FROM atendimentos WHERE id = ?");
    $stmtCheckAtend->execute([$idAtendimento]);
    $atendData = $stmtCheckAtend->fetch(PDO::FETCH_ASSOC);

    if ($atendData['status_pagamento'] !== 'pendente') {
        throw new Exception("ERRO: O atendimento deveria continuar com status 'pendente'.");
    }
    if ((float)$atendData['valor_liquido_clinica'] !== 0.00 || (float)$atendData['comissao_dentista'] !== 0.00) {
        throw new Exception("ERRO: A clínica e o dentista não devem receber repasses/comissões enquanto houver saldo pendente (Devem ser 0.00).");
    }
    echo "[OK] Validação pós 1ª parcela bem-sucedida (Status: pendente, Repasse: 0.00).\n";

    // Verificar pagamentos registrados no banco
    $stmtCheckPags = $pdo->prepare("SELECT forma_pagamento, valor, status FROM atendimento_pagamentos WHERE id_atendimento = ? ORDER BY id ASC");
    $stmtCheckPags->execute([$idAtendimento]);
    $pags = $stmtCheckPags->fetchAll(PDO::FETCH_ASSOC);

    if (count($pags) !== 2) {
        throw new Exception("ERRO: Deveriam haver exatamente 2 registros de pagamento de fiado.");
    }
    if ($pags[0]['forma_pagamento'] !== 'fiado' || $pags[0]['status'] !== 'pago' || (float)$pags[0]['valor'] !== 200.00) {
        throw new Exception("ERRO: O primeiro pagamento deve ser de fiado pago no valor de R$ 200.00.");
    }
    if ($pags[1]['forma_pagamento'] !== 'fiado' || $pags[1]['status'] !== 'pendente' || (float)$pags[1]['valor'] !== 200.00) {
        throw new Exception("ERRO: O segundo pagamento deve ser de fiado pendente no valor de R$ 200.00.");
    }
    echo "[OK] Registros de pagamento verificados (1 Pago, 1 Pendente de R$ 200.00 cada).\n";

    // 4. Simular Quitação (2ª parcela de R$ 200,00 via Pix)
    echo "\nSimulando quitação da 2ª parcela (Pix - R$ 200,00)...\n";
    $pagamentoModel->confirmarPagamentoCompleto($idAtendimento, [
        'forma' => ['pix'],
        'valor' => ['200.00'],
        'parcelas' => [1]
    ], $financeiroService, $atendimentoModel);

    // Validar status pós quitação
    $stmtCheckAtendFinal = $pdo->prepare("SELECT status_pagamento, valor_liquido_clinica, comissao_dentista FROM atendimentos WHERE id = ?");
    $stmtCheckAtendFinal->execute([$idAtendimento]);
    $atendDataFinal = $stmtCheckAtendFinal->fetch(PDO::FETCH_ASSOC);

    if ($atendDataFinal['status_pagamento'] !== 'pago') {
        throw new Exception("ERRO: O atendimento deveria ter mudado o status para 'pago'.");
    }
    if ((float)$atendDataFinal['valor_liquido_clinica'] <= 0.00) {
        throw new Exception("ERRO: O repasse para a clínica deve ser maior que 0.00 após a quitação total.");
    }
    if ((float)$atendDataFinal['comissao_dentista'] <= 0.00) {
        throw new Exception("ERRO: A comissão do dentista deve ser calculada e ser maior que 0.00 após a quitação total.");
    }
    echo "[OK] Validação pós quitação bem-sucedida (Status: pago, Repasse Clínica: R$ " . number_format($atendDataFinal['valor_liquido_clinica'], 2, ',', '.') . ", Comissão Dentista: R$ " . number_format($atendDataFinal['comissao_dentista'], 2, ',', '.') . ").\n";

    // Verificar pagamentos registrados no banco pós quitação
    $stmtCheckPagsFinal = $pdo->prepare("SELECT forma_pagamento, valor, status FROM atendimento_pagamentos WHERE id_atendimento = ? ORDER BY id ASC");
    $stmtCheckPagsFinal->execute([$idAtendimento]);
    $pagsFinal = $stmtCheckPagsFinal->fetchAll(PDO::FETCH_ASSOC);

    if (count($pagsFinal) !== 2) {
        throw new Exception("ERRO: Deveriam haver exatamente 2 registros de pagamento.");
    }
    if ($pagsFinal[0]['forma_pagamento'] !== 'fiado' || $pagsFinal[0]['status'] !== 'pago') {
        throw new Exception("ERRO: O primeiro pagamento deve continuar como fiado pago.");
    }
    if ($pagsFinal[1]['forma_pagamento'] !== 'pix' || $pagsFinal[1]['status'] !== 'pago' || (float)$pagsFinal[1]['valor'] !== 200.00) {
        throw new Exception("ERRO: O segundo pagamento deve ter sido substituído por Pix pago no valor de R$ 200.00.");
    }
    echo "[OK] Registros de pagamento finais verificados pós quitação.\n";

    // Verificar status dos procedimentos
    $stmtCheckProc = $pdo->prepare("SELECT status_execucao FROM atendimento_procedimentos WHERE id_atendimento = ?");
    $stmtCheckProc->execute([$idAtendimento]);
    $procStatus = $stmtCheckProc->fetchColumn();
    if ($procStatus !== 'feito') {
        throw new Exception("ERRO: O procedimento deveria ter mudado o status para 'feito'.");
    }
    echo "[OK] Status do procedimento atualizado para 'feito'.\n";

    $pdo->rollBack();
    echo "\n💎 TESTE DE FIADO CONCLUÍDO COM 100% SUCESSO!\n";
    exit(0);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ TESTE FALHOU: " . $e->getMessage() . "\n";
    exit(1);
}
