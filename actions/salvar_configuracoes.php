<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $nome = trim($_POST['nome']);
    
    $senha_antiga = $_POST['senha_antiga'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome)) {
        header("Location: " . BASE_URL . "configuracoes.php?erro=geral");
        exit;
    }

    try {
        // 1. Busca a senha atual (hash) do banco para validação
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $usuario_atual = $stmt->fetch();

        if (!$usuario_atual) {
            // Usuário não encontrado (sessão inválida)
            session_destroy();
            header("Location: " . BASE_URL . "login.php");
            exit;
        }

        // Inicia a query de atualização
        $sql = "UPDATE usuarios SET nome = ?";
        $params = [$nome];

        // 2. Verifica se o usuário quer trocar a senha
        if (!empty($senha_antiga) || !empty($nova_senha) || !empty($confirmar_senha)) {
            
            // Validação: Todos os campos de senha devem estar preenchidos
            if (empty($senha_antiga) || empty($nova_senha) || empty($confirmar_senha)) {
                header("Location: " . BASE_URL . "configuracoes.php?erro=campos_vazios");
                exit;
            }

            // Validação: Senha antiga deve bater com o hash do banco
            if (!password_verify($senha_antiga, $usuario_atual['senha'])) {
                header("Location: " . BASE_URL . "configuracoes.php?erro=senha_incorreta");
                exit;
            }

            // Validação: Nova senha e confirmação devem ser iguais
            if ($nova_senha !== $confirmar_senha) {
                header("Location: " . BASE_URL . "configuracoes.php?erro=senhas_nao_coincidem");
                exit;
            }

            // Adiciona a nova senha à query
            $sql .= ", senha = ?";
            $params[] = password_hash($nova_senha, PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $usuario_id;

        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);

        // Atualiza o nome na sessão para refletir a mudança imediatamente
        $_SESSION['usuario_nome'] = $nome;

        header("Location: " . BASE_URL . "configuracoes.php?msg=sucesso");
        exit;

    } catch (Exception $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        header("Location: " . BASE_URL . "configuracoes.php?erro=geral");
        exit;
    }
}
?>