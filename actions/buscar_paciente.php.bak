<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = '%' . trim($_GET['term']) . '%';

if (strlen(trim($_GET['term'])) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Busca diretamente na tabela de pacientes, que é a fonte da verdade.
    // Retorna o ID do paciente, que é crucial para as chaves estrangeiras.
    $stmt = $pdo->prepare(
        "SELECT id, nome, cpf, telefone, email
         FROM pacientes
         WHERE nome LIKE ? OR cpf LIKE ?
         ORDER BY nome ASC
         LIMIT 10"
    );
    $stmt->execute([$term, $term]);
    
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pacientes);

} catch (Exception $e) {
    // Em caso de erro, retorna um array vazio. Opcional: logar $e->getMessage()
    error_log("Erro em buscar_paciente.php: " . $e->getMessage());
    echo json_encode([]);
}
