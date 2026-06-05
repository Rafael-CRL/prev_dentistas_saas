<?php
namespace App\Models;

use PDO;
use Exception;

/**
 * Singleton que gerencia as configurações parametrizadas da clínica ativa.
 * Lê de `clinica_taxas_cartao` e `clinica_regras_comissao`.
 */
class Config {
    private static $instance = null;
    private $pdo;
    private $clinica_id;
    private $taxas = [];
    private $comissoes = [];
    private $configuracoes = [];

    private function __construct() {
        global $pdo;
        
        // Garante que a sessão está iniciada para capturar o clinica_id
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Tenta obter o PDO do escopo global se existir, senão usa do database.php (necessário para o fallback do singleton no legado)
        if (!$pdo) {
             // O autoloader pode ser chamado antes do database.php em alguns contextos legados, 
             // então exigimos a conexão caso a global não exista.
             require_once __DIR__ . '/../../config/database.php';
             $this->pdo = $pdo;
        } else {
             $this->pdo = $pdo;
        }

        if (!$this->pdo) {
             throw new Exception("Falha ao obter instância de PDO em Config.");
        }
        
        // Em um sistema real multi-tenant a clínica da sessão é mandatória. 
        // O default para 1 é temporário enquanto algumas áreas legadas não exigem login strict (ex. public).
        $this->clinica_id = $_SESSION['clinica_id'] ?? 1;
        
        $this->loadTaxas();
        $this->loadComissoes();
        $this->loadConfiguracoes();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadTaxas() {
        $stmt = $this->pdo->prepare("SELECT bandeira, modalidade, parcelas, taxa_percentual FROM clinica_taxas_cartao WHERE clinica_id = ?");
        $stmt->execute([$this->clinica_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            // Chave de busca rápida
            $key = strtolower($row['modalidade']) . '_' . strtolower($row['bandeira']) . '_' . $row['parcelas'];
            $this->taxas[$key] = floatval($row['taxa_percentual']);
        }
    }

    private function loadComissoes() {
        $stmt = $this->pdo->prepare("SELECT id, tipo, valor_regra, valor_meta, percentual_bonus FROM clinica_regras_comissao WHERE clinica_id = ?");
        $stmt->execute([$this->clinica_id]);
        // Armazena a primeira (ou única) regra de comissão aplicável.
        $this->comissoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function loadConfiguracoes() {
        $stmt = $this->pdo->prepare("SELECT chave, valor FROM clinica_configuracoes WHERE clinica_id = ?");
        $stmt->execute([$this->clinica_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $this->configuracoes[$row['chave']] = $row['valor'];
        }
    }

    /**
     * Retorna a taxa configurada no banco de dados.
     * Caso não encontre a configuração exata de bandeira, tenta buscar uma genérica,
     * e caso não exista no BD, retorna um valor de fallback seguro.
     */
    public function getTaxaCartao($modalidade, $bandeira = '', $parcelas = 1) {
        $modalidade = strtolower($modalidade);
        $bandeira = strtolower($bandeira);

        if ($bandeira !== '') {
            $key = $modalidade . '_' . $bandeira . '_' . $parcelas;
            if (isset($this->taxas[$key])) {
                return $this->taxas[$key];
            }
        }
        
        // Fallback: Procura a primeira taxa que bata apenas com a modalidade e parcelas
        foreach ($this->taxas as $k => $taxa) {
            if (strpos($k, $modalidade . '_') === 0 && preg_match('/_' . $parcelas . '$/', $k)) {
                return $taxa;
            }
        }
        
        // Retorno padrão (Hardcode de Fallback) caso o BD não tenha sido populado corretamente
        // Isso evita que o sistema pare por completo, mas em produção o ideal é ter os dados no BD.
        if ($modalidade === 'debito') {
            return 1.50; // default debito
        } elseif ($modalidade === 'credito') {
            if ($parcelas == 1) return 2.99;
            if ($parcelas <= 6) return 5.00;
            return 10.00;
        }
        return 0.0;
    }

    /**
     * Retorna as regras de comissão.
     */
    public function getRegraComissao() {
        if (!empty($this->comissoes)) {
            return $this->comissoes[0];
        }
        // Fallback seguro caso a tabela de configuração esteja vazia
        return [
            'tipo' => 'percentual',
            'valor_regra' => 20.00,
            'valor_meta' => 10000.00,
            'percentual_bonus' => 5.00
        ];
    }
    
    /**
     * Retorna configurações gerais dinâmicas (ex: logos, tema).
     */
    public function getConfiguracao($chave, $default = null) {
        return $this->configuracoes[$chave] ?? $default;
    }
}
