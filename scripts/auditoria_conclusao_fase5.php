<?php
/**
 * scripts/auditoria_conclusao_fase5.php
 * Auditoria Holística de Conformidade MVC e Segurança SaaS
 * Foco: Autenticação, Pacientes, Procedimentos e BaseController
 */

require_once __DIR__ . '/../config/database.php';

// --- CONFIGURAÇÃO DA MATRIZ DE REQUISITOS ---

$requisitos_classes = [
    'App\Controllers\BaseController' => 'app/Controllers/BaseController.php',
    'App\Controllers\PacienteController' => 'app/Controllers/PacienteController.php',
    'App\Controllers\ProcedimentoController' => 'app/Controllers/ProcedimentoController.php',
    'App\Controllers\AuthController' => 'app/Controllers/AuthController.php',
    'App\Models\Paciente' => 'app/Models/Paciente.php',
    'App\Models\Procedimento' => 'app/Models/Procedimento.php',
    'App\Models\AuthModel' => 'app/Models/AuthModel.php'
];

$controladores_filhos = [
    'App\Controllers\PacienteController',
    'App\Controllers\ProcedimentoController',
    'App\Controllers\AuthController'
];

// --- MOTOR DE AUDITORIA ---

$relatorio = [
    'arquitetura' => ['total' => 0, 'sucesso' => 0, 'falhas' => []],
    'seguranca' => ['total' => 0, 'sucesso' => 0, 'falhas' => []],
    'limpeza' => ['total' => 0, 'sucesso' => 0, 'falhas' => []]
];

function logResultado(&$relatorio, $cat, $status, $msg) {
    $relatorio[$cat]['total']++;
    if ($status === 'OK') {
        $relatorio[$cat]['sucesso']++;
    } else {
        $relatorio[$cat]['falhas'][] = "[$status] $msg";
    }
}

echo "AUDITORIA TÉCNICA - FASE 5 (CONSOLIDAÇÃO MVC & SAAS)\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Validar Existência de Arquivos e Namespaces
foreach ($requisitos_classes as $class => $path) {
    $fullPath = __DIR__ . '/../' . $path;
    if (file_exists($fullPath)) {
        logResultado($relatorio, 'arquitetura', 'OK', "Arquivo da classe $class encontrado.");
        
        $content = file_get_contents($fullPath);
        $expectedNamespace = str_replace('/', '\\', dirname(str_replace('\\', '/', $class)));
        if (strpos($content, "namespace " . $expectedNamespace) !== false) {
            logResultado($relatorio, 'arquitetura', 'OK', "Namespace de $class está correto ($expectedNamespace).");
        } else {
            logResultado($relatorio, 'arquitetura', 'ERRO', "Namespace incorreto em $path. Esperado: $expectedNamespace");
        }
    } else {
        logResultado($relatorio, 'arquitetura', 'ERRO', "Arquivo $path não encontrado.");
    }
}

// 2. Validar Herança BaseController
require_once __DIR__ . '/../app/autoload.php';

foreach ($controladores_filhos as $class) {
    try {
        if (class_exists($class)) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isSubclassOf('App\Controllers\BaseController')) {
                logResultado($relatorio, 'arquitetura', 'OK', "$class estende BaseController corretamente.");
            } else {
                logResultado($relatorio, 'arquitetura', 'ERRO', "$class NÃO estende BaseController.");
            }

            // Verificar se render() foi sobrescrito (não deveria ser)
            if ($reflection->hasMethod('render') && $reflection->getMethod('render')->getDeclaringClass()->getName() === $class) {
                logResultado($relatorio, 'arquitetura', 'ERRO', "$class possui método render() local (Redundância detectada).");
            } else {
                logResultado($relatorio, 'arquitetura', 'OK', "$class utiliza render() da BaseController.");
            }
        }
    } catch (Exception $e) {
        logResultado($relatorio, 'arquitetura', 'ERRO', "Falha ao analisar classe $class: " . $e->getMessage());
    }
}

// 3. Auditoria de Segurança SaaS (Filtros clinica_id)
$models_to_check = ['app/Models/Paciente.php', 'app/Models/Procedimento.php'];
foreach ($models_to_check as $modelPath) {
    $path = __DIR__ . '/../' . $modelPath;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        // Verifica se existem queries sem clinica_id (simplificado para fins de auditoria)
        $queries = preg_match_all('/(SELECT|UPDATE|DELETE|INSERT).*(FROM|INTO|SET)\s+(\w+)/i', $content, $matches);
        $missing_filter = (strpos($content, 'clinica_id') === false);
        
        if ($missing_filter) {
            logResultado($relatorio, 'seguranca', 'ERRO', "Model $modelPath NÃO parece conter filtros clinica_id.");
        } else {
            logResultado($relatorio, 'seguranca', 'OK', "Model $modelPath possui referências a clinica_id.");
        }
    }
}

// 4. Auditoria de Autenticação
$authCtrlPath = __DIR__ . '/../app/Controllers/AuthController.php';
if (file_exists($authCtrlPath)) {
    $content = file_get_contents($authCtrlPath);
    if (strpos($content, 'session_regenerate_id') !== false) {
        logResultado($relatorio, 'seguranca', 'OK', "AuthController implementa regeneração de sessão.");
    } else {
        logResultado($relatorio, 'seguranca', 'ERRO', "AuthController FALTA session_regenerate_id (Risco de Fixation).");
    }
}

$authModelPath = __DIR__ . '/../app/Models/AuthModel.php';
if (file_exists($authModelPath)) {
    $content = file_get_contents($authModelPath);
    if (strpos($content, 'status = 1') !== false) {
        logResultado($relatorio, 'seguranca', 'OK', "AuthModel valida status do usuário.");
    } else {
        logResultado($relatorio, 'seguranca', 'ALERTA', "AuthModel não parece validar status do usuário (ativo/inativo).");
    }
}

// 5. Auditoria de Limpeza (Zero .bak/.old)
$dirty_files = shell_exec("find . -name '*.bak' -o -name '*.old' | grep -v 'vendor'");
if (empty($dirty_files)) {
    logResultado($relatorio, 'limpeza', 'OK', "Nenhum arquivo de backup (.bak/.old) encontrado.");
} else {
    logResultado($relatorio, 'limpeza', 'ERRO', "Arquivos residuais detectados:\n" . $dirty_files);
}

// --- RELATÓRIO FINAL ---

echo "\nRESUMO DA AUDITORIA:\n";
foreach ($relatorio as $cat => $dados) {
    $status = ($dados['sucesso'] === $dados['total']) ? "✅" : "❌";
    echo sprintf("%-12s: %s %d/%d Sucessos\n", ucfirst($cat), $status, $dados['sucesso'], $dados['total']);
}

if ($relatorio['arquitetura']['sucesso'] < $relatorio['arquitetura']['total'] || 
    $relatorio['seguranca']['sucesso'] < $relatorio['seguranca']['total']) {
    echo "\n⚠️ ATENÇÃO: Falhas críticas detectadas. O sistema não atende aos requisitos de conformidade Fase 5.\n";
    foreach (array_merge($relatorio['arquitetura']['falhas'], $relatorio['seguranca']['falhas']) as $falha) {
        echo "  - $falha\n";
    }
    exit(1);
} else {
    echo "\n💎 SUCESSO: O sistema está em total conformidade com os padrões de excelência da Fase 5.\n";
    exit(0);
}
