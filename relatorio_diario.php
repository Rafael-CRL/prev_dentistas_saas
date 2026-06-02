<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';
require_once 'config/controle_acesso.php';

if (!is_admin() && !is_dentista() && !is_recepcionista()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Pega a data da URL ou usa a data de hoje
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Navegação
$data_obj = new DateTime($data_selecionada);
$data_anterior = (clone $data_obj)->modify('-1 day')->format('Y-m-d');
$data_posterior = (clone $data_obj)->modify('+1 day')->format('Y-m-d');

try {
    // 1. Faturamento Bruto
    $stmtBruto = $pdo->prepare("
        SELECT SUM(ap.valor_procedimento) as total 
        FROM atendimentos a
        JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        WHERE DATE(a.data_atendimento) = ? AND a.status_pagamento = 'pago' AND ap.status_execucao = 'feito'
    ");
    $stmtBruto->execute([$data_selecionada]);
    $faturamento_bruto = $stmtBruto->fetchColumn() ?? 0;

    // 2. Taxas de Máquina
    $stmtTaxas = $pdo->prepare("SELECT SUM(a.taxa_cartao) as total FROM atendimentos a WHERE DATE(a.data_atendimento) = ? AND a.status_pagamento = 'pago' AND EXISTS (SELECT 1 FROM atendimento_procedimentos ap WHERE ap.id_atendimento = a.id AND ap.status_execucao = 'feito')");
    $stmtTaxas->execute([$data_selecionada]);
    $total_taxas = $stmtTaxas->fetchColumn() ?? 0;

    // 3. Pagamento por Dentista
    $sqlDentistas = "
        SELECT u.nome, SUM(a.comissao_dentista) as total_comissao
        FROM atendimentos a
        JOIN usuarios u ON a.id_dentista = u.id
        WHERE DATE(a.data_atendimento) = ? AND a.status_pagamento = 'pago' AND EXISTS (SELECT 1 FROM atendimento_procedimentos ap WHERE ap.id_atendimento = a.id AND ap.status_execucao = 'feito')
    ";
    $params = [$data_selecionada];

    if (is_dentista()) {
        $sqlDentistas .= " AND a.id_dentista = ?";
        $params[] = $_SESSION['usuario_id'];
    }

    $sqlDentistas .= "
        GROUP BY u.nome
        HAVING total_comissao > 0
        ORDER BY u.nome
    ";

    $stmtDentistas = $pdo->prepare($sqlDentistas);
    $stmtDentistas->execute($params);
    $pagamento_dentistas = $stmtDentistas->fetchAll();
    $total_comissoes = array_sum(array_column($pagamento_dentistas, 'total_comissao'));

    // 4. Despesas do Dia
    $stmtDespesas = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa = ? ORDER BY descricao");
    $stmtDespesas->execute([$data_selecionada]);
    $despesas_dia = $stmtDespesas->fetchAll();
    $total_despesas = array_sum(array_column($despesas_dia, 'valor'));

    // 5. Custo com Protético
    $stmtAuxiliar = $pdo->prepare("SELECT SUM(a.custo_auxiliar) as total FROM atendimentos a WHERE DATE(a.data_atendimento) = ? AND a.status_pagamento = 'pago' AND EXISTS (SELECT 1 FROM atendimento_procedimentos ap WHERE ap.id_atendimento = a.id AND ap.status_execucao = 'feito')");
    $stmtAuxiliar->execute([$data_selecionada]);
    $total_custo_auxiliar = $stmtAuxiliar->fetchColumn() ?? 0;

    // 6. Lucro Líquido
    $lucro_liquido = $faturamento_bruto - $total_taxas - $total_comissoes - $total_despesas - $total_custo_auxiliar;

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    // Zera os valores em caso de erro
    $faturamento_bruto = 0;
    $total_taxas = 0;
    $pagamento_dentistas = [];
    $total_comissoes = 0;
    $despesas_dia = [];
    $total_despesas = 0;
    $total_custo_auxiliar = 0;
    $lucro_liquido = 0;
}
?>

<div class="card">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <a href="?data=<?= $data_anterior ?>" class="btn btn-secondary">&lt; Dia Anterior</a>
        <h2>Relatório do Dia: <?= date('d/m/Y', strtotime($data_selecionada)) ?></h2>
        <a href="?data=<?= $data_posterior ?>" class="btn btn-secondary">Próximo Dia &gt;</a>
    </div>

    <form method="GET" action="relatorio_diario.php" class="card" style="margin-top: 1rem; margin-bottom: 2rem;">
        <div class="form-group" style="max-width: 300px; margin: auto;">
            <label for="data">Selecionar outra data</label>
            <input type="date" name="data" id="data" value="<?= $data_selecionada ?>" onchange="this.form.submit()">
        </div>
    </form>

    <?php if (is_admin()): ?>
    <div class="dashboard-grid">
        
        <div class="stat-card">
            <h3>Entrada Bruta</h3>
            <div class="stat-value" style="color: var(--primary-color);">R$ <?= number_format($faturamento_bruto, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Taxas de Cartão</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_taxas, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Custo Auxiliar</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_custo_auxiliar, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Saídas (Despesas)</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_despesas, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3>Lucro Líquido do Dia</h3>
            <div class="stat-value">R$ <?= number_format($lucro_liquido, 2, ',', '.') ?></div>
        </div>
    </div>
    <?php endif; ?>


    <div class="card" style="margin-top: 2rem;">
        <h3>Pagamentos por Dentista</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Dentista</th>
                    <th>Valor a Pagar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pagamento_dentistas) > 0): ?>
                    <?php foreach ($pagamento_dentistas as $dentista): ?>
                    <tr>
                        <td data-label = "Nome"><?= htmlspecialchars($dentista['nome']) ?></td>
                        <td data-label = "Comissão Densista" style="color: var(--success-color); font-weight: bold;">R$ <?= number_format($dentista['total_comissao'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center;">Nenhum atendimento comissionado no dia.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (is_admin()): ?>
             <tfoot>
                <tr style="font-weight: bold;">
                    <td>Total Comissões</td>
                    <td data-label = "Total" style="color: var(--danger-color);">- R$ <?= number_format($total_comissoes, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php if (is_admin()): ?>
    <div class="card" style="margin-top: 2rem;">
        <h3>Despesas Detalhadas do Dia</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($despesas_dia) > 0): ?>
                    <?php foreach ($despesas_dia as $despesa): ?>
                    <tr>
                        <td data-label = "Descrição"><?= htmlspecialchars($despesa['descricao']) ?></td>
                        <td data-label = "Tipo"><?= ucfirst($despesa['tipo']) ?></td>
                        <td data-label = "Valor">R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center;">Nenhuma despesa registrada para este dia.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2">Total Despesas</td>
                    <td>R$ <?= number_format($total_despesas, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'views/footer.php'; ?>
