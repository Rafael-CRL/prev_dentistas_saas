<?php
/**
 * scripts/valida_correcoes.php
 * Script de validação automatizada das 4 correções de débito técnico pós-Fase 5.
 */

echo "=== INICIANDO VALIDAÇÃO TÉCNICA DAS CORREÇÕES ===\n\n";

$erros = 0;

// Helper para assert
function assertTest($title, $condition, $failMessage) {
    global $erros;
    if ($condition) {
        echo "  [OK] $title\n";
    } else {
        echo "  [FALHA] $title: $failMessage\n";
        $erros++;
    }
}

// -----------------------------------------------------------------------------
// Item 1 — Recibo hardcoded (SaaS)
// -----------------------------------------------------------------------------
echo "[Item 1] Validando View de Recibo...\n";
$reciboPath = __DIR__ . '/../app/Views/atendimentos/recibo.php';
if (file_exists($reciboPath)) {
    $content = file_get_contents($reciboPath);
    // Verifica se os valores estáticos foram removidos
    $hasHardcode = strpos($content, '"Clínica Odontológica Prev Dentistas"') !== false;
    assertTest(
        "Ausência de dados institucionais hardcoded no recibo.php",
        !$hasHardcode,
        "Ainda há o nome da clínica hardcoded no recibo."
    );
} else {
    assertTest("View recibo.php existente", false, "Arquivo não encontrado.");
}

// -----------------------------------------------------------------------------
// Item 2 — AuthModel e Isolamento de Login (SaaS / Segurança)
// -----------------------------------------------------------------------------
echo "\n[Item 2] Validando Isolamento de Login...\n";
$authModelPath = __DIR__ . '/../app/Models/AuthModel.php';
if (file_exists($authModelPath)) {
    $content = file_get_contents($authModelPath);
    // Verifica se authenticate exige clinica_id
    $hasClinicaFilter = strpos($content, 'AND clinica_id = ?') !== false;
    assertTest(
        "Filtro de clinica_id na busca de credenciais no AuthModel",
        $hasClinicaFilter,
        "A query de autenticação não filtra por clinica_id."
    );

    $hasFindClinica = strpos($content, 'function findClinicaId') !== false;
    assertTest(
        "Método findClinicaId presente no AuthModel",
        $hasFindClinica,
        "Método findClinicaId ausente no AuthModel."
    );
} else {
    assertTest("AuthModel.php existente", false, "Arquivo não encontrado.");
}

$loginViewPath = __DIR__ . '/../app/Views/auth/login.php';
if (file_exists($loginViewPath)) {
    $content = file_get_contents($loginViewPath);
    $hasInput = strpos($content, 'name="clinica_identificador"') !== false;
    assertTest(
        "Presença do campo clinica_identificador na View de login",
        $hasInput,
        "O campo input name='clinica_identificador' não foi localizado na tela de login."
    );
}

// -----------------------------------------------------------------------------
// Item 3 — Fallbacks hardcoded em Config.php e FinanceiroService.php (Zero Hardcode)
// -----------------------------------------------------------------------------
echo "\n[Item 3] Validando Ausência de Fallbacks (Zero Hardcode)...\n";
$configPath = __DIR__ . '/../app/Models/Config.php';
if (file_exists($configPath)) {
    $content = file_get_contents($configPath);
    // Não deve conter a taxa de débito antiga 0.009875
    $hasOldDebitRate = strpos($content, '0.009875') !== false;
    assertTest(
        "Remoção de taxas de cartão fallback hardcoded no Config.php",
        !$hasOldDebitRate,
        "Config.php ainda contém fallbacks estáticos para taxas de cartão (ex: 0.009875)."
    );

    // Deve lançar exceções
    $hasExceptions = strpos($content, 'throw new \\Exception') !== false;
    assertTest(
        "Lançamento de exceções explícitas em caso de falta de parâmetros no Config.php",
        $hasExceptions,
        "Config.php não lança exceções para configurações faltantes."
    );
}

$finServicePath = __DIR__ . '/../app/Services/FinanceiroService.php';
if (file_exists($finServicePath)) {
    $content = file_get_contents($finServicePath);
    // Não deve conter o valor 50 hardcoded em get('comissao_especializado', 50)
    $hasOldFallback = strpos($content, "get('comissao_especializado', 50)") !== false;
    assertTest(
        "Remoção do fallback de comissões secundárias no FinanceiroService.php",
        !$hasOldFallback,
        "FinanceiroService.php ainda contém fallbacks estáticos para comissões especializadas."
    );
}

// -----------------------------------------------------------------------------
// Item 4 — die() no UsuarioController (Polimento)
// -----------------------------------------------------------------------------
echo "\n[Item 4] Validando Polimento do UsuarioController...\n";
$usuarioCtrlPath = __DIR__ . '/../app/Controllers/UsuarioController.php';
if (file_exists($usuarioCtrlPath)) {
    $content = file_get_contents($usuarioCtrlPath);
    // Conta ocorrências de die()
    $dieCount = substr_count($content, 'die(');
    assertTest(
        "Ausência de chamadas de die() no UsuarioController.php",
        $dieCount === 0,
        "Foram encontradas $dieCount ocorrências de die() no UsuarioController."
    );
}

echo "\n=== RESUMO FINAL DA VALIDAÇÃO ===\n";
if ($erros === 0) {
    echo "STATUS: 💎 SUCESSO! Todas as correções foram validadas com sucesso.\n";
    exit(0);
} else {
    echo "STATUS: ❌ ERRO! Foram detectados $erros erros nas validações. Verifique os logs.\n";
    exit(1);
}
