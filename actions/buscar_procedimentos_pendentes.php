<?php
// actions/buscar_procedimentos_pendentes.php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Sessão expirada.']);
    exit;
}

$pacienteId = $_GET['paciente_id'] ?? null;

if (!$pacienteId) {
    echo json_encode([]);
    exit;
}

try {
    // Encontra todos os procedimentos para um paciente que não estão com status 'finalizado'
    $stmt = $pdo->prepare("
        SELECT
            ap.id as atendimento_procedimento_id,
            ap.id_procedimento,
            p.nome as procedimento_nome,
            p.categoria,
            ap.quantidade,
            ap.valor_procedimento,
            ap.local,
            ap.custo_auxiliar,
            ap.descricao,
            ap.natureza
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.id_atendimento = a.id
        JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE a.paciente_id = ? AND ap.status_execucao = 'pendente'
    ");
    $stmt->execute([$pacienteId]);
    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pendentes);

} catch (Exception $e) {
    error_log("Erro em buscar_procedimentos_pendentes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar procedimentos.']);
}