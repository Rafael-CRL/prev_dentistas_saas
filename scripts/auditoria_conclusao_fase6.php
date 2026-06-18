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
}

// 6. Verificação Dinâmica de Imutabilidade Histórica
try {
    // A. Confirmar via query que nenhum registro em atendimento_procedimentos tem valor_procedimento = 0 ou NULL
    $stmtZeroNull = $pdo->query("
        SELECT COUNT(*) 
        FROM atendimento_procedimentos 
        WHERE valor_procedimento = 0 OR valor_procedimento IS NULL
    ");
    $qtdZeroNull = (int)$stmtZeroNull->fetchColumn();
    
    if ($qtdZeroNull === 0) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Nenhum procedimento de atendimento possui valor zerado ou nulo no histórico.\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Encontrados $qtdZeroNull registros com valor zerado/nulo em atendimento_procedimentos.\n";
        $falhas++;
    }

    // B. Simular alteração de preço base em procedimentos e verificar que os valores passados de atendimento_procedimentos não mudam
    $stmtProc = $pdo->query("
        SELECT ap.id_procedimento, ap.valor_procedimento, p.valor_base 
        FROM atendimento_procedimentos ap
        JOIN procedimentos p ON ap.id_procedimento = p.id
        LIMIT 1
    ");
    $amostra = $stmtProc->fetch(PDO::FETCH_ASSOC);

    if ($amostra) {
        $idProc = (int)$amostra['id_procedimento'];
        $valorHistorico = (float)$amostra['valor_procedimento'];
        $valorBaseAtual = (float)$amostra['valor_base'];

        // Iniciamos uma transação para que a simulação não persista no banco
        $pdo->beginTransaction();

        // Modifica o valor base do procedimento cadastrado
        $novoValorSimulado = $valorBaseAtual + 50.00;
        $stmtUpdate = $pdo->prepare("UPDATE procedimentos SET valor_base = ? WHERE id = ?");
        $stmtUpdate->execute([$novoValorSimulado, $idProc]);

        // Consulta novamente o valor histórico na tabela transacional
        $stmtCheckHist = $pdo->prepare("SELECT valor_procedimento FROM atendimento_procedimentos WHERE id_procedimento = ? LIMIT 1");
        $stmtCheckHist->execute([$idProc]);
        $valorPosAlteracao = (float)$stmtCheckHist->fetchColumn();

        // Reverte as alterações para não corromper o banco
        $pdo->rollBack();

        if (abs($valorPosAlteracao - $valorHistorico) < 0.001) {
            echo "{$cores['sucesso']}[OK]{$cores['reset']} Simulação de imutabilidade histórica passou. Alterar valor_base não afetou registros históricos.\n";
        } else {
            echo "{$cores['erro']}[FALHA]{$cores['reset']} Simulação falhou! Alterar valor_base corrompeu os valores históricos (Anterior: $valorHistorico, Novo: $valorPosAlteracao).\n";
            $falhas++;
        }
    } else {
        echo "{$cores['aviso']}[AVISO]{$cores['reset']} Sem registros em atendimento_procedimentos para simular teste de imutabilidade.\n";
    }

} catch (Exception $e) {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Erro ao executar teste lógico de imutabilidade: " . $e->getMessage() . "\n";
    $falhas++;
}

// 7. Validação Estática de Casos de Borda no Controller
try {
    $controllerCode = file_get_contents(__DIR__ . '/../app/Controllers/ClinicaController.php');

    // A. Validar que comissões fora de 0-100 são bloqueadas
    $hasComissaoValidation = (
        strpos($controllerCode, '< 0') !== false && 
        strpos($controllerCode, '> 100') !== false && 
        strpos($controllerCode, 'comissao_especializado') !== false
    );
    if ($hasComissaoValidation) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Validação de limite de comissão (0-100%) detectada no Controller.\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Falta validação de limite de comissão (0-100%) no ClinicaController.php.\n";
        $falhas++;
    }

    // B. Validar que taxa de cartão fora de 0-100 é bloqueada
    $hasTaxaValidation = (
        strpos($controllerCode, '< 0') !== false && 
        strpos($controllerCode, '> 100') !== false && 
        strpos($controllerCode, 'taxa_percentual') !== false
    );
    if ($hasTaxaValidation) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Validação de limite de taxa de cartão (0-100%) detectada no Controller.\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Falta validação de limite de taxa de cartão (0-100%) no ClinicaController.php.\n";
        $falhas++;
    }

    // C. Validar que parcelas fora de 1-12 ou 1-24 são rejeitadas
    $hasParcelasValidation = (
        strpos($controllerCode, '< 1') !== false && 
        (strpos($controllerCode, '> 12') !== false || strpos($controllerCode, '> 24') !== false) && 
        strpos($controllerCode, 'parcelas') !== false
    );
    if ($hasParcelasValidation) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Validação de limite de parcelas (1-12 ou 1-24) detectada no Controller.\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Falta validação de limite de parcelas (1-12 ou 1-24) no ClinicaController.php.\n";
        $falhas++;
    }
} catch (Exception $e) {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Erro ao executar teste estático de casos de borda: " . $e->getMessage() . "\n";
    $falhas++;
}

// 8. Teste Dinâmico de Rejeição de POST sem CSRF (via cURL)
try {
    // Rota pública do AuthController que exige CSRF no POST
    $cmdAuth = 'curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost/actions/login_handler.php';
    $statusAuth = (int)trim(shell_exec($cmdAuth));

    // Rota protegida do ClinicaController
    $cmdClinica = 'curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost/clinica/salvar-dados';
    $statusClinica = (int)trim(shell_exec($cmdClinica));

    if ($statusAuth === 403) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Teste dinâmico: AuthController bloqueou POST sem CSRF com status 403 (Forbidden).\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Teste dinâmico: AuthController POST sem CSRF retornou status $statusAuth (esperado: 403).\n";
        $falhas++;
    }

    if ($statusClinica === 302 || $statusClinica === 403) {
        echo "{$cores['sucesso']}[OK]{$cores['reset']} Teste dinâmico: ClinicaController impediu processamento não autorizado de POST sem CSRF (Status: $statusClinica).\n";
    } else {
        echo "{$cores['erro']}[FALHA]{$cores['reset']} Teste dinâmico: ClinicaController POST sem CSRF retornou status $statusClinica (esperado: 302 ou 403).\n";
        $falhas++;
    }
} catch (Exception $e) {
    echo "{$cores['erro']}[FALHA]{$cores['reset']} Erro ao executar teste dinâmico de CSRF via cURL: " . $e->getMessage() . "\n";
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
