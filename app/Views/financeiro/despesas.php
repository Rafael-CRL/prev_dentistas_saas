<div class="card">
    <h2>Gestão de Despesas</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <div class="alert alert-success"><?= $_SESSION['feedback']; unset($_SESSION['feedback']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['feedback_erro'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['feedback_erro']; unset($_SESSION['feedback_erro']); ?></div>
    <?php endif; ?>

    <!-- Formulário para Adicionar Despesa -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Nova Despesa</h3>
        <form action="<?= BASE_URL ?>financeiro/despesas/salvar" method="POST">
            <?= \App\Helpers\CsrfHelper::input() ?>
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <input type="text" name="descricao" id="descricao" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select name="tipo" id="tipo" class="form-control" required>
                    <option value="fixa">Fixa</option>
                    <option value="variavel">Variável</option>
                </select>
            </div>
            <div class="form-group">
                <label for="data_despesa">Data</label>
                <input type="date" name="data_despesa" id="data_despesa" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <button type="submit" class="btn btn-success">Salvar Despesa</button>
        </form>
    </div>

    <!-- Tabela de Despesas -->
    <h3 style="margin-top: 2rem;">Despesas Lançadas</h3>
    <table class="table" style="margin-top: 1rem;">
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Tipo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($despesas)): ?>
                <?php foreach ($despesas as $despesa): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($despesa['data_despesa'])) ?></td>
                        <td><?= htmlspecialchars($despesa['descricao']) ?></td>
                        <td>R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></td>
                        <td><?= ucfirst($despesa['tipo']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>financeiro/despesas/excluir?id=<?= $despesa['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja remover esta despesa?');">Remover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhuma despesa registrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
