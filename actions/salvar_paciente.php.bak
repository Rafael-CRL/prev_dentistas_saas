<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/seguranca.php';
require_once '../config/controle_acesso.php';

// Apenas usuários com permissão podem acessar
if (!is_admin() && !is_recepcionista() && !is_dentista()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'] ?? null;

    // Coleta os dados do formulário
    $data = [
        ':nome' => $_POST['paciente_nome'] ?? '',
        ':cpf' => !empty($_POST['paciente_cpf']) ? $_POST['paciente_cpf'] : null,
        ':data_nascimento' => !empty($_POST['paciente_data_nascimento']) ? $_POST['paciente_data_nascimento'] : null,
        ':email' => !empty($_POST['paciente_email']) ? filter_var($_POST['paciente_email'], FILTER_VALIDATE_EMAIL) ? $_POST['paciente_email'] : null : null,
        ':telefone' => !empty($_POST['paciente_telefone']) ? $_POST['paciente_telefone'] : null,
        ':cep' => !empty($_POST['paciente_cep']) ? $_POST['paciente_cep'] : null,
        ':endereco' => !empty($_POST['paciente_endereco']) ? $_POST['paciente_endereco'] : null,
        ':numero' => !empty($_POST['paciente_numero']) ? $_POST['paciente_numero'] : null,
        ':bairro' => !empty($_POST['paciente_bairro']) ? $_POST['paciente_bairro'] : null,
        ':cidade' => !empty($_POST['paciente_cidade']) ? $_POST['paciente_cidade'] : null,
        ':estado' => !empty($_POST['paciente_estado']) ? $_POST['paciente_estado'] : null,
    ];

    try {
        if ($paciente_id) {
            // --- UPDATE ---
            $data[':id'] = $paciente_id;
            $sql = "UPDATE pacientes SET 
                        nome = :nome, cpf = :cpf, data_nascimento = :data_nascimento, email = :email, telefone = :telefone, 
                        cep = :cep, endereco = :endereco, numero = :numero, bairro = :bairro, cidade = :cidade, estado = :estado
                    WHERE id = :id";
        } else {
            // --- INSERT ---
            $sql = "INSERT INTO pacientes (nome, cpf, data_nascimento, email, telefone, cep, endereco, numero, bairro, cidade, estado)
                    VALUES (:nome, :cpf, :data_nascimento, :email, :telefone, :cep, :endereco, :numero, :bairro, :cidade, :estado)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);

        header("Location: " . BASE_URL . "pacientes.php?msg=sucesso");
        exit;

    } catch (PDOException $e) {
        // Tratar erros, como CPF duplicado
        // O código de erro '23000' é genérico para violação de integridade. 
        // A mensagem de erro específica pode variar (ex: '1062' no MySQL para Duplicate entry)
        if ($e->getCode() == '23000') {
            $erro_msg = "Erro: Já existe um paciente com este CPF.";
        } else {
            $erro_msg = "Erro ao salvar paciente: " . $e->getMessage();
        }
        // Redirecionar para a página de origem com uma mensagem de erro
        $redirect_page = $paciente_id ? "editar_paciente.php?id=$paciente_id" : "pacientes.php";
        header("Location: " . BASE_URL . "$redirect_page?erro=" . urlencode($erro_msg));
        exit;
    }
}
