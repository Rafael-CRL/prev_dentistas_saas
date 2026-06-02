<?php
// config/database.php
//Banco de teste
$host     = $_ENV['DB_HOST'] ?? 'localhost';
$port     = $_ENV['DB_PORT'] ?? '3306';
$db_name  = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
