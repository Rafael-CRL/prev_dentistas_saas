<div class="card">
    <h2>Gestão de Usuários</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <p class="<?= $_SESSION['feedback']['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($_SESSION['feedback']['message']) ?>
        </p>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

    <!-- Formulário para Adicionar Usuário -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Usuário</h3>
        <form action="<?= BASE_URL ?>usuarios/salvar" method="POST">
            <?= \App\Helpers\CsrfHelper::input() ?>
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
                            <a href="<?= BASE_URL ?>usuarios/editar?id=<?= $usuario['id'] ?>" class="btn btn-primary">Editar</a>
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): // Não pode excluir a si mesmo ?>
                                <a href="<?= BASE_URL ?>usuarios/remover?id=<?= $usuario['id'] ?>" class="btn btn-danger" onclick="return confirm('Você realmente deseja remover esse usuário?');">Remover</a>
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
