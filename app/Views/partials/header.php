<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/controle_acesso.php';

function isActiveTab(string $tab): bool {
    $v = $GLOBALS['current_view'] ?? '';

    if (empty($v)) {
        // Fallback robusto baseado no URI caso a view não esteja disponível (ex: em telas de erro)
        $uri = $_SERVER['REQUEST_URI'];
        switch ($tab) {
            case 'dashboard':
                return strpos($uri, 'index.php') !== false || $uri === '/' || substr($uri, -1) === '/';
            case 'novo_atendimento':
                return strpos($uri, 'atendimentos/cadastrar') !== false || strpos($uri, 'financeiro/pagar') !== false;
            case 'cadastros':
                return (
                    (strpos($uri, 'pacientes') !== false && strpos($uri, 'pacientes/relatorio') === false) ||
                    strpos($uri, 'procedimentos') !== false ||
                    strpos($uri, 'despesas') !== false ||
                    (strpos($uri, 'usuarios') !== false && strpos($uri, 'usuarios/configuracoes') === false)
                );
            case 'relatorios':
                return strpos($uri, 'relatorios') !== false || strpos($uri, 'pacientes/relatorio') !== false;
            case 'configuracoes':
                return strpos($uri, 'clinica/painel') !== false || strpos($uri, 'usuarios/configuracoes') !== false;
            default:
                return false;
        }
    }

    // Identificação 100% precisa baseada no arquivo de View que está sendo renderizado pelo Controller
    switch ($tab) {
        case 'dashboard':
            return $v === 'dashboard';
        case 'novo_atendimento':
            return $v === 'atendimentos/cadastrar' || $v === 'financeiro/pagar';
        case 'cadastros':
            return (
                ($v === 'pacientes/index' || strpos($v, 'pacientes/') === 0) && $v !== 'pacientes/relatorio'
            ) || (
                $v === 'procedimentos/index' || strpos($v, 'procedimentos/') === 0
            ) || (
                $v === 'financeiro/despesas' || strpos($v, 'financeiro/despesas/') === 0
            ) || (
                ($v === 'usuarios/index' || strpos($v, 'usuarios/') === 0) && $v !== 'usuarios/configuracoes'
            );
        case 'relatorios':
            return (
                $v === 'pacientes/relatorio' || 
                $v === 'financeiro/relatorio_procedimentos' || 
                strpos($v, 'financeiro/relatorio_') === 0 ||
                strpos($v, 'relatorios/') === 0
            );
        case 'configuracoes':
            return $v === 'clinica/painel' || $v === 'usuarios/configuracoes';
        default:
            return false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($_SESSION['clinica_nome']) ? htmlspecialchars($_SESSION['clinica_nome']) : 'Clínica Prev Dentistas' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= BASE_URL ?>assets/js/mascaras.js"></script>
    <script>
        // Executado imediatamente para evitar flash de fundo claro em modo escuro
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
    <header class="navbar">
        <div class="logo" translate="no">
            <a href="<?= BASE_URL ?>index.php" style="text-decoration:none; color:inherit;">
                🦷 <?= isset($_SESSION['clinica_nome']) ? htmlspecialchars($_SESSION['clinica_nome']) : 'Prev Dentistas' ?>
            </a>
        </div>
        
        <?php if(isset($_SESSION['usuario_id'])): ?>
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <?php endif; ?>
        
        <nav class="menu" id="navbar-menu">
            <?php if(isset($_SESSION['usuario_id'])): ?>
                <a href="<?= BASE_URL ?>index.php" class="<?= isActiveTab('dashboard') ? 'active' : '' ?>">Dashboard</a>
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActiveTab('novo_atendimento') ? 'active' : '' ?>">
                        Novo Atendimento <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>atendimentos/cadastrar">Lançar/Executar Procedimento</a>
                        <a href="<?= BASE_URL ?>financeiro/pagar">Confirmar Pagamento</a>
                    </div>
                </div>
                
                <?php if (is_admin() || is_dentista() ||is_recepcionista()): ?>
                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActiveTab('cadastros') ? 'active' : '' ?>">
                        Cadastros <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>pacientes">Pacientes</a>
                        <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>procedimentos">Procedimentos</a>
                        <a href="<?= BASE_URL ?>financeiro/despesas">Despesas</a>
                        <a href="<?= BASE_URL ?>usuarios">Usuários</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActiveTab('relatorios') ? 'active' : '' ?>">
                        Relatórios <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?= BASE_URL ?>financeiro/relatorios/diario">Diário</a>
                        <?php if (is_admin() || is_dentista()): ?>
                        <a href="<?= BASE_URL ?>financeiro/relatorios/dentistas">Por Dentista</a>
                        <a href="<?= BASE_URL ?>pacientes/relatorio">Por Paciente</a>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                        <a href="<?= BASE_URL ?>financeiro/relatorios/geral">Financeiro Geral</a>
                        <a href="<?= BASE_URL ?>financeiro/relatorios/procedimentos">Por Procedimentos</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="javascript:void(0)" class="<?= isActiveTab('configuracoes') ? 'active' : '' ?>">
                        Configurações <small>▾</small>
                    </a>
                    <div class="dropdown-content">
                        <?php if (is_admin()): ?>
                            <a href="<?= BASE_URL ?>clinica/painel">Clínica</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>usuarios/configuracoes">Meu Perfil</a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <?php if(isset($_SESSION['usuario_id'])): ?>
            <div class="user-menu">
                <button id="theme-toggle" class="btn-theme-toggle" aria-label="Alternar Tema" style="background: none; border: none; color: inherit; font-size: 1.2rem; cursor: pointer; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; outline: none; margin-right: 8px;">
                    <i class="fa fa-moon-o"></i>
                </button>
                <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                <a href="<?= BASE_URL ?>logout" class="btn btn-secondary" translate="no">Sair</a>
            </div>
        <?php endif; ?>
    </header>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica do Menu Mobile
            const menuToggle = document.getElementById('mobile-menu');
            const navMenu = document.getElementById('navbar-menu');
            
            if (menuToggle && navMenu) {
                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                });
            }

            document.querySelectorAll('.dropdown').forEach(function(dropdown) {
                const dropdownToggle = dropdown.querySelector('a');
                dropdownToggle.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        event.preventDefault();
                        const content = dropdown.querySelector('.dropdown-content');
                        const isVisible = content.style.display === 'block';
                        
                        // Fecha outros
                        document.querySelectorAll('.dropdown-content').forEach(c => c.style.display = 'none');
                        content.style.display = isVisible ? 'none' : 'block';
                    }
                });
            });

            // Lógica de Alternar Tema (Dark Mode)
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle ? themeToggle.querySelector('i') : null;
            
            function updateThemeIcon(theme) {
                if (!themeIcon) return;
                if (theme === 'dark') {
                    themeIcon.className = 'fa fa-sun-o';
                } else {
                    themeIcon.className = 'fa fa-moon-o';
                }
            }
            
            // Define o ícone inicial com base no tema ativo
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            updateThemeIcon(currentTheme);
            
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    let newTheme = 'light';
                    
                    if (activeTheme === 'light') {
                        newTheme = 'dark';
                        document.documentElement.setAttribute('data-theme', 'dark');
                    } else {
                        document.documentElement.removeAttribute('data-theme');
                    }
                    
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon(newTheme);
                    
                    // Atualiza gráficos do Chart.js dinamicamente se o usuário estiver no Dashboard
                    if (typeof Chart !== 'undefined' && window.myCharts) {
                        const isDark = newTheme === 'dark';
                        const textColor = isDark ? '#cbd5e1' : '#666';
                        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
                        
                        Chart.defaults.color = textColor;
                        Chart.defaults.borderColor = gridColor;
                        
                        Object.values(window.myCharts).forEach(chart => {
                            if (chart) {
                                if (chart.options.scales) {
                                    if (chart.options.scales.x) {
                                        chart.options.scales.x.grid = chart.options.scales.x.grid || {};
                                        chart.options.scales.x.grid.color = gridColor;
                                        chart.options.scales.x.ticks = chart.options.scales.x.ticks || {};
                                        chart.options.scales.x.ticks.color = textColor;
                                    }
                                    if (chart.options.scales.y) {
                                        chart.options.scales.y.grid = chart.options.scales.y.grid || {};
                                        chart.options.scales.y.grid.color = gridColor;
                                        chart.options.scales.y.ticks = chart.options.scales.y.ticks || {};
                                        chart.options.scales.y.ticks.color = textColor;
                                    }
                                }
                                if (chart.options.plugins && chart.options.plugins.legend) {
                                    chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                                    chart.options.plugins.legend.labels.color = textColor;
                                }
                                chart.update();
                            }
                        });
                    }
                });
            }
        });
    </script>
    <main class="container">    