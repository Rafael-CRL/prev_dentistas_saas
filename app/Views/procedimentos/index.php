<div class="card">
    <h2>Gestão de Procedimentos</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <p class="<?= $_SESSION['feedback']['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($_SESSION['feedback']['message']) ?>
        </p>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Procedimento</h3>
        <form action="<?= BASE_URL ?>procedimentos/salvar" method="POST">
            <div class="form-group">
                <label for="nome">Nome do Procedimento</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" required>
                    <option value="geral">Geral</option>
                    <option value="especializado">Especializado</option>
                    <option value="protese">Prótese</option>
                </select>
            </div>
            <div class="form-group">
                <label for="tipo">Arquivo</label>
                <select name="tipo" id="tipo" required>
                    <option value="0">Sem Arquivo</option>
                    <option value="1">Com Arquivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="valor_base">Valor Base (R$)</label>
                <input type="number" step="0.01" name="valor_base" id="valor_base">
            </div>
            <button type="submit" class="btn btn-success">Salvar Procedimento</button>
        </form>
    </div>

    <h3 style="margin-top: 2rem;">Procedimentos Cadastrados</h3>
    <table class="mobile-card-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Valor Base</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($procedimentos) > 0): ?>
                <?php foreach ($procedimentos as $proc): ?>
                    <tr>
                        <td data-label="Nome"><?= htmlspecialchars($proc['nome']) ?></td>
                        <td data-label="Categoria"><?= ucfirst($proc['categoria']) ?></td>
                        <td data-label="Valor Base">
                            R$ <?= number_format((float)$proc['valor_base'], 2, ',', '.') ?>
                        </td>
                        <td data-label="Ações">
                            <a href="<?= BASE_URL ?>procedimentos/excluir?id=<?= $proc['id'] ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Tem certeza que deseja remover este procedimento?');">
                                Remover
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum procedimento registrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.success { color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px; }
.error   { color: red;   background: #ffebee; padding: 1rem; border-radius: 6px; }
</style>
