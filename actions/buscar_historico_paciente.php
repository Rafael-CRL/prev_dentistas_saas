<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

$pacienteId = $_GET['paciente_id'] ?? null;

if (!$pacienteId) {
    echo json_encode(['erro' => 'ID do paciente não fornecido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
         SELECT
            ap.id,
            p.nome as procedimento_nome,
            ap.local,
            ap.descricao,
            ap.status_execucao,
            a.data_atendimento,
            a.status_pagamento
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.id_atendimento = a.id
        JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE a.paciente_id = ? 
		AND (ap.status_execucao = 'feito' OR ap.status_execucao = 'pendente')
        ORDER BY a.data_atendimento DESC
    ");
    $stmt->execute([(int)$pacienteId]);
    $procedimentos = $stmt->fetchAll();

    $realizados = [];
    $pendentes = [];

    foreach ($procedimentos as $proc) {
        if ($proc['status_execucao'] === 'feito') {
            $realizados[] = $proc;
        } else {
            $pendentes[] = $proc;
        }
    }

    echo json_encode(['realizados' => $realizados, 'pendentes' => $pendentes]);

} catch (Exception $e) {
    error_log("Erro em buscar_historico_paciente.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar histórico do paciente.']);
}
?>
