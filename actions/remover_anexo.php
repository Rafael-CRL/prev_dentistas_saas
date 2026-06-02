<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../config/controle_acesso.php';

// Apenas usuários logados (admin ou dentista) podem remover
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

// Iniciar transação para garantir consistência
$pdo->beginTransaction();

try {
    // 1. Buscar o caminho do arquivo ANTES de apagar a referência
    $stmt_select = $pdo->prepare("SELECT url_arquivo FROM atendimento_procedimentos WHERE id = ?");
    $stmt_select->execute([$id_procedimento]);
    $caminho_relativo = $stmt_select->fetchColumn();

    // Se não há anexo registrado, não há nada a fazer.
    if (empty($caminho_relativo)) {
        $pdo->rollBack(); // Nada a fazer
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Nenhum anexo para remover.']);
        exit;
    }
    
    // 2. Atualizar o banco de dados para remover a referência
    $stmt_update = $pdo->prepare("UPDATE atendimento_procedimentos SET url_arquivo = NULL WHERE id = ?");
    if (!$stmt_update->execute([$id_procedimento])) {
        throw new Exception('Falha ao remover a referência do arquivo no banco de dados.');
    }

    // 3. Tentar apagar o arquivo físico
    $caminho_absoluto = realpath(__DIR__ . '/../' . $caminho_relativo);
    
    if ($caminho_absoluto && file_exists($caminho_absoluto)) {
        // A supressão de erro com @ é para evitar warnings que quebram o JSON.
        // A falha é tratada logo em seguida.
        if (!@unlink($caminho_absoluto)) {
            // Se unlink falhar, desfaz a atualização do banco de dados
            throw new Exception('Falha ao apagar o arquivo físico do servidor. Verifique as permissões do arquivo/pasta.');
        }
    }

    // 4. Se tudo deu certo, comita a transação
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Arquivo removido com sucesso.']);

} catch (Exception $e) {
    // 5. Se algo deu errado, desfaz tudo
    $pdo->rollBack();
    header('Content-Type: application/json');
    error_log('Erro em remover_anexo.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Ocorreu um erro interno ao tentar remover o anexo.']);
}
