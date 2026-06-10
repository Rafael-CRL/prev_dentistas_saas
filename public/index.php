<?php
/**
 * Front Controller
 * Ponto de entrada único da aplicação
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../config/app.php';

// Ajusta o include_path para que os requires legados continuem funcionando
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/../'));

require_once 'config/session.php';
require_once 'config/database.php';

// Normalização da URI para roteamento
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}
$uri = ltrim($uri, '/');
if (empty($uri)) { $uri = 'index.php'; }

// --- ROTEAMENTO MVC ---

// 0. Módulo de Dashboard
if ($uri === 'index.php' || $uri === 'index') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $controller = new \App\Controllers\DashboardController($pdo, (int)$_SESSION['clinica_id']);
    $controller->index();
    exit;
}

// 1. Módulo de Autenticação
if ($uri === 'login.php' || $uri === 'login' || $uri === 'logout.php' || $uri === 'actions/login_handler.php') {
    $controller = new \App\Controllers\AuthController($pdo);
    if (strpos($uri, 'logout') !== false) {
        $controller->logout();
    } elseif ($uri === 'actions/login_handler.php') {
        $controller->login();
    } else {
        $controller->showLogin();
    }
    exit;
}

// 2. Módulo de Pacientes
if (strpos($uri, 'pacientes') === 0 || $uri === 'editar_paciente.php') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $controller = new \App\Controllers\PacienteController($pdo, (int)$_SESSION['clinica_id']);
    
    if ($uri === 'pacientes' || $uri === 'pacientes.php') {
        $controller->index();
    } elseif ($uri === 'pacientes/editar' || $uri === 'editar_paciente.php') {
        $id = $_GET['id'] ?? null;
        $controller->editar($id);
    } elseif ($uri === 'pacientes/salvar' || $uri === 'actions/salvar_paciente.php') {
        $controller->salvar();
    } elseif ($uri === 'pacientes/excluir' || $uri === 'actions/excluir_paciente.php') {
        $id = $_GET['id'] ?? null;
        $controller->excluir($id);
    } elseif ($uri === 'pacientes/buscar' || $uri === 'actions/buscar_paciente.php') {
        $controller->apiBuscar();
    } elseif ($uri === 'pacientes/historico' || $uri === 'actions/buscar_historico_paciente.php') {
        $controller->apiHistorico();
    } elseif ($uri === 'pacientes/pendentes' || $uri === 'actions/buscar_procedimentos_pendentes.php') {
        $controller->apiPendentes();
    }
    exit;
}

// 3. Módulo de Procedimentos
if (strpos($uri, 'procedimentos') === 0) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $controller = new \App\Controllers\ProcedimentoController($pdo, (int)$_SESSION['clinica_id']);

    if ($uri === 'procedimentos' || $uri === 'procedimentos.php') {
        $controller->index();
    } elseif ($uri === 'procedimentos/salvar') {
        $controller->salvar();
    } elseif ($uri === 'procedimentos/excluir') {
        $controller->excluir();
    }
    exit;
}

// 4. Módulo de Atendimentos
if (strpos($uri, 'atendimentos') === 0) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $controller = new \App\Controllers\AtendimentoController($pdo, (int)$_SESSION['clinica_id']);

    if ($uri === 'atendimentos/cadastrar' || $uri === 'views/novo_atendimento.php') {
        $controller->cadastrar();
    } elseif ($uri === 'atendimentos/salvar' || $uri === 'actions/salvar_atendimento.php') {
        $controller->salvar();
    } elseif ($uri === 'atendimentos/verificar-pagamento' || $uri === 'actions/verificar_pagamento_pendente.php') {
        $controller->verificarPagamentoPendente();
    }
    exit;
}

// 5. Módulo Financeiro
if (strpos($uri, 'financeiro') === 0 || in_array($uri, ['relatorios.php', 'relatorio_diario.php', 'relatorio_dentistas.php', 'relatorio_procedimentos.php'])) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    $controller = new \App\Controllers\FinanceiroController($pdo, (int)$_SESSION['clinica_id']);

    if ($uri === 'financeiro/pagar' || $uri === 'views/confirmar_pagamento.php') {
        $controller->showPagar();
    } elseif ($uri === 'financeiro/salvar-pagamento' || $uri === 'actions/salvar_pagamento.php') {
        $controller->salvarPagamento();
    } elseif ($uri === 'financeiro/despesas' || $uri === 'despesas.php') {
        $controller->despesas();
    } elseif ($uri === 'financeiro/despesas/salvar' || $uri === 'actions/salvar_despesa.php') {
        $controller->salvarDespesa();
    } elseif ($uri === 'financeiro/despesas/excluir' || $uri === 'actions/excluir_despesa.php') {
        $controller->excluirDespesa();
    } elseif ($uri === 'financeiro/relatorios/geral' || $uri === 'relatorios.php') {
        $controller->relatorioGeral();
    } elseif ($uri === 'financeiro/relatorios/diario' || $uri === 'relatorio_diario.php') {
        $controller->relatorioDiario();
    } elseif ($uri === 'financeiro/relatorios/dentistas' || $uri === 'relatorio_dentistas.php') {
        $controller->relatorioDentistas();
    } elseif ($uri === 'financeiro/relatorios/procedimentos' || $uri === 'relatorio_procedimentos.php') {
        $controller->relatorioProcedimentos();
    }
    exit;
}

// --- COMPATIBILIDADE LEGADA ---

$legacy_file_path = realpath(__DIR__ . '/../' . ltrim($uri, '/'));

if ($legacy_file_path && is_file($legacy_file_path) && strpos($legacy_file_path, realpath(__DIR__ . '/../')) === 0) {
    $extension = pathinfo($legacy_file_path, PATHINFO_EXTENSION);
    $static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf'];
    
    if (in_array(strtolower($extension), $static_extensions)) {
        return false; 
    }
    
    require_once $legacy_file_path;
    exit;
}

// Erro 404
http_response_code(404);
echo "Erro 404: Página não encontrada no sistema MVC/Legado.";
