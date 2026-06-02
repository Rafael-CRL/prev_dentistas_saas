<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/controle_acesso.php';

// Apenas administradores podem acessar esta página
if (!is_admin()) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';
require_once 'views/header.php';

// Busca usuários
try {
    $stmt = $pdo->query("SELECT id, nome, login, perfil FROM usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<p class='error'>Erro ao buscar usuários: " . $e->getMessage() . "</p>";
    $usuarios = [];
}
?>

<div class="card">
    <h2>Gestão de Usuários</h2>

    <?php if (isset($_GET['erro'])):
        $erro = $_GET['erro'];
        if ($erro === 'login_duplicado') {
            echo "<p class='error'>O login informado já está em uso. Por favor, escolha outro.</p>";
        } elseif ($erro === 'autoexclusao') {
            echo "<p class='error'>Você não pode excluir seu próprio usuário.</p>";
        } elseif ($erro === 'conflito_atendimento') {
            echo "<p class='error'>Não é possível excluir o usuário, pois ele está vinculado a atendimentos.</p>";
        }
    endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
        <p style="color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px;">Usuário salvo com sucesso!</p>
    <?php endif; ?>

    <!-- Formulário para Adicionar Usuário -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Usuário</h3>
        <form action="<?= BASE_URL ?>actions/salvar_usuario.php" method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="login">Login</label>
                <input type="text" name="login" id="login" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <div class="form-group">
                <label for="perfil">Perfil</label>
                <select name="perfil" id="perfil" required>
                    <option value="recepcionista">Recepcionista</option>
                    <option value="dentista">Dentista</option>
                    <option value="proprietario">Proprietário</option>                    
                </select>
            </div>
            <button type="submit" class="btn btn-success">Salvar Usuário</button>
        </form>
    </div>

    <!-- Tabela de Usuários -->
    <h3 style="margin-top: 2rem;">Usuários Cadastrados</h3>
    <table class="mobile-card-table" style="margin-top: 1rem;">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Login</th>
                <th>Perfil</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($usuarios) > 0): ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td data-label="Nome"><?= htmlspecialchars($usuario['nome']) ?></td>
                        <td data-label="Login"><?= htmlspecialchars($usuario['login']) ?></td>
                        <td data-label="Perfil"><?= ucfirst($usuario['perfil']) ?></td>
                        <td data-label= "Ações" style="display: flex; gap: 0.5rem;">
                            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-primary">Editar</a>
                            <?php if ($usuario['id'] !== $_SESSION['usuario_id']): // Não pode excluir a si mesmo ?>
                                <a href="<?= BASE_URL ?>actions/excluir_usuario.php?id=<?= $usuario['id'] ?>" class="btn btn-danger" onclick="return confirm('Você realmente deseja remover esse usuário?');">Remover</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum usuário registrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'views/footer.php'; ?>
