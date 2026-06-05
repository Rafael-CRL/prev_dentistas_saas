<?php
/**
 * scripts/auditoria_conclusao_fase4.php
 * Auditoria de Conformidade Arquitetural - Sistema SaaS Prev-Dentistas
 * Foco: Fase 4 - Infraestrutura Zero Hardcode (Config & Services)
 */

require_once __DIR__ . '/../config/database.php';

// --- CONFIGURAÇÃO DA MATRIZ DE REQUISITOS ---

$requisitos_classes = [
    ['app/Models/Config.php', 'Classe Config (Singleton)', 'Planejamento.md - Fase 4'],
    ['app/Services/FinanceiroService.php', 'Classe FinanceiroService', 'Planejamento.md - Fase 4']
];

$arquivos_refatorados = [
    'actions/salvar_atendimento.php',
    'actions/salvar_pagamento.php'
];

// --- MOTOR DE AUDITORIA ---

$relatorio = [
    'obrigatorios' => ['total' => 0, 'sucesso' => 0, 'falhas' => []],
    'recomendados' => ['total' => 0, 'sucesso' => 0, 'falhas' => []]
];

function adicionarResultado(&$relatorio, $tipo, $status, $msg, $item) {
    $relatorio[$tipo]['total']++;
    if ($status === 'OK') {
        $relatorio[$tipo]['sucesso']++;
    } else {
        $relatorio[$tipo]['falhas'][] = "[$status] $item: $msg";
    }
}

echo "AUDITORIA TÉCNICA FINAL - FASE 4 (Infraestrutura Zero Hardcode)\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Auditoria de Existência das Classes Base
foreach ($requisitos_classes as $req) {
    $path = __DIR__ . '/../' . $req[0];
    $exists = file_exists($path);
    $status = $exists ? 'OK' : 'ERRO';
    adicionarResultado($relatorio, 'obrigatorios', $status, "Arquivo da classe não encontrado ({$req[2]})", $req[0]);
}

// 2. Auditoria Estrutural da Classe Config (Singleton Check)
$configPath = __DIR__ . '/../app/Models/Config.php';
if (file_exists($configPath)) {
    $content = file_get_contents($configPath);
    
    // Check for private constructor
    $hasPrivateConstruct = preg_match('/private\s+function\s+__construct/', $content);
    adicionarResultado($relatorio, 'obrigatorios', $hasPrivateConstruct ? 'OK' : 'ERRO', "Construtor não é privado (Violação do Singleton)", 'Config::__construct');
    
    // Check for getInstance method
    $hasGetInstance = preg_match('/public\s+static\s+function\s+getInstance/', $content);
    adicionarResultado($relatorio, 'obrigatorios', $hasGetInstance ? 'OK' : 'ERRO', "Método estático getInstance não encontrado", 'Config::getInstance');

    // Check for namespace
    $hasNamespace = strpos($content, 'namespace App\Models;') !== false;
    adicionarResultado($relatorio, 'obrigatorios', $hasNamespace ? 'OK' : 'ERRO', "Namespace App\Models ausente", 'Config Namespace');
}

// 3. Auditoria do FinanceiroService (Zero Hardcode Check)
$servicePath = __DIR__ . '/../app/Services/FinanceiroService.php';
if (file_exists($servicePath)) {
    $content = file_get_contents($servicePath);
    
    // O Service não deve ter as constantes velhas declaradas explicitamente como valores (ex: 0.009875 ou 0.20)
    // O ideal é buscar de $this->config.
    $hasHardcodedRate = preg_match('/0\.009875|0\.03|0\.20|0\.30/', $content);
    adicionarResultado($relatorio, 'recomendados', $hasHardcodedRate ? 'ALERTA' : 'OK', "Resquícios de valores hardcoded (taxas decimais literais) detectados no Service", 'FinanceiroService');
    
    $usesConfig = strpos($content, '$this->config->getTaxaCartao') !== false || strpos($content, '$this->config->getRegraComissao') !== false;
    adicionarResultado($relatorio, 'obrigatorios', $usesConfig ? 'OK' : 'ERRO', "Service não está consumindo os dados da classe Config", 'FinanceiroService Dependencies');
}

// 4. Auditoria de Refatoração de Consumidores (Injeção em actions/)
foreach ($arquivos_refatorados as $arquivo) {
    $path = __DIR__ . '/../' . $arquivo;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Verifica se a chamada legada estática existe
        $usesLegacyStatic = strpos($content, 'Financeiro::calcularComissao') !== false || strpos($content, 'Financeiro::calcularLiquidoMaquininha') !== false;
        adicionarResultado($relatorio, 'obrigatorios', $usesLegacyStatic ? 'ERRO' : 'OK', "Ainda utiliza chamada estática legada (Financeiro::)", $arquivo);

        // Verifica se instancia o Service novo
        $usesNewService = strpos($content, 'new FinanceiroService(') !== false || strpos($content, '$financeiroService->calcular') !== false;
        adicionarResultado($relatorio, 'obrigatorios', $usesNewService ? 'OK' : 'ERRO', "Não instancia/utiliza o novo FinanceiroService", $arquivo);
    } else {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Arquivo consumidor não encontrado para análise", $arquivo);
    }
}

// --- RESULTADO FINAL ---

$perc_obrigatorios = ($relatorio['obrigatorios']['total'] > 0) ? round(($relatorio['obrigatorios']['sucesso'] / $relatorio['obrigatorios']['total']) * 100) : 0;
$perc_recomendados = ($relatorio['recomendados']['total'] > 0) ? round(($relatorio['recomendados']['sucesso'] / $relatorio['recomendados']['total']) * 100) : 0;

echo "\nRESUMO DA AUDITORIA:\n";
echo "REQUISITOS OBRIGATÓRIOS: $perc_obrigatorios% " . ($perc_obrigatorios == 100 ? "(✓)" : "(✗)") . "\n";
echo "RECOMENDAÇÕES TÉCNICAS: $perc_recomendados% " . ($perc_recomendados == 100 ? "(✓)" : "(ℹ)") . "\n\n";

if (!empty($relatorio['obrigatorios']['falhas'])) {
    echo "FALHAS CRÍTICAS (Impede conclusão da Fase 4):\n";
    foreach ($relatorio['obrigatorios']['falhas'] as $f) echo "  - $f\n";
}

if (!empty($relatorio['recomendados']['falhas'])) {
    echo "\nOBSERVAÇÕES ARQUITETURAIS (Melhoria Contínua):\n";
    foreach ($relatorio['recomendados']['falhas'] as $f) echo "  - $f\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
if ((int)$perc_obrigatorios >= 100) {
    echo "VEREDITO: A Fase 4 está CONCLUÍDA. A infraestrutura base foi construída com sucesso.\n";
} else {
    echo "VEREDITO: A Fase 4 está INCOMPLETA. Existem pendências críticas listadas acima.\n";
}
