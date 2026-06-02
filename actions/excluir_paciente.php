<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/seguranca.php';
require_once '../config/controle_acesso.php';

// Apenas usuários com permissão podem acessar
if (!is_admin() && !is_recepcionista() && !is_dentista()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$paciente_id = $_GET['id'] ?? null;

if (!$paciente_id) {
    header("Location: " . BASE_URL . "pacientes.php");
    exit;
}

try {
    // Verificar se o paciente está sendo usado em algum atendimento
    // Isso previne a exclusão de pacientes com histórico, mantendo a integridade dos dados.
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE paciente_id = ?");
    $stmtCheck->execute([$paciente_id]);
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        // Se estiver vinculado, redireciona com uma mensagem de erro
        header("Location: " . BASE_URL . "pacientes.php?erro=conflito_atendimento");
        exit;
    }

    // Se não houver conflitos, prossegue com a exclusão
    $stmtDelete = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
    $stmtDelete->execute([$paciente_id]);

    header("Location: " . BASE_URL . "pacientes.php?msg=excluido");
    exit;

} catch (PDOException $e) {
    // Em caso de erro de banco de dados (ex: a restrição de FK impede a exclusão),
    // redireciona com uma mensagem genérica de erro.
    // A verificação acima deve prevenir isso na maioria dos casos.
    error_log("Erro ao excluir paciente: " . $e->getMessage());
    header("Location: " . BASE_URL . "pacientes.php?erro=inesperado");
    exit;
}
