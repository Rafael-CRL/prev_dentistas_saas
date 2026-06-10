<?php
/**
 * scripts/test_financeiro_db.php
 * Teste completo de integração do Banco de Dados via Docker
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

use App\Models\Atendimento;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Services\FinanceiroService;
use App\Models\Config;

echo "INICIANDO TESTE DE INTEGRAÇÃO DO BANCO DE DADOS (VIA DOCKER)...\n";
echo "Host: " . ($host ?? 'localhost') . "\n";
echo "Banco: " . ($db_name ?? '') . "\n\n";

try {
    $clinica_id = 1; // Usando clínica padrão para teste

    echo "1. Instanciando modelos...\n";
    $atendimentoModel = new Atendimento($pdo, $clinica_id);
    $despesaModel = new Despesa($pdo, $clinica_id);
    $pagamentoModel = new Pagamento($pdo, $clinica_id);
    $config = Config::getInstance($pdo, $clinica_id);
    $financeiroService = new FinanceiroService($config);
    echo "   -> Modelos instanciados com sucesso.\n\n";

    echo "2. Testando consultas do relatório geral...\n";
    $data_inicio = date('Y-m-01');
    $data_fim = date('Y-m-t');
    
    $bruto = $atendimentoModel->obterFaturamentoBrutoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $liquido = $atendimentoModel->obterFaturamentoLiquidoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $totalDespesas = $despesaModel->obterTotalPeriodo($data_inicio, $data_fim);
    echo "   -> Faturamento bruto no período: R$ " . number_format($bruto, 2, ',', '.') . "\n";
    echo "   -> Faturamento líquido no período: R$ " . number_format($liquido, 2, ',', '.') . "\n";
    echo "   -> Despesas no período: R$ " . number_format($totalDespesas, 2, ',', '.') . "\n\n";

    echo "3. Testando consultas do gráfico...\n";
    $grafico1 = $atendimentoModel->obterDadosGraficoFaturamentoEDespesas($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $grafico2 = $atendimentoModel->obterDadosGraficoLiquido($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    $grafico3 = $atendimentoModel->obterDadosGraficoPagamentos($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
    echo "   -> Gráfico faturamento/despesas: " . count($grafico1) . " registros.\n";
    echo "   -> Gráfico líquido: " . count($grafico2) . " registros.\n";
    echo "   -> Gráfico formas de pagamento: " . count($grafico3) . " registros.\n\n";

    echo "4. Testando consultas de relatório diário...\n";
    $hoje = date('Y-m-d');
    $brutoDia = $atendimentoModel->obterFaturamentoBrutoDiario($hoje);
    $taxasDia = $atendimentoModel->obterTaxasCartaoDiario($hoje);
    $comissoesDia = $atendimentoModel->obterComissoesDentistasDiario($hoje);
    echo "   -> Faturamento do dia: R$ " . number_format($brutoDia, 2, ',', '.') . "\n";
    echo "   -> Taxas de cartão do dia: R$ " . number_format($taxasDia, 2, ',', '.') . "\n";
    echo "   -> Comissões calculadas no dia: " . count($comissoesDia) . " dentistas.\n\n";

    echo "5. Testando relatório por dentista...\n";
    $data_inicio_mes = date('Y-m-01 00:00:00');
    $data_fim_mes = date('Y-m-t 23:59:59');
    $relDentistas = $atendimentoModel->obterFaturamentoPorDentistaPeriodo($data_inicio_mes, $data_fim_mes, 'todos');
    echo "   -> Desempenho por dentista: " . count($relDentistas) . " registros.\n\n";

    echo "6. Testando relatório por procedimentos...\n";
    $totalProcs = $atendimentoModel->obterTotalProcedimentosQuantidadePeriodo($data_inicio_mes, $data_fim_mes);
    $relProcs = $atendimentoModel->obterRelatorioProcedimentosPeriodo($data_inicio_mes, $data_fim_mes);
    echo "   -> Total de procedimentos executados: " . $totalProcs . "\n";
    echo "   -> Relatório agrupado de procedimentos: " . count($relProcs) . " registros.\n\n";

    echo "💎 SUCESSO ABSOLUTO: Todas as queries de integração executaram com sucesso no banco de dados e os modelos estão totalmente operacionais!\n";
    exit(0);

} catch (Exception $e) {
    echo "❌ FALHA DE INTEGRAÇÃO: " . $e->getMessage() . "\n";
    exit(1);
}
