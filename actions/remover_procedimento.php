<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../config/controle_acesso.php';

// Apenas administradores e dentistas podem remover
if (!is_admin() && !is_dentista()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit;
}

$id_procedimento = filter_input(INPUT_POST, 'id_procedimento', FILTER_SANITIZE_NUMBER_INT);

if (empty($id_procedimento)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID do procedimento não fornecido.']);
    exit;
}

try {
    // Correção: JOIN com a tabela atendimentos para acessar o status_pagamento
    $stmt = $pdo->prepare("
        SELECT ap.status_execucao, a.status_pagamento, ap.url_arquivo 
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.id_atendimento = a.id
        WHERE ap.id = ?
    ");
    $stmt->execute([$id_procedimento]);
    $procedimento = $stmt->fetch();

    if (!$procedimento) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Procedimento não encontrado.']);
        exit;
    }

    if ($procedimento['status_execucao'] !== 'pendente' || $procedimento['status_pagamento'] !== 'nao_aplicavel') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Você não tem autorização para apagar este procedimento.']);
        exit;
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Apagar o arquivo associado, se existir
    if (!empty($procedimento['url_arquivo'])) {
        $caminho_absoluto = realpath(__DIR__ . '/../' . $procedimento['url_arquivo']);
        if ($caminho_absoluto && file_exists($caminho_absoluto)) {
            if (!@unlink($caminho_absoluto)) {
                throw new Exception('Falha ao apagar o arquivo de anexo associado. Verifique as permissões.');
            }
        }
    }

    // Apagar o registro do procedimento
    $stmt_delete = $pdo->prepare("DELETE FROM atendimento_procedimentos WHERE id = ?");
    $stmt_delete->execute([$id_procedimento]);

    // Commit da transação
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Procedimento removido com sucesso.']);

} catch (Exception $e) {
    // Rollback em caso de erro, verificando se há uma transação ativa
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    
    // Log do erro para o servidor, sem expor detalhes sensíveis ao usuário
    error_log("Erro em remover_procedimento.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Ocorreu um erro interno ao tentar remover o procedimento.']);
}