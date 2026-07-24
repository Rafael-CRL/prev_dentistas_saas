<?php
// setup_data.php
require_once 'config/database.php';

try {
    echo "Iniciando cadastro de dados padrão...<br>";

    // 1. Criar Dentistas e Proprietário
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        // Hash das senhas
        $senhaRoberto = password_hash('123', PASSWORD_BCRYPT);
        $senhaAna = password_hash('123', PASSWORD_BCRYPT);
        $senhaAdmin = password_hash('admin123', PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nome, login, senha, perfil, clinica_id) VALUES 
                ('Administrador', 'admin', ?, 'proprietario', 1),
                ('Dr. Roberto Silva', 'roberto', ?, 'dentista', 1),
                ('Dra. Ana Costa', 'ana', ?, 'dentista', 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$senhaAdmin, $senhaRoberto, $senhaAna]);
        echo "Usuários (Proprietário e Dentistas) cadastrados com senhas seguras.<br>";
    }

    // 2. Criar Procedimentos
    $stmt = $pdo->query("SELECT COUNT(*) FROM procedimentos");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO procedimentos (nome, categoria, valor_base, clinica_id) VALUES 
                ('Limpeza Completa', 'geral', 150.00, 1),
                ('Restauração Simples', 'geral', 200.00, 1),
                ('Canal (Endodontia)', 'especializado', 800.00, 1),
                ('Implante Unitário', 'especializado', 2500.00, 1),
                ('Prótese Total', 'protese', 1800.00, 1)";
        $pdo->exec($sql);
        echo "Procedimentos cadastrados.<br>";
    }

    echo "<strong>Configuração concluída! <a href='index.php'>Ir para o Dashboard</a></strong>";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>