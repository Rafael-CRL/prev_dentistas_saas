<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $categoria = $_POST['categoria'];
    $valor_base = !empty($_POST['valor_base']) ? floatval($_POST['valor_base']) : null;
    $tipo = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;

    if (empty($nome) || empty($categoria)) {
        die("Erro: Nome e categoria são obrigatórios.");
    }

    try {
        $sql = "INSERT INTO procedimentos (nome, categoria, valor_base, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $categoria, $valor_base, $tipo]);

        header("Location: " . BASE_URL . "procedimentos.php?msg=sucesso");
        exit;
    } catch (PDOException $e) {
        die("Erro ao salvar procedimento: " . $e->getMessage());
    }
}
?>
