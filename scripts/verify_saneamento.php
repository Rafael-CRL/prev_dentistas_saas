<?php
/**
 * Script de Verificação Pós-Saneamento (Versão Evoluída Fase 5)
 * Este script valida a integridade do sistema após a limpeza de arquivos legados.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO VERIFICAÇÃO DE INTEGRIDADE ===\n\n";

$erros = 0;
$avisos = 0;

// 1. Verificação de Arquivos Críticos (Padrão MVC + Legado Estável)
echo "[1/3] Verificando arquivos críticos...\n";
$arquivos_obrigatorios = [
    'public/index.php',
    'app/Controllers/AtendimentoController.php',
    'app/Models/Atendimento.php',
    'config/database.php',
    'public/assets/css/style.css'
];

foreach ($arquivos_obrigatorios as $arq) {
    if (file_exists(__DIR__ . '/../' . $arq)) {
        echo "  OK: $arq encontrado.\n";
    } else {
        echo "  ERRO: $arq NÃO ENCONTRADO!\n";
        $erros++;
    }
}

// 2. Verificação de Links Quebrados (Busca por resquícios de arquivos deletados)
echo "\n[2/3] Verificando referências a arquivos deletados...\n";
$arquivos_de_busca = ['views/header.php', 'index.php', 'actions/salvar_arquivo_procedimento.php'];
$padrao_lixo = '/(relatorio_paciente[23]|novo_atendimento[234]|salvar_atendimento2|salvar_atendimento|verificar_pagamento_pendente)\.php/';

foreach ($arquivos_de_busca as $arq) {
    $caminho = __DIR__ . '/../' . $arq;
    if (file_exists($caminho)) {
        $conteudo = file_get_contents($caminho);
        if (preg_match_all($padrao_lixo, $conteudo, $matches)) {
            echo "  AVISO: Referência antiga encontrada em $arq: " . implode(', ', array_unique($matches[0])) . "\n";
            $avisos++;
        } else {
            echo "  OK: $arq está limpo.\n";
        }
    }
}

// 3. Verificação de Estrutura de Banco de Dados
echo "\n[3/3] Verificando esquema do banco de dados...\n";
try {
    require_once __DIR__ . '/../config/database.php';
    
    $colunas_necessarias = [
        'atendimento_procedimentos' => ['custo_auxiliar', 'natureza', 'status_execucao'],
        'atendimentos' => ['comissao_dentista', 'valor_liquido_clinica']
    ];

    foreach ($colunas_necessarias as $tabela => $colunas) {
        $stmt = $pdo->query("DESCRIBE $tabela");
        $colunas_reais = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($colunas as $col) {
            if (in_array($col, $colunas_reais)) {
                echo "  OK: Coluna '$col' presente na tabela '$tabela'.\n";
            } else {
                echo "  ERRO: Coluna '$col' FALTANDO na tabela '$tabela'!\n";
                $erros++;
            }
        }
    }

} catch (Exception $e) {
    echo "  ERRO DE CONEXÃO AO BANCO: " . $e->getMessage() . "\n";
    $erros++;
}

echo "\n=== RESUMO DA VERIFICAÇÃO ===\n";
echo "Erros Críticos: $erros\n";
echo "Avisos/Lembretes: $avisos\n";

if ($erros === 0) {
    echo "\nCONCLUÍDO: O sistema parece estar íntegro e pronto para uso.\n";
} else {
    echo "\nATENÇÃO: Foram encontrados erros que precisam de correção manual.\n";
}
?>
