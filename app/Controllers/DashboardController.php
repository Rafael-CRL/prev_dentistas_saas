<?php

namespace App\Controllers;

use App\Models\Config;
use App\Services\FinanceiroService;
use PDO;

class DashboardController extends BaseController
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
        $this->financeiroService = new FinanceiroService($config, $pdo, $clinica_id);
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

        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($pagina < 1) {
            $pagina = 1;
        }
        $itensPorPagina = 10;

        // Delega o processamento ao FinanceiroService
        $dadosDashboard = $this->financeiroService->obterDadosDashboard($mes_selecionado, $busca, $pagina, $itensPorPagina);

        $this->render('dashboard', $dadosDashboard);
    }

    /**
     * Retorna estatísticas financeiras formatadas em JSON para gráficos e métricas.
     */
    public function apiStats(): void
    {
        $mes_selecionado = $_GET['mes'] ?? date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $mes_selecionado)) {
            $mes_selecionado = date('Y-m');
        }

        try {
            $stats = $this->financeiroService->obterEstatisticasGraficos($mes_selecionado);
            $this->json(['sucesso' => true, 'dados' => $stats]);
        } catch (\Exception $e) {
            error_log("Erro ao obter estatísticas do dashboard: " . $e->getMessage());
            $this->json(['sucesso' => false, 'erro' => 'Ocorreu um erro interno ao carregar estatísticas.'], 500);
        }
    }
}
