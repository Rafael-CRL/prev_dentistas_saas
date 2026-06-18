<div class="card">
    <h2>Editar Usuário</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <p class="<?= $_SESSION['feedback']['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($_SESSION['feedback']['message']) ?>
        </p>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>usuarios/salvar" method="POST" style="margin-top: 1rem;">
        <?= \App\Helpers\CsrfHelper::input() ?>
        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
        
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="login">Login</label>
            <input type="text" name="login" id="login" value="<?= htmlspecialchars($usuario['login']) ?>" required>
        </div>

        <div class="form-group">
            <label for="senha">Nova Senha (deixe em branco para não alterar)</label>
            <input type="password" name="senha" id="senha">
        </div>

        <div class="form-group">
            <label for="perfil">Perfil</label>
            <select name="perfil" id="perfil" required>
                <option value="recepcionista" <?= $usuario['perfil'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                <option value="dentista" <?= $usuario['perfil'] === 'dentista' ? 'selected' : '' ?>>Dentista</option>
                <option value="proprietario" <?= $usuario['perfil'] === 'proprietario' ? 'selected' : '' ?>>Proprietário</option>
            </select>
        </div>

        <div class="form-group">
            <label for="percentual_comissao">Percentual de Comissão Individual (%) <span style="font-size: 0.85rem; font-weight: normal; color: #666;">(Opcional - Apenas Dentistas. Se vazio, aplica a regra por categoria)</span></label>
            <input type="number" step="0.01" min="0" max="100" name="percentual_comissao" id="percentual_comissao" value="<?= $usuario['percentual_comissao'] !== null ? htmlspecialchars($usuario['percentual_comissao']) : '' ?>">
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="<?= BASE_URL ?>usuarios" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
