<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';
require_once 'config/controle_acesso.php';

// Apenas administradores podem acessar esta página
if (!is_admin() && !is_dentista()){
    header('Location: index.php');
    exit;
}

// Filtros
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
//Se o usuario for dentista, o filtro é travado no ID dele.
if(is_dentista() && !is_admin()){
    $dentista_id = $_SESSION['usuario_id'];
}else{
    $dentista_id = isset($_GET['dentista_id']) ? $_GET['dentista_id'] : 'todos';
}

// Define o período com base no mês selecionado
$data_inicio = date('Y-m-01', strtotime($mes));
$data_fim = date('Y-m-t', strtotime($mes));

try {
    
    // Busca todos os dentistas para o dropdown apenas para o admin
    $dentistas = [];
    if (is_admin()) {   
    $stmtDentistas = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'dentista' ORDER BY nome");
    $dentistas = $stmtDentistas->fetchAll();
    }

    // Monta a query base
    $sql = "
        SELECT
            u.id as dentista_id,
            u.nome as dentista_nome,
            COUNT(atendimento_agg.id) as total_atendimentos,
            SUM(atendimento_agg.faturamento_bruto) as faturamento_bruto,
            SUM(atendimento_agg.valor_liquido_clinica) as valor_para_clinica,
            SUM(atendimento_agg.comissao_dentista) as valor_para_dentista
        FROM usuarios u
        JOIN (
            SELECT
                a.id_dentista,
                a.id,
                a.valor_liquido_clinica,
                a.comissao_dentista,
                SUM(ap.valor_procedimento) as faturamento_bruto
            FROM atendimentos a
            JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
            WHERE a.data_atendimento BETWEEN :data_inicio AND :data_fim
              AND ap.status_execucao IN ('finalizado', 'feito')
              AND a.status_pagamento = 'pago'
            GROUP BY a.id, a.id_dentista, a.valor_liquido_clinica, a.comissao_dentista
        ) as atendimento_agg ON u.id = atendimento_agg.id_dentista
    ";
    
    $params = [
        'data_inicio' => $data_inicio . ' 00:00:00',
        'data_fim' => $data_fim . ' 23:59:59'
    ];

    // Adiciona filtro de dentista se não for 'todos'
    if ($dentista_id !== 'todos') {
        $sql .= " AND u.id = :dentista_id";
        $params['dentista_id'] = $dentista_id;
    }

    $sql .= " GROUP BY u.id, u.nome ORDER BY faturamento_bruto DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $relatorio_dentistas = $stmt->fetchAll();

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    $relatorio_dentistas = [];
    $dentistas = [];
}
?>

<div class="card">
    <h2>Relatório de Desempenho por Dentista</h2>

    <form method="GET" action="relatorio_dentistas.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div class="form-group">
                <label for="mes">Mês</label>
                <input type="month" name="mes" id="mes" value="<?= $mes ?>">
            </div>
            <?php if (is_admin()): ?>
            <div class="form-group">
                <label for="dentista_id">Dentista</label>
                <select name="dentista_id" id="dentista_id">
                    <option value="todos">Todos</option>
                    <?php foreach ($dentistas as $dentista): ?>
                        <option value="<?= $dentista['id'] ?>" <?= $dentista_id == $dentista['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dentista['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div style="margin-top: 2rem;">
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Dentista</th>
                    <th>Nº de Atendimentos</th>
                    <th>Faturamento Bruto</th>
                    <th>Valor p/ Dentista</th>
                    <th>Valor p/ Clínica</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($relatorio_dentistas) > 0): ?>
                    <?php foreach($relatorio_dentistas as $rel): ?>
                    <tr>
                        <td data-label = "Dentista"><?= htmlspecialchars($rel['dentista_nome']) ?></td>
                        <td data-label = "Atendimentos"><?= $rel['total_atendimentos'] ?></td>
                        <td data-label = "Faturamento Bruto">R$ <?= number_format($rel['faturamento_bruto'], 2, ',', '.') ?></td>
                        <td data-label = "Faturamento Dentista" style="color: var(--success-color); font-weight: bold;"><?= number_format($rel['valor_para_dentista'], 2, ',', '.') ?></td>
                        <td data-label = "Faturamento Clínica" style="color: var(--success-color); font-weight: bold;"><?= number_format($rel['valor_para_clinica'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
