<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';
require_once '../config/controle_acesso.php';

// Apenas usuários com permissão podem acessar
if (!is_admin() && !is_dentista()) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Acesso negado.'];
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

function redirect_with_error($message, $paciente_nome) {
    $redirect_url = BASE_URL . 'relatorio_paciente3.php?paciente_nome=' . urlencode($paciente_nome) . '&erro=' . urlencode($message);
    header("Location: " . $redirect_url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atendimento_procedimento_id = $_POST['atendimento_procedimento_id'] ?? null;
    $paciente_nome_redirect = $_POST['paciente_nome_redirect'] ?? '';

    if (!$atendimento_procedimento_id || !isset($_FILES['arquivo_procedimento'])) {
        redirect_with_error('Dados inválidos para o upload.', $paciente_nome_redirect);
    }

    if ($_FILES['arquivo_procedimento']['error'] !== UPLOAD_ERR_OK) {
        redirect_with_error('Erro no upload do arquivo. Código: ' . $_FILES['arquivo_procedimento']['error'], $paciente_nome_redirect);
    }

    try {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['arquivo_procedimento']['tmp_name']);
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mimeType, $allowedMimeTypes)) {
            redirect_with_error('Formato de arquivo não permitido. Apenas PDF, JPG e PNG.', $paciente_nome_redirect);
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = 'proc_' . $atendimento_procedimento_id . '_' . uniqid() . '.' . $extension;
        $uploadFile = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['arquivo_procedimento']['tmp_name'], $uploadFile)) {
            throw new Exception("Falha ao mover o arquivo enviado.");
        }

        $urlArquivo = 'uploads/' . $fileName;

        // Atualiza o banco de dados
        $stmt = $pdo->prepare("UPDATE atendimento_procedimentos SET url_arquivo = ? WHERE id = ?");
        $stmt->execute([$urlArquivo, $atendimento_procedimento_id]);

        $redirect_url = BASE_URL . 'relatorio_paciente3.php?paciente_nome=' . urlencode($paciente_nome_redirect) . '&msg=upload_sucesso';
        header("Location: " . $redirect_url);
        exit;

    } catch (Exception $e) {
        error_log("Erro em salvar_arquivo_procedimento.php: " . $e->getMessage());
        redirect_with_error('Ocorreu um erro interno ao salvar o arquivo.', $paciente_nome_redirect);
    }
} else {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
?>