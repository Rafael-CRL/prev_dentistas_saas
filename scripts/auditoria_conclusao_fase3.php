<?php
/**
 * scripts/auditoria_conclusao_fase3.php
 * Auditoria de Conformidade Arquitetural - Sistema SaaS Prev-Dentistas
 * Versão Consolidada: Estrutura + Dados + Artefatos
 */

require_once __DIR__ . '/../config/database.php';

// --- CONFIGURAÇÃO DA MATRIZ DE REQUISITOS (Baseada em DB.md e Planejamento.md) ---

$requisitos_artefatos = [
    ['database/migration.sql', 'Arquivo de migração consolidado', 'Planejamento.md - Fase 3']
];

$requisitos_tabelas = [
    ['clinicas', 'Tabela Mestra de Clientes', 'DB.md Seção 2'],
    ['clinica_configuracoes', 'Tabela de Personalização', 'DB.md Seção 2'],
    ['clinica_taxas_cartao', 'Parâmetros de Taxas (Zero Hardcode)', 'DB.md Seção 2'],
    ['clinica_regras_comissao', 'Regras de Repasse (Zero Hardcode)', 'DB.md Seção 2'],
];

$requisitos_isolamento = [
    'usuarios', 'pacientes', 'procedimentos', 'atendimentos',
    'despesas', 'atendimento_procedimentos', 'atendimento_pagamentos'
];

$requisitos_indices = [
    ['pacientes', 'cpf', 'Planejamento.md - Constraints'],
    ['usuarios', 'login', 'Planejamento.md - Constraints']
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

echo "AUDITORIA TÉCNICA FINAL - FASE 3 (SaaS Multi-tenant)\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Auditoria de Artefatos (Sistema de Arquivos)
foreach ($requisitos_artefatos as $req) {
    $path = __DIR__ . '/../' . $req[0];
    $exists = file_exists($path);
    $size_ok = $exists && filesize($path) > 100;
    $status = $size_ok ? 'OK' : 'ERRO';
    adicionarResultado($relatorio, 'obrigatorios', $status, "Arquivo não encontrado ou vazio ({$req[2]})", $req[0]);
}

// 2. Verificar Tabelas Existentes
$stmt = $pdo->query("SHOW TABLES");
$tabelas_no_banco = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requisitos_tabelas as $req) {
    $status = in_array($req[0], $tabelas_no_banco) ? 'OK' : 'ERRO';
    adicionarResultado($relatorio, 'obrigatorios', $status, "Tabela FALTANDO no banco (Ref: {$req[2]})", $req[0]);
}

// 3. Validar Isolamento, Tipagem e Integridade de Dados
foreach ($requisitos_isolamento as $tabela) {
    if (!in_array($tabela, $tabelas_no_banco)) {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Tabela não encontrada para validar isolamento", $tabela);
        continue;
    }

    // A. Metadados: clinica_id existe e é do tipo correto?
    $stmt = $pdo->prepare("
        SELECT COLUMN_TYPE, IS_NULLABLE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'clinica_id'
    ");
    $stmt->execute([$tabela]);
    $col = $stmt->fetch();

    if (!$col) {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "FALTA coluna clinica_id (DB.md Seção 3)", $tabela);
    } else {
        adicionarResultado($relatorio, 'obrigatorios', 'OK', "clinica_id presente", $tabela);
        
        $tipo_ok = (strpos($col['COLUMN_TYPE'], 'int') !== false && $col['IS_NULLABLE'] === 'NO');
        if (!$tipo_ok) {
            adicionarResultado($relatorio, 'recomendados', 'ALERTA', "clinica_id deve ser INT e NOT NULL (Atual: {$col['COLUMN_TYPE']}, Null: {$col['IS_NULLABLE']})", $tabela);
        } else {
            adicionarResultado($relatorio, 'recomendados', 'OK', "Tipagem clinica_id correta", $tabela);
        }
    }

    // B. Integridade de Dados: Existem registros órfãos?
    if ($col) {
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM $tabela t 
            LEFT JOIN clinicas c ON t.clinica_id = c.id 
            WHERE t.clinica_id IS NULL OR c.id IS NULL
        ");
        $orfaos = $stmt->fetchColumn();
        if ($orfaos > 0) {
            adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Detectados $orfaos registros órfãos (clinica_id inválido)", $tabela);
        } else {
            adicionarResultado($relatorio, 'obrigatorios', 'OK', "Integridade de dados OK (zero órfãos)", $tabela);
        }
    }

    // C. Foreign Keys (Recomendação Arquitetural)
    $stmt = $pdo->prepare("
        SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'clinica_id' AND REFERENCED_TABLE_NAME = 'clinicas'
    ");
    $stmt->execute([$tabela]);
    if (!$stmt->fetch()) {
        adicionarResultado($relatorio, 'recomendados', 'ALERTA', "Falta Foreign Key (Integridade referencial não forçada no banco)", $tabela);
    } else {
        adicionarResultado($relatorio, 'recomendados', 'OK', "Foreign Key presente", $tabela);
    }
}

// 4. Validar Índices Únicos Compostos (Regras de Negócio)
foreach ($requisitos_indices as $req) {
    list($tabela, $coluna, $fonte) = $req;
    if (!in_array($tabela, $tabelas_no_banco)) continue;

    $stmt = $pdo->prepare("
        SELECT INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0
        ORDER BY INDEX_NAME, SEQ_IN_INDEX
    ");
    $stmt->execute([$tabela]);
    $indices = $stmt->fetchAll(PDO::FETCH_GROUP);

    $composto_ok = false;
    $ordem_ideal = false;
    $legado_detectado = false;

    foreach ($indices as $nome => $colunas) {
        $cols = array_column($colunas, 'COLUMN_NAME');
        
        // Verifica se é composto
        if (in_array('clinica_id', $cols) && in_array($coluna, $cols)) {
            $composto_ok = true;
            // Verifica ordem (Recomendado para performance)
            if ($cols[0] === 'clinica_id') $ordem_ideal = true;
        }
        
        // Verifica se o legado (único simples) ainda existe
        if (count($cols) === 1 && $cols[0] === $coluna) {
            $legado_detectado = true;
        }
    }

    if (!$composto_ok) {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Falta índice Único Composto (clinica_id, $coluna)", $tabela);
    } else {
        adicionarResultado($relatorio, 'obrigatorios', 'OK', "Índice composto presente", $tabela);
        
        $status_ordem = $ordem_ideal ? 'OK' : 'ALERTA';
        $msg_ordem = $ordem_ideal ? "Ordem de colunas ideal" : "clinica_id deveria ser a primeira coluna do índice para performance";
        adicionarResultado($relatorio, 'recomendados', $status_ordem, $msg_ordem, "$tabela.$coluna");
    }

    if ($legado_detectado) {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Índice único legado ainda existe (Bloqueia Multi-tenant)", $tabela);
    }
}

// 5. Cadastros Mínimos (Zero Hardcode Check)
try {
    $stmt = $pdo->query("SELECT id FROM clinicas LIMIT 1");
    if (!$stmt->fetchColumn()) {
        adicionarResultado($relatorio, 'obrigatorios', 'ERRO', "Nenhuma clínica cadastrada na tabela mestra", 'clinicas');
    }
    
    foreach (['clinica_configuracoes', 'clinica_taxas_cartao', 'clinica_regras_comissao'] as $t_cfg) {
        if (in_array($t_cfg, $tabelas_no_banco)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $t_cfg");
            $qtd = $stmt->fetchColumn();
            $status = $qtd > 0 ? 'OK' : 'ALERTA';
            adicionarResultado($relatorio, 'recomendados', $status, "$qtd registros encontrados (Necessários para Fase 4)", $t_cfg);
        }
    }
} catch (Exception $e) {}

// --- RESULTADO FINAL ---

$perc_obrigatorios = ($relatorio['obrigatorios']['total'] > 0) ? round(($relatorio['obrigatorios']['sucesso'] / $relatorio['obrigatorios']['total']) * 100) : 0;
$perc_recomendados = ($relatorio['recomendados']['total'] > 0) ? round(($relatorio['recomendados']['sucesso'] / $relatorio['recomendados']['total']) * 100) : 0;

echo "RESUMO DA AUDITORIA:\n";
echo "REQUISITOS OBRIGATÓRIOS: $perc_obrigatorios% " . ($perc_obrigatorios == 100 ? "(✓)" : "(✗)") . "\n";
echo "RECOMENDAÇÕES TÉCNICAS: $perc_recomendados% " . ($perc_recomendados == 100 ? "(✓)" : "(ℹ)") . "\n\n";

if (!empty($relatorio['obrigatorios']['falhas'])) {
    echo "FALHAS CRÍTICAS (Impede conclusão da Fase 3):\n";
    foreach ($relatorio['obrigatorios']['falhas'] as $f) echo "  - $f\n";
}

if (!empty($relatorio['recomendados']['falhas'])) {
    echo "\nOBSERVAÇÕES ARQUITETURAIS (Melhoria Contínua):\n";
    foreach ($relatorio['recomendados']['falhas'] as $f) echo "  - $f\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
if ((int)$perc_obrigatorios >= 100) {
    echo "VEREDITO: A Fase 3 está CONCLUÍDA. O banco e os artefatos seguem o planejamento.\n";
} else {
    echo "VEREDITO: A Fase 3 está INCOMPLETA. Existem pendências críticas listadas acima.\n";
}
