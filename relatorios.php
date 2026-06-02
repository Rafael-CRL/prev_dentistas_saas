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

// Filtro de data
$data_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
$data_fim = isset($_GET['fim']) ? $_GET['fim'] : date('Y-m-t');

// Paginação
$itensPorPagina = 10;

// Paginação Atendimentos
$pagina_at = isset($_GET['pagina_at']) ? (int)$_GET['pagina_at'] : 1;
if ($pagina_at < 1) $pagina_at = 1;
$offset_at = ($pagina_at - 1) * $itensPorPagina;

// Paginação Despesas
$pagina_de = isset($_GET['pagina_de']) ? (int)$_GET['pagina_de'] : 1;
if ($pagina_de < 1) $pagina_de = 1;
$offset_de = ($pagina_de - 1) * $itensPorPagina;
try {
    // Totais
    // Cálculo do Bruto (soma dos procedimentos vinculados aos atendimentos do período)
    $stmtBruto = $pdo->prepare("
        SELECT SUM(ap.valor_procedimento) 
        FROM atendimentos a 
        JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        WHERE a.data_atendimento BETWEEN ? AND ? AND a.status_pagamento = 'pago' AND ap.status_execucao = 'feito'
    ");
    $stmtBruto->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $bruto = $stmtBruto->fetchColumn() ?? 0;

    // Cálculo do Líquido (soma do valor líquido da clínica registrado no atendimento)
    $stmtLiquido = $pdo->prepare("
        SELECT SUM(valor_liquido_clinica) 
        FROM atendimentos 
        WHERE data_atendimento BETWEEN ? AND ? AND status_pagamento = 'pago'
    ");
    $stmtLiquido->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $liquido = $stmtLiquido->fetchColumn() ?? 0;

    $financas = ['bruto' => $bruto, 'liquido' => $liquido];

    $stmtDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $stmtDespesas->execute([$data_inicio, $data_fim]);
    $despesas = $stmtDespesas->fetchColumn();

    // Detalhes de Atendimentos com Paginação
    // Contagem de atendimentos para paginação
    $stmtCountAtendimentos = $pdo->prepare("
        SELECT COUNT(DISTINCT a.id) 
        FROM atendimentos a 
        WHERE a.data_atendimento BETWEEN ? AND ? AND a.status_pagamento = 'pago'
    ");
    $stmtCountAtendimentos->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $totalRegistrosAtendimentos = $stmtCountAtendimentos->fetchColumn();
    $totalPaginasAtendimentos = ceil($totalRegistrosAtendimentos / $itensPorPagina);

    $stmtAtendimentos = $pdo->prepare("
        SELECT 
            a.id, a.data_atendimento, p.nome as paciente_nome, a.valor_liquido_clinica, 
            u.nome as dentista, 
            GROUP_CONCAT(CASE WHEN ap.status_execucao = 'feito' THEN proc.nome END SEPARATOR ', ') as procedimento, 
            SUM(CASE WHEN ap.status_execucao = 'feito' THEN ap.valor_procedimento ELSE 0 END) as valor_bruto 
        FROM atendimentos a 
        JOIN pacientes p ON a.paciente_id = p.id
        JOIN usuarios u ON a.id_dentista = u.id 
        LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento 
        LEFT JOIN procedimentos proc ON ap.id_procedimento = proc.id 
        WHERE a.data_atendimento BETWEEN :data_inicio AND :data_fim AND a.status_pagamento = 'pago'
        GROUP BY a.id
        ORDER BY a.data_atendimento DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmtAtendimentos->bindValue(':data_inicio', $data_inicio . ' 00:00:00');
    $stmtAtendimentos->bindValue(':data_fim', $data_fim . ' 23:59:59');
    $stmtAtendimentos->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmtAtendimentos->bindValue(':offset', $offset_at, PDO::PARAM_INT);
    $stmtAtendimentos->execute();
    $atendimentos = $stmtAtendimentos->fetchAll();

    // Detalhes de Despesas com Paginação
    $stmtCountDespesas = $pdo->prepare("SELECT COUNT(id) FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $stmtCountDespesas->execute([$data_inicio, $data_fim]);
    $totalRegistrosDespesas = $stmtCountDespesas->fetchColumn();
    $totalPaginasDespesas = ceil($totalRegistrosDespesas / $itensPorPagina);

    $stmtListaDespesas = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa BETWEEN :data_inicio AND :data_fim ORDER BY data_despesa DESC LIMIT :limit OFFSET :offset");
    $stmtListaDespesas->bindValue(':data_inicio', $data_inicio);
    $stmtListaDespesas->bindValue(':data_fim', $data_fim);
    $stmtListaDespesas->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmtListaDespesas->bindValue(':offset', $offset_de, PDO::PARAM_INT);
    $stmtListaDespesas->execute();
    $listaDespesas = $stmtListaDespesas->fetchAll();

    // Dados para o gráfico
    $stmtGrafico = $pdo->prepare("
        SELECT
            dia,
            SUM(faturamento) as faturamento,
            SUM(despesa) as despesa
        FROM (
            SELECT
                DATE(a.data_atendimento) as dia,
                SUM(ap.valor_procedimento) as faturamento,
                0 as despesa
            FROM atendimentos a
            JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
            WHERE a.data_atendimento BETWEEN ? AND ? AND a.status_pagamento = 'pago' AND ap.status_execucao = 'feito'
            GROUP BY DATE(a.data_atendimento)

            UNION ALL

            SELECT
                DATE(data_despesa) as dia,
                0 as faturamento,
                SUM(valor) as despesa
            FROM despesas
            WHERE data_despesa BETWEEN ? AND ?
            GROUP BY DATE(data_despesa)
        ) as T
        GROUP BY dia
        ORDER BY dia
    ");
    $stmtGrafico->execute([
        $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59',
        $data_inicio, $data_fim
    ]);
    // Alterado para um método de processamento mais robusto
    $rawDadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);
    $dadosGrafico = [];
    foreach ($rawDadosGrafico as $row) {
        $dadosGrafico[$row['dia']] = $row;
    }

    $stmtLiquidoGrafico = $pdo->prepare("
        SELECT
            DATE(data_atendimento) as dia,
            SUM(valor_liquido_clinica) as liquido
        FROM atendimentos
        WHERE data_atendimento BETWEEN ? AND ? AND status_pagamento = 'pago'
        GROUP BY DATE(data_atendimento)
        ORDER BY dia
    ");
    $stmtLiquidoGrafico->execute([
        $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59'
    ]);
    $rawDadosLiquidoGrafico = $stmtLiquidoGrafico->fetchAll(PDO::FETCH_ASSOC);
    $dadosLiquidoGrafico = [];
    foreach ($rawDadosLiquidoGrafico as $row) {
        $dadosLiquidoGrafico[$row['dia']] = $row;
    }


    // Preparar dados para o Chart.js
    $labels = [];
    $faturamentoData = [];
    $despesaData = [];
    $lucroLiquidoData = [];

    $begin = new DateTime($data_inicio);
    $end = new DateTime($data_fim);
    $end->setTime(23, 59, 59);

    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($begin, $interval, $end);

    foreach ($period as $dt) {
        $data = $dt->format('Y-m-d');
        $labels[] = $dt->format('d/m');
        
        $faturamento = $dadosGrafico[$data]['faturamento'] ?? 0;
        $despesa = $dadosGrafico[$data]['despesa'] ?? 0;
        $liquido = $dadosLiquidoGrafico[$data]['liquido'] ?? 0;

        $faturamentoData[] = $faturamento;
        $despesaData[] = $despesa;
        $lucroLiquidoData[] = $liquido - $despesa;
    }

    // Dados para o gráfico de pizza de pagamentos
    $stmtPagamentos = $pdo->prepare("
        SELECT
            forma_pagamento,
            SUM(valor) as total
        FROM
            atendimento_pagamentos ap
        JOIN
            atendimentos a ON ap.id_atendimento = a.id
        WHERE
            a.data_atendimento BETWEEN ? AND ?
        GROUP BY
            forma_pagamento
    ");
    $stmtPagamentos->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $dadosPagamentos = $stmtPagamentos->fetchAll(PDO::FETCH_KEY_PAIR);

    $pagamentoLabels = [];
    $pagamentoData = [];
    if ($dadosPagamentos) {
        $pagamentoLabels = array_keys($dadosPagamentos);
        $pagamentoData = array_values($dadosPagamentos);
        // Capitalize labels for better readability
        $pagamentoLabels = array_map('ucfirst', $pagamentoLabels);
    }

    // --- DEBUG ---
    // Dados para a notificação de depuração
   /* $debug_data = [
        'periodo' => ['inicio' => $data_inicio, 'fim' => $data_fim],
        'dados_brutos_faturamento_despesa' => $dadosGrafico,
        'dados_brutos_liquido' => $dadosLiquidoGrafico,
        'dados_finais_grafico' => [
            'labels' => $labels,
            'faturamento' => $faturamentoData,
            'despesas' => $despesaData,
            'lucro_liquido' => $lucroLiquidoData
        ]
    ];*/
    // --- FIM DEBUG ---


} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    // Seta valores padrão para evitar erros na renderização
    $financas = ['bruto' => 0, 'liquido' => 0];
    $despesas = 0;
    $atendimentos = [];
    $listaDespesas = [];
    $totalPaginasAtendimentos = 0;
    $totalPaginasDespesas = 0;
    $labels = [];
    $faturamentoData = [];
    $despesaData = [];
    $lucroLiquidoData = [];
    $pagamentoLabels = [];
    $pagamentoData = [];
}
?>

<!-- Notificação de depuração temporária 
<div id="debug-notification" style="position: fixed; top: 80px; right: 20px; background: #fff; border: 1px solid #ccc; padding: 15px; z-index: 10000; box-shadow: 0 5px 15px rgba(0,0,0,0.2); max-height: 80vh; overflow: auto; max-width: 500px;">
    <button onclick="this.parentElement.style.display='none'" style="position: absolute; top: 5px; right: 5px; border: none; background: #eee; cursor: pointer; padding: 2px 5px; font-size: 14px;">&times;</button>
    <h4 style="margin-top: 0;">Valores para o Gráfico (Debug)</h4>
    <p style="font-size: 0.9em; color: #666;">Dados crus do banco e processados para o gráfico.</p>
    <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars(json_encode($debug_data, JSON_PRETTY_PRINT)) ?></pre>
</div>
-->
<div class="card">
    <h2>Relatório Financeiro</h2>

    <form method="GET" action="relatorios.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="dashboard-grid" style="margin-top: 2rem;">
        <div class="stat-card">
            <h3>Faturamento Bruto</h3>
            <div class="stat-value">R$ <?= number_format($financas['bruto'] ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Total Despesas</h3>
            <div class="stat-value">R$ <?= number_format($despesas ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3>Lucro Líquido</h3>
            <div class="stat-value">R$ <?= number_format(($financas['liquido'] ?? 0) - ($despesas ?? 0), 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="chart-buttons" style="margin-top: 2rem; text-align: center; margin-bottom: 1rem; display: flex; justify-content: center; gap: 10px;">
        <button id="btnEvolucao" class="btn btn-primary">Ver Evolução Financeira</button>
        <button id="btnPagamentos" class="btn btn-secondary">Ver Distribuição de Pagamentos</button>
    </div>

    <div id="chart-evolucao-container" style="margin-top: 1rem;">
        <h3>Evolução Financeira</h3>
        <canvas id="evolucaoFinanceiraChart" style="max-height: 400px;"></canvas>
    </div>

    <div id="chart-pagamentos-container" style="margin-top: 1rem; display: none;">
        <h3>Distribuição de Pagamentos</h3>
        <canvas id="pagamentosChart" style="max-height: 400px;"></canvas>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Atendimentos</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Procedimento</th>
                    <th>Valor Bruto</th>
                    <th>Valor Líquido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($atendimentos as $at): ?>
                <tr>
                    <td data-label = "Data"><?= date('d/m/Y', strtotime($at['data_atendimento'])) ?></td>
                    <td data-label = "Paciente"><?= htmlspecialchars($at['paciente_nome']) ?></td>
                    <td data-label = "Procedimento"><?= htmlspecialchars($at['procedimento'] ?? '') ?></td>
                    <td data-label = "Valor Bruto">R$ <?= number_format($at['valor_bruto'], 2, ',', '.') ?></td>
                    <td data-label = "Lucro Líquido">R$ <?= number_format($at['valor_liquido_clinica'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação Atendimentos -->
        <?php if ($totalPaginasAtendimentos > 1): ?>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
            <?php for ($i = 1; $i <= $totalPaginasAtendimentos; $i++): ?>
                <?php 
                    $active = $i === $pagina_at ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                    $queryParams = $_GET;
                    $queryParams['pagina_at'] = $i;
                    $url = '?' . http_build_query($queryParams);
                ?>
                <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Despesas</h3>
        <table class="mobile-card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listaDespesas as $dp): ?>
                <tr>
                    <td data-label = "Data"><?= date('d/m/Y', strtotime($dp['data_despesa'])) ?></td>
                    <td data-label = "Descrição"><?= htmlspecialchars($dp['descricao']) ?></td>
                    <td data-label = "Tipo"><?= ucfirst($dp['tipo']) ?></td>
                    <td data-label = "Valor">R$ <?= number_format($dp['valor'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação Despesas -->
        <?php if ($totalPaginasDespesas > 1): ?>
        <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
            <?php for ($i = 1; $i <= $totalPaginasDespesas; $i++): ?>
                <?php 
                    $active = $i === $pagina_de ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                    $queryParams = $_GET;
                    $queryParams['pagina_de'] = $i;
                    $url = '?' . http_build_query($queryParams);
                ?>
                <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEvolucao = document.getElementById('btnEvolucao');
    const btnPagamentos = document.getElementById('btnPagamentos');
    const evolucaoContainer = document.getElementById('chart-evolucao-container');
    const pagamentosContainer = document.getElementById('chart-pagamentos-container');

    // Botão para mostrar o gráfico de evolução
    btnEvolucao.addEventListener('click', () => {
        evolucaoContainer.style.display = 'block';
        pagamentosContainer.style.display = 'none';
        
        btnEvolucao.classList.add('btn-primary');
        btnEvolucao.classList.remove('btn-secondary');
        
        btnPagamentos.classList.add('btn-secondary');
        btnPagamentos.classList.remove('btn-primary');
    });

    // Botão para mostrar o gráfico de pagamentos
    btnPagamentos.addEventListener('click', () => {
        evolucaoContainer.style.display = 'none';
        pagamentosContainer.style.display = 'block';

        btnPagamentos.classList.add('btn-primary');
        btnPagamentos.classList.remove('btn-secondary');

        btnEvolucao.classList.add('btn-secondary');
        btnEvolucao.classList.remove('btn-primary');
    });
    const ctx = document.getElementById('evolucaoFinanceiraChart').getContext('2d');
    const evolucaoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Faturamento Bruto',
                    data: <?= json_encode($faturamentoData) ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Despesas',
                    data: <?= json_encode($despesaData) ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Lucro Líquido',
                    data: <?= json_encode($lucroLiquidoData) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    <?php if (!empty($pagamentoData)): ?>
    const ctxPagamentos = document.getElementById('pagamentosChart').getContext('2d');
    const pagamentosChart = new Chart(ctxPagamentos, {
        type: 'pie',
        data: {
            labels: <?= json_encode($pagamentoLabels) ?>,
            datasets: [{
                label: 'Total R$',
                data: <?= json_encode($pagamentoData) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',  // Vermelho para Dinheiro/Pix
                    'rgba(54, 162, 235, 0.7)', // Azul para Débito
                    'rgba(255, 206, 86, 0.7)', // Amarelo para Crédito
                    'rgba(75, 192, 192, 0.7)', // Verde para outros
                    'rgba(153, 102, 255, 0.7)',// Roxo
                    'rgba(255, 159, 64, 0.7)'  // Laranja
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false,
                    text: 'Formas de Pagamento no Período'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                            }
                            return label;
                        },
                        footer: function(tooltipItems) {
                            let sum = tooltipItems[0].chart.getDatasetMeta(0).total;
                            let percentage = (tooltipItems[0].parsed * 100 / sum).toFixed(2) + '%';
                            return 'Porcentagem: ' + percentage;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once 'views/footer.php'; ?>
