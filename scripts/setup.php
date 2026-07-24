<?php
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$username   = $_ENV['DB_USER'] ?? 'root';
$password   = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? '';
$port       = $_ENV['DB_PORT'] ?? '3306';

// Create connection to MariaDB server
$conn = new mysqli($servername, $username, $password, "", $port);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error . "\n");
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
  echo "Database created or already exists.\n";
} else {
  echo "Error creating database: " . $conn->error . "\n";
}
$conn->close();

// Helper function to execute a multi-query SQL file
function executeSqlFile($servername, $username, $password, $dbname, $port, $filepath) {
    if (!file_exists($filepath)) {
        echo "Error: File $filepath not found.\n";
        return false;
    }
    
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error . "\n";
        return false;
    }
    
    $sql = file_get_contents($filepath);
    echo "Executing $filepath...\n";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->next_result());
        echo "Successfully executed $filepath.\n";
        $conn->close();
        return true;
    } else {
        echo "Error executing $filepath: " . $conn->error . "\n";
        $conn->close();
        return false;
    }
}

// Execute database creation and migrations in order
executeSqlFile($servername, $username, $password, $dbname, $port, 'database/clinica_prev_dentistas.sql');
executeSqlFile($servername, $username, $password, $dbname, $port, 'database/migration.sql');
executeSqlFile($servername, $username, $password, $dbname, $port, 'database/migration_normalize_cnpj.sql');

echo "\nDatabase setup fully completed!\n";

