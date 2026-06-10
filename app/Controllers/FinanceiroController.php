<?php

namespace App\Controllers;

use App\Models\Pagamento;
use App\Models\Despesa;
use App\Models\Atendimento;
use App\Models\Paciente;
use App\Models\Config;
use App\Services\FinanceiroService;
use PDO;

class FinanceiroController extends BaseController
{
    private PDO $pdo;
    private int $clinica_id;
    private FinanceiroService $financeiroService;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
        
        $config = Config::getInstance($pdo, $clinica_id);
        $this->financeiroService = new FinanceiroService($config);
    }

    /**
     * Exibe a tela de confirmação de pagamento.
     */
    public function showPagar(): void
    {
        $paciente_id = $_GET['paciente_id'] ?? null;
        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        $pacienteModel = new Paciente($this->pdo, $this->clinica_id);

        $data = [
            'paciente_id' => $paciente_id,
            'paciente' => null,
            'atendimentos' => [],
            'valor_total' => 0,
            'ultimo_atendimento_id' => null
        ];

        if ($paciente_id) {
            $data['paciente'] = $pacienteModel->getById($paciente_id);
            // Reutiliza lógica de buscar último atendimento pendente
            $data['ultimo_atendimento_id'] = $atendimentoModel->buscarUltimoPendente($paciente_id);

            if ($data['ultimo_atendimento_id']) {
                $procedimentos = $atendimentoModel->buscarProcedimentosFinalizados($data['ultimo_atendimento_id']);
                $data['atendimentos'] = $procedimentos;
                
                foreach ($procedimentos as $proc) {
                    $data['valor_total'] += $proc['valor_procedimento'];
                }
            }
        }

        $this->render('financeiro/pagar', $data);
    }

    /**
     * Processa a confirmação do pagamento via POST.
     */
    public function salvarPagamento(): void
    {
        $atendimento_id = $_POST['atendimento_id'] ?? null;
        $pagamentos = $_POST['pagamentos'] ?? null;

        if (!$atendimento_id || !$pagamentos) {
            $this->json(['sucesso' => false, 'erro' => 'Dados incompletos.'], 400);
        }

        try {
            $this->pdo->beginTransaction();

            $pagamentoModel = new Pagamento($this->pdo, $this->clinica_id);
            $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
            
            $pagamentoModel->confirmarPagamentoCompleto(
                (int)$atendimento_id,
                $pagamentos,
                $this->financeiroService,
                $atendimentoModel
            );

            $this->pdo->commit();
            $this->json(['sucesso' => true, 'mensagem' => 'Pagamento confirmado com sucesso!']);

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro ao confirmar pagamento (ID Atendimento: {$atendimento_id}): " . $e->getMessage());
            $this->json(['sucesso' => false, 'erro' => 'Ocorreu um erro interno ao processar o pagamento.'], 500);
        }
    }

    /**
     * Gestão de Despesas.
     */
    public function despesas(): void
    {
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);
        $this->render('financeiro/despesas', [
            'despesas' => $despesaModel->listarTodas()
        ]);
    }

    public function salvarDespesa(): void
    {
        try {
            $despesaModel = new Despesa($this->pdo, $this->clinica_id);
            $sucesso = $despesaModel->salvar($_POST);

            if ($sucesso) {
                $_SESSION['feedback'] = "Despesa salva com sucesso!";
            } else {
                $_SESSION['feedback_erro'] = "Erro ao salvar despesa.";
            }
        } catch (\Exception $e) {
            error_log("Erro ao salvar despesa: " . $e->getMessage());
            $_SESSION['feedback_erro'] = "Ocorreu um erro interno ao salvar a despesa.";
        }

        header("Location: " . BASE_URL . "financeiro/despesas");
        exit;
    }

    public function excluirDespesa(): void
    {
        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $despesaModel = new Despesa($this->pdo, $this->clinica_id);
                $despesaModel->excluir((int)$id);
                $_SESSION['feedback'] = "Despesa excluída com sucesso!";
            } catch (\Exception $e) {
                error_log("Erro ao excluir despesa (ID: {$id}): " . $e->getMessage());
                $_SESSION['feedback_erro'] = "Ocorreu um erro interno ao excluir a despesa.";
            }
        }
        header("Location: " . BASE_URL . "financeiro/despesas");
        exit;
    }

    /**
     * Relatório Financeiro Geral (MVC/SaaS)
     */
    public function relatorioGeral(): void
    {
        if (!is_admin()) {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }

        $data_inicio = $_GET['inicio'] ?? date('Y-m-01');
        $data_fim = $_GET['fim'] ?? date('Y-m-t');

        $itensPorPagina = 10;

        // Paginação Atendimentos
        $pagina_at = isset($_GET['pagina_at']) ? (int)$_GET['pagina_at'] : 1;
        if ($pagina_at < 1) $pagina_at = 1;
        $offset_at = ($pagina_at - 1) * $itensPorPagina;

        // Paginação Despesas
        $pagina_de = isset($_GET['pagina_de']) ? (int)$_GET['pagina_de'] : 1;
        if ($pagina_de < 1) $pagina_de = 1;
        $offset_de = ($pagina_de - 1) * $itensPorPagina;

        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);

        $bruto = $atendimentoModel->obterFaturamentoBrutoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $liquido = $atendimentoModel->obterFaturamentoLiquidoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $totalDespesas = $despesaModel->obterTotalPeriodo($data_inicio, $data_fim);

        // Paginação Atendimentos
        $totalRegistrosAtendimentos = $atendimentoModel->obterContagemPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $totalPaginasAtendimentos = ceil($totalRegistrosAtendimentos / $itensPorPagina);
        $atendimentos = $atendimentoModel->listarPeriodoPaginado($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59', $itensPorPagina, $offset_at);

        // Paginação Despesas
        $totalRegistrosDespesas = $despesaModel->obterContagemPeriodo($data_inicio, $data_fim);
        $totalPaginasDespesas = ceil($totalRegistrosDespesas / $itensPorPagina);
        $despesas = $despesaModel->listarPeriodoPaginado($data_inicio, $data_fim, $itensPorPagina, $offset_de);

        // Gráficos
        $rawDadosGrafico = $atendimentoModel->obterDadosGraficoFaturamentoEDespesas($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $dadosGrafico = [];
        foreach ($rawDadosGrafico as $row) {
            $dadosGrafico[$row['dia']] = $row;
        }

        $rawDadosLiquidoGrafico = $atendimentoModel->obterDadosGraficoLiquido($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $dadosLiquidoGrafico = [];
        foreach ($rawDadosLiquidoGrafico as $row) {
            $dadosLiquidoGrafico[$row['dia']] = $row;
        }

        // Preparar dados para o Chart.js
        $labels = [];
        $faturamentoData = [];
        $despesaData = [];
        $lucroLiquidoData = [];

        $begin = new \DateTime($data_inicio);
        $end = new \DateTime($data_fim);
        $end->setTime(23, 59, 59);

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);

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

        // Formas de pagamento
        $dadosPagamentos = $atendimentoModel->obterDadosGraficoPagamentos($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $pagamentoLabels = [];
        $pagamentoData = [];
        if ($dadosPagamentos) {
            $pagamentoLabels = array_keys($dadosPagamentos);
            $pagamentoData = array_values($dadosPagamentos);
            $pagamentoLabels = array_map('ucfirst', $pagamentoLabels);
        }

        $this->render('financeiro/relatorio_geral', [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'bruto' => $bruto,
            'liquido' => $liquido,
            'total_despesas' => $totalDespesas,
            'atendimentos' => $atendimentos,
            'despesas' => $despesas,
            'totalPaginasAtendimentos' => $totalPaginasAtendimentos,
            'totalPaginasDespesas' => $totalPaginasDespesas,
            'pagina_at' => $pagina_at,
            'pagina_de' => $pagina_de,
            'labels' => $labels,
            'faturamentoData' => $faturamentoData,
            'despesaData' => $despesaData,
            'lucroLiquidoData' => $lucroLiquidoData,
            'pagamentoLabels' => $pagamentoLabels,
            'pagamentoData' => $pagamentoData
        ]);
    }

    /**
     * Relatório do Dia (MVC/SaaS)
     */
    public function relatorioDiario(): void
    {
        $data_selecionada = $_GET['data'] ?? date('Y-m-d');

        $data_obj = new \DateTime($data_selecionada);
        $data_anterior = (clone $data_obj)->modify('-1 day')->format('Y-m-d');
        $data_posterior = (clone $data_obj)->modify('+1 day')->format('Y-m-d');

        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);

        $faturamento_bruto = $atendimentoModel->obterFaturamentoBrutoDiario($data_selecionada);
        $total_taxas = $atendimentoModel->obterTaxasCartaoDiario($data_selecionada);
        $total_custo_auxiliar = $atendimentoModel->obterCustoAuxiliarDiario($data_selecionada);
        $pagamento_dentistas = $atendimentoModel->obterComissoesDentistasDiario($data_selecionada);
        $total_comissoes = array_sum(array_column($pagamento_dentistas, 'total_comissao'));

        $despesas_dia = $despesaModel->listarPorData($data_selecionada);
        $total_despesas = array_sum(array_column($despesas_dia, 'valor'));

        $lucro_liquido = $faturamento_bruto - $total_taxas - $total_comissoes - $total_despesas - $total_custo_auxiliar;

        $this->render('financeiro/relatorio_diario', [
            'data_selecionada' => $data_selecionada,
            'data_anterior' => $data_anterior,
            'data_posterior' => $data_posterior,
            'faturamento_bruto' => $faturamento_bruto,
            'total_taxas' => $total_taxas,
            'total_custo_auxiliar' => $total_custo_auxiliar,
            'pagamento_dentistas' => $pagamento_dentistas,
            'total_comissoes' => $total_comissoes,
            'despesas_dia' => $despesas_dia,
            'total_despesas' => $total_despesas,
            'lucro_liquido' => $lucro_liquido
        ]);
    }

    /**
     * Relatório por Dentista (MVC/SaaS)
     */
    public function relatorioDentistas(): void
    {
        if (!is_admin() && !is_dentista()) {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }

        $mes = $_GET['mes'] ?? date('Y-m');
        
        if (is_dentista() && !is_admin()) {
            $dentista_id = $_SESSION['usuario_id'];
        } else {
            $dentista_id = $_GET['dentista_id'] ?? 'todos';
        }

        $data_inicio = date('Y-m-01 00:00:00', strtotime($mes));
        $data_fim = date('Y-m-t 23:59:59', strtotime($mes));

        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        
        $dentistas = [];
        if (is_admin()) {
            $stmt = $this->pdo->prepare("SELECT id, nome FROM usuarios WHERE perfil = 'dentista' AND clinica_id = ? ORDER BY nome");
            $stmt->execute([$this->clinica_id]);
            $dentistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $relatorio_dentistas = $atendimentoModel->obterFaturamentoPorDentistaPeriodo($data_inicio, $data_fim, $dentista_id);

        $this->render('financeiro/relatorio_dentistas', [
            'mes' => $mes,
            'dentista_id' => $dentista_id,
            'dentistas' => $dentistas,
            'relatorio_dentistas' => $relatorio_dentistas
        ]);
    }

    /**
     * Relatório por Procedimentos (MVC/SaaS)
     */
    public function relatorioProcedimentos(): void
    {
        if (!is_admin()) {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }

        $data_inicio = $_GET['inicio'] ?? date('Y-m-01');
        $data_fim = $_GET['fim'] ?? date('Y-m-t');

        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);

        $totalProcedimentos = $atendimentoModel->obterTotalProcedimentosQuantidadePeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $procedimentos_relatorio = $atendimentoModel->obterRelatorioProcedimentosPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');

        $this->render('financeiro/relatorio_procedimentos', [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'totalProcedimentos' => $totalProcedimentos,
            'procedimentos_relatorio' => $procedimentos_relatorio
        ]);
    }
}
