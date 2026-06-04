<?php
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? '';
$port       = $_ENV['DB_PORT'] ?? '3306';

// Create connection
$conn = new mysqli($servername, $username, $password, "", $port);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create database
// Nota: No Railway o banco geralmente já vem criado, mas mantemos a lógica por segurança
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql) === TRUE) {
  echo "Database created successfully\n";
} else {
  echo "Error creating database: " . $conn->error . "\n";
}

$conn->close();

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read sql file
$sql = file_get_contents('database/clinica_prev_dentistas.sql');

// Execute multi query
if ($conn->multi_query($sql)) {
    echo "Database setup successfully";
} else {
    echo "Error setting up database: " . $conn->error;
}

$conn->close();
