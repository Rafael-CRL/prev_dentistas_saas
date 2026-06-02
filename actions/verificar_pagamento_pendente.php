<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$paciente_id = $_GET['paciente_id'] ?? null;

if (!$paciente_id) {
    echo json_encode(['pendente' => false, 'erro' => 'ID do paciente não fornecido.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) 
         FROM atendimentos 
         WHERE paciente_id = ? AND status_pagamento = 'pendente'"
    );
    $stmt->execute([$paciente_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['pendente' => $count > 0]);

} catch (Exception $e) {
    // Em caso de erro, é mais seguro não bloquear o fluxo, mas logar o erro.
    error_log("Erro ao verificar pagamento pendente: " . $e->getMessage());
    echo json_encode(['pendente' => false, 'erro' => 'Erro ao consultar o banco de dados.']);
}
?>
