<div class="card">
    <h2>Editar Procedimento</h2>

    <?php require_once __DIR__ . '/../partials/alert.php'; ?>

    <form action="<?= BASE_URL ?>procedimentos/salvar" method="POST">
        <?= \App\Helpers\CsrfHelper::input() ?>
        <input type="hidden" name="id" value="<?= $procedimento['id'] ?>">
        
        <div class="form-group">
            <label for="nome">Nome do Procedimento</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($procedimento['nome']) ?>" required>
        </div>

        <div class="form-group">
            <label for="categoria">Categoria</label>
            <select name="categoria" id="categoria" required>
                <option value="geral" <?= $procedimento['categoria'] === 'geral' ? 'selected' : '' ?>>Geral</option>
                <option value="especializado" <?= $procedimento['categoria'] === 'especializado' ? 'selected' : '' ?>>Especializado</option>
                <option value="protese" <?= $procedimento['categoria'] === 'protese' ? 'selected' : '' ?>>Prótese</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tipo">Arquivo</label>
            <select name="tipo" id="tipo" required>
                <option value="0" <?= (int)$procedimento['tipo'] === 0 ? 'selected' : '' ?>>Sem Arquivo</option>
                <option value="1" <?= (int)$procedimento['tipo'] === 1 ? 'selected' : '' ?>>Com Arquivo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="valor_base">Valor Base (R$)</label>
            <input type="number" step="0.01" name="valor_base" id="valor_base" value="<?= $procedimento['valor_base'] ?>">
            <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">
                * Alterar o preço aqui afetará apenas novos atendimentos. Históricos faturados permanecem inalterados.
            </p>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="<?= BASE_URL ?>procedimentos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.success { color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px; }
.error   { color: red;   background: #ffebee; padding: 1rem; border-radius: 6px; }
</style>
