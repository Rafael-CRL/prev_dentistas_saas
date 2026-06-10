<?php
/**
 * scripts/auditoria_conclusao_fase4.php
 * Auditoria de Conformidade Arquitetural - Sistema SaaS Prev-Dentistas
 */

require_once __DIR__ . '/../config/database.php';

$requisitos_classes = [
    ['app/Models/Config.php', 'Classe Config (Singleton)'],
    ['app/Services/FinanceiroService.php', 'Classe FinanceiroService']
];

$arquivos_refatorados = [
    'app/Controllers/AtendimentoController.php',
    'actions/salvar_pagamento.php'
];

$relatorio = [
    'obrigatorios' => ['total' => 0, 'sucesso' => 0, 'falhas' => []]
];

function adicionarResultado(&$relatorio, $status, $msg, $item) {
    $relatorio['obrigatorios']['total']++;
    if ($status === 'OK') {
        $relatorio['obrigatorios']['sucesso']++;
    } else {
        $relatorio['obrigatorios']['falhas'][] = "[$status] $item: $msg";
    }
}

echo "AUDITORIA TÉCNICA - FASE 4 (Infraestrutura Zero Hardcode)\n";
echo str_repeat("-", 40) . "\n";

foreach ($requisitos_classes as $req) {
    $path = __DIR__ . '/../' . $req[0];
    adicionarResultado($relatorio, file_exists($path) ? 'OK' : 'ERRO', "Arquivo não encontrado", $req[0]);
}

foreach ($arquivos_refatorados as $arquivo) {
    $path = __DIR__ . '/../' . $arquivo;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $usesNewService = strpos($content, 'FinanceiroService') !== false;
        adicionarResultado($relatorio, $usesNewService ? 'OK' : 'ERRO', "Não utiliza FinanceiroService", $arquivo);
    } else {
        adicionarResultado($relatorio, 'ERRO', "Arquivo consumidor não encontrado", $arquivo);
    }
}

$perc = ($relatorio['obrigatorios']['total'] > 0) ? round(($relatorio['obrigatorios']['sucesso'] / $relatorio['obrigatorios']['total']) * 100) : 0;

echo "\nRESUMO: $perc% " . ($perc == 100 ? "✅" : "❌") . "\n";
if (!empty($relatorio['obrigatorios']['falhas'])) {
    foreach ($relatorio['obrigatorios']['falhas'] as $f) echo "  - $f\n";
}

if ($perc >= 100) {
    echo "\nVEREDITO: FASE 4 CONCLUÍDA.\n";
} else {
    echo "\nVEREDITO: FASE 4 INCOMPLETA.\n";
    exit(1);
}
