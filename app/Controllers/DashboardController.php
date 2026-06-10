<?php

namespace App\Controllers;

use App\Models\Atendimento;
use App\Models\Despesa;
use PDO;

class DashboardController extends BaseController
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Exibe o Dashboard Principal.
     */
    public function index(): void
    {
        // Define o período (Mês selecionado ou atual)
        $mes_selecionado = $_GET['mes'] ?? date('Y-m');

        // Validação básica do formato YYYY-MM para segurança
        if (!preg_match('/^\d{4}-\d{2}$/', $mes_selecionado)) {
            $mes_selecionado = date('Y-m');
        }

        $data_inicio = date('Y-m-01', strtotime($mes_selecionado));
        $data_fim = date('Y-m-t', strtotime($mes_selecionado));

        // Navegação entre meses
        $mes_anterior = date('Y-m', strtotime($data_inicio . ' -1 month'));
        $mes_proximo = date('Y-m', strtotime($data_inicio . ' +1 month'));

        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);

        // Estatísticas do Topo (Mês Selecionado)
        $faturamentoBruto = $atendimentoModel->obterFaturamentoBrutoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $lucroLiquido = $atendimentoModel->obterFaturamentoLiquidoPeriodo($data_inicio . ' 00:00:00', $data_fim . ' 23:59:59');
        $totalDespesas = $despesaModel->obterTotalPeriodo($data_inicio, $data_fim);

        // Paginação e Busca de Atendimentos
        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($pagina < 1) $pagina = 1;
        $itensPorPagina = 10;
        $offset = ($pagina - 1) * $itensPorPagina;

        $totalRegistros = $atendimentoModel->obterContagemDashboard($busca);
        $totalPaginas = ceil($totalRegistros / $itensPorPagina);
        $ultimosAtendimentos = $atendimentoModel->listarDashboard($busca, $itensPorPagina, $offset);

        // Formata o nome do mês em português
        $formatter = new \IntlDateFormatter(
            'pt_BR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            \IntlDateFormatter::GREGORIAN,
            'MMMM \'de\' yyyy'
        );
        $mesAtual = $formatter->format(strtotime($data_inicio));

        $this->render('dashboard', [
            'mes_selecionado' => $mes_selecionado,
            'mes_anterior' => $mes_anterior,
            'mes_proximo' => $mes_proximo,
            'faturamentoBruto' => $faturamentoBruto,
            'lucroLiquido' => $lucroLiquido,
            'totalDespesas' => $totalDespesas,
            'busca' => $busca,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'ultimosAtendimentos' => $ultimosAtendimentos,
            'mesAtual' => $mesAtual
        ]);
    }
}
