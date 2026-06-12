<?php
/**
 * AUDITORIA TÉCNICA - FINALIZAÇÃO DA FASE 6 (ITEM 1)
 * Verificação de integridade para Gestão da Clínica e Edição de Preços.
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../config/database.php';

$cores = [
    'sucesso' => "\033[0;32m",
    'erro'    => "\033[0;31m",
    'aviso'   => "\033[1;33m",
    'reset'   => "\033[0m"
];

echo "\n{$cores['aviso']}=== INICIANDO AUDITORIA DA FASE 6 (ITEM 1) - GESTÃO DA CLÍNICA ==={$cores['reset']}\n\n";

$falhas = 0;

// 1. Verificação de Arquivos Base (MVC)
$arquivosObrigatorios = [
    'app/Models/Clinica.php',
    'app/Controllers/ClinicaController.php',
    'app/Views/clinica/painel.php',
    'app/Views/procedimentos/editar.php'
];

foreach ($arquivosObrigatorios as $arquivo) {
    if (file_exists(__DIR__ . '/../' . $arquivo)) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Arquivo encontrado: $arquivo\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Arquivo ausente: $arquivo\n";
        $falhas++;
    }
}

// 2. Verificação de Integridade do Model Procedimento (Imutabilidade)
try {
    $reflection = new ReflectionClass('App\Models\Procedimento');
    if ($reflection->hasMethod('update') && $reflection->hasMethod('getById')) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Model Procedimento atualizado para edição de preços.\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Model Procedimento não possui métodos de edição.\n";
        $falhas++;
    }
} catch (Exception $e) {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Erro ao refletir classe Procedimento.\n";
    $falhas++;
}

// 3. Verificação de Tabelas no Banco de Dados (Zero Hardcode)
$tabelasNecessarias = [
    'clinica_taxas_cartao',
    'clinica_regras_comissao',
    'clinica_configuracoes'
];

foreach ($tabelasNecessarias as $tabela) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tabela]);
    if ($stmt->fetch()) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Tabela detectada no banco: $tabela\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Tabela ausente no banco: $tabela\n";
        $falhas++;
    }
}

// 4. Verificação de Roteamento no Front Controller
$frontController = file_get_contents(__DIR__ . '/../public/index.php');
if (strpos($frontController, 'clinica/painel') !== false && strpos($frontController, 'procedimentos/editar') !== false) {
    echo "{$cores['sucesso']}[OK]{$cores['reset']} Rotas da Fase 6 detectadas no Front Controller.\n";
} else {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Rotas da Fase 6 não configuradas no index.php.\n";
    $falhas++;
}

// 5. Verificação de Segurança (CSRF)
$painelView = file_get_contents(__DIR__ . '/../app/Views/clinica/painel.php');
if (strpos($painelView, 'CsrfHelper::input()') !== false) {
    echo "{$cores['sucesso']}[OK]{$cores['reset']} Proteção CSRF detectada nos formulários administrativos.\n";
} else {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Formulários sem proteção CSRF detectada.\n";
    $falhas++;
}

// 6. Teste de Integração Lógica (Taxas Dinâmicas)
echo "\n{$cores['aviso']}--- TESTE DE INTEGRAÇÃO FINANCEIRA ---{$cores['reset']}\n";
try {
    $clinica_id = 1; // Clínica padrão para teste
    
    // Força a reinicialização da instância Singleton para carregar dados frescos
    $reflection = new ReflectionClass('App\Models\Config');
    $instanceProperty = $reflection->getProperty('instance');
    $instanceProperty->setAccessible(true);
    $instanceProperty->setValue(null, null);

    $config = App\Models\Config::getInstance($pdo, $clinica_id);
    $service = new App\Services\FinanceiroService($config);
    
    // Simula uma venda de R$ 100,00 no crédito 1x
    $resultado = $service->calcularLiquidoMaquininha(100, 'credito', 1);
    
    if (isset($resultado['taxa_aplicada_percentual'])) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} FinanceiroService está consumindo taxas dinâmicas (Taxa detectada: {$resultado['taxa_aplicada_percentual']}%).\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} FinanceiroService não retornou a taxa aplicada.\n";
        $falhas++;
    }
} catch (Exception $e) {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Erro no teste de integração: " . $e->getMessage() . "\n";
    $falhas++;
}

// 7. Verificação de Partials (Pilar C)
if (file_exists(__DIR__ . '/../app/Views/partials/alert.php')) {
    echo "{$cores['sucesso']}[OK]{$cores['reset']} Partial de alertas (Pilar C) encontrado.\n";
} else {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Partial de alertas não encontrado.\n";
    $falhas++;
}

echo "\n" . str_repeat("=", 50) . "\n";
if ($falhas === 0) {
    echo "{$cores['sucesso']}AUDITORIA CONCLUÍDA COM SUCESSO!{$cores['reset']}\n";
    echo "O Item 1 da Fase 6 está pronto para homologação e deploy.\n";
} else {
    echo "{$cores['erro']}AUDITORIA REPROVADA!{$cores['reset']}\n";
    echo "Foram detectadas $falhas falhas que impedem a conclusão da etapa.\n";
}
echo str_repeat("=", 50) . "\n\n";
