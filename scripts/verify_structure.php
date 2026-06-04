<?php
/**
 * Script de Verificação de Arquitetura (Pós-Fase 2)
 * Valida a nova estrutura public/app, Autoloader e Front Controller.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO VALIDAÇÃO TÉCNICA DA ARQUITETURA ===\n\n";

$erros = 0;

// 1. Verificação de Diretórios e Isolamento
echo "[1/4] Verificando integridade dos diretórios...\n";
$diretorios = [
    'app/Controllers', 'app/Models', 'app/Services', 'app/Views',
    'public/assets/js', 'public/assets/css', 'public/uploads',
    'scripts', 'database'
];

foreach ($diretorios as $dir) {
    if (is_dir(__DIR__ . '/../' . $dir)) {
        echo "  OK: Diretório /$dir criado.\n";
    } else {
        echo "  ERRO: Diretório /$dir FALTANDO!\n";
        $erros++;
    }
}

// 2. Teste do Autoloader PSR-4
echo "\n[2/4] Testando Autoloader PSR-4...\n";
require_once __DIR__ . '/../app/autoload.php';

try {
    // Tenta instanciar a classe pré-existente criada para este fim
    if (class_exists('App\Models\ConfigTest')) {
        $testObj = new \App\Models\ConfigTest();
        if ($testObj->ping() === 'pong') {
            echo "  OK: Autoloader PSR-4 funcionando corretamente (Mapeamento App\\Models validado).\n";
        } else {
            echo "  ERRO: Autoloader carregou a classe App\\Models\\ConfigTest mas o método falhou.\n";
            $erros++;
        }
    } else {
        echo "  ERRO: Autoloader não encontrou a classe App\\Models\\ConfigTest. Verifique o mapeamento em app/autoload.php.\n";
        $erros++;
    }
} catch (Exception $e) {
    echo "  ERRO: Falha ao testar autoloader: " . $e->getMessage() . "\n";
    $erros++;
}

// 3. Verificação de Ativos e Máscaras
echo "\n[3/4] Verificando ativos centralizados...\n";
$assets = [
    'public/assets/js/mascaras.js',
    'public/assets/css/style.css',
    'public/assets/img/odontograma.png'
];

foreach ($assets as $asset) {
    if (file_exists(__DIR__ . '/../' . $asset)) {
        echo "  OK: Ativo /$asset localizado.\n";
    } else {
        echo "  ERRO: Ativo /$asset FALTANDO!\n";
        $erros++;
    }
}

// 4. Verificação de Configuração do Front Controller
echo "\n[4/4] Verificando Front Controller e Segurança...\n";
$frontFiles = ['public/index.php', 'public/.htaccess', 'Dockerfile'];
foreach ($frontFiles as $f) {
    if (file_exists(__DIR__ . '/../' . $f)) {
        echo "  OK: Arquivo /$f configurado.\n";
    } else {
        echo "  ERRO: Arquivo /$f FALTANDO!\n";
        $erros++;
    }
}

// Verificação de segurança: scripts de manutenção não devem estar na raiz (foram movidos)
if (!file_exists(__DIR__ . '/../setup.php')) {
    echo "  OK: setup.php removido da raiz (Segurança).\n";
} else {
    echo "  AVISO: setup.php ainda na raiz!\n";
}

echo "\n=== RESUMO FINAL ===\n";
if ($erros === 0) {
    echo "ESTADO: Arquitetura validada com sucesso. Pronto para a Fase 3.\n";
} else {
    echo "ESTADO: Foram encontrados $erros erro(s). Verifique os logs acima.\n";
}
?>
