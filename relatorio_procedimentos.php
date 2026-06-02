<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';
require_once 'config/controle_acesso.php';

if (!is_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Filtro de data: O período vem preenchido por padrão com o mês atual
$data_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
$data_fim = isset($_GET['fim']) ? $_GET['fim'] : date('Y-m-t');

try {
    // Buscar total de procedimentos executados no período para calcular a porcentagem
    $stmtTotal = $pdo->prepare("
        SELECT SUM(ap.quantidade) 
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.id_atendimento = a.id
        WHERE a.data_atendimento BETWEEN ? AND ? 
        AND ap.status_execucao = 'feito'
        AND a.status_pagamento = 'pago'
    ");
    $stmtTotal->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $totalProcedimentos = $stmtTotal->fetchColumn() ?? 0;

    // Buscar a lista de procedimentos, quantidade, e valor bruto
    $stmtProc = $pdo->prepare("
        SELECT 
            p.nome as procedimento_nome,
            SUM(ap.quantidade) as quantidade_executada,
            SUM(ap.valor_procedimento) as valor_bruto_total
        FROM atendimento_procedimentos ap
        JOIN atendimentos a ON ap.id_atendimento = a.id
        JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE a.data_atendimento BETWEEN ? AND ? 
        AND ap.status_execucao = 'feito'
        AND a.status_pagamento = 'pago'
        GROUP BY p.id, p.nome
        ORDER BY quantidade_executada DESC, valor_bruto_total DESC
    ");
    $stmtProc->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $procedimentos_relatorio = $stmtProc->fetchAll();

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    $procedimentos_relatorio = [];
    $totalProcedimentos = 0;
}
?>

<div class="card">
    <h2>Relatório por Procedimentos</h2>

    <form method="GET" action="relatorio_procedimentos.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= htmlspecialchars($data_inicio) ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= htmlspecialchars($data_fim) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div style="margin-top: 2rem;">
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Procedimento</th>
                    <th>Vezes Executado</th>
                    <th>Representação (%)</th>
                    <th>Valor Bruto Gerado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($procedimentos_relatorio) > 0): ?>
                    <?php foreach($procedimentos_relatorio as $proc): 
                        $porcentagem = $totalProcedimentos > 0 ? ($proc['quantidade_executada'] / $totalProcedimentos) * 100 : 0;
                    ?>
                    <tr>
                        <td data-label="Procedimento"><?= htmlspecialchars($proc['procedimento_nome']) ?></td>
                        <td data-label="Vezes Executado"><?= htmlspecialchars($proc['quantidade_executada']) ?></td>
                        <td data-label="Representação (%)"><?= number_format($porcentagem, 2, ',', '.') ?>%</td>
                        <td data-label="Valor Bruto Gerado" style="color: var(--success-color); font-weight: bold;">R$ <?= number_format($proc['valor_bruto_total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">Nenhum procedimento encontrado para o período selecionado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (count($procedimentos_relatorio) > 0): ?>
            <tfoot>
                <tr style="font-weight: bold; background-color: #f8f9fa;">
                    <td>Total</td>
                    <td data-label="Total Executado"><?= $totalProcedimentos ?></td>
                    <td data-label="Total %">100,00%</td>
                    <td data-label="Soma Valor Bruto" style="color: var(--success-color);">R$ <?= number_format(array_sum(array_column($procedimentos_relatorio, 'valor_bruto_total')), 2, ',', '.') ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>