<?php
// Script temporário para validar a migração do Banco de Dados
// Localizado em scripts/ para manter a raiz limpa

// Ajuste do caminho: de scripts/ para config/database.php
$db_path = __DIR__ . '/../config/database.php';

if (!file_exists($db_path)) {
    die("Erro: Arquivo de configuração não encontrado em: $db_path\n");
}

require_once $db_path;

$tables = ['usuarios', 'pacientes', 'procedimentos', 'atendimentos', 'despesas', 'clinicas', 'clinica_taxas_cartao', 'clinica_regras_comissao'];

echo "Validação de Estrutura de Banco de Dados (SaaS)\n";
echo str_repeat("-", 80) . "\n";
printf("%-25s | %-30s | %s\n", "Tabela", "Status", "Colunas");
echo str_repeat("-", 80) . "\n";

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $has_clinica_id = in_array('clinica_id', $columns);
        $status = $has_clinica_id ? "✅ Multi-tenant" : "⚠️ Legado";
        
        if ($table === 'clinicas') $status = "ℹ️ Tabela Mestra";
        if (strpos($table, 'clinica_') === 0 && $table !== 'clinicas') {
             $status = "✅ OK (Config)";
        }

        printf("%-25s | %-30s | %s\n", $table, $status, implode(', ', array_slice($columns, 0, 5)) . (count($columns) > 5 ? "..." : ""));
    } catch (PDOException $e) {
        printf("%-25s | %-30s | %s\n", $table, "❌ Não encontrada", "-");
    }
}
echo str_repeat("-", 80) . "\n";
