<?php
namespace App\Models;

use PDO;

class Config
{
    private static $instance = null;
    private $pdo;
    private $clinicaId;

    private $taxasCartao = [];
    private $regrasComissao = [];
    private $configuracoes = [];

    private function __construct(PDO $pdo, int $clinicaId)
    {
        $this->pdo = $pdo;
        $this->clinicaId = $clinicaId;
        $this->loadAll();
    }

    /**
     * Singleton Pattern - Garante apenas uma leitura no banco por requisição.
     */
    public static function getInstance(PDO $pdo, int $clinicaId): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo, $clinicaId);
        }
        return self::$instance;
    }

    private function loadAll()
    {
        // 1. Carregar Configurações Genéricas (Chave-Valor)
        $stmtConfig = $this->pdo->prepare("SELECT chave, valor FROM clinica_configuracoes WHERE clinica_id = ?");
        $stmtConfig->execute([$this->clinicaId]);
        foreach ($stmtConfig->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->configuracoes[$row['chave']] = $row['valor'];
        }

        // 2. Carregar Regras de Comissão Base
        $stmtRegra = $this->pdo->prepare("SELECT * FROM clinica_regras_comissao WHERE clinica_id = ? LIMIT 1");
        $stmtRegra->execute([$this->clinicaId]);
        $this->regrasComissao = $stmtRegra->fetch(PDO::FETCH_ASSOC) ?: [];

        // 3. Carregar Taxas de Cartão
        $stmtTaxas = $this->pdo->prepare("SELECT bandeira, modalidade, parcelas, taxa_percentual FROM clinica_taxas_cartao WHERE clinica_id = ?");
        $stmtTaxas->execute([$this->clinicaId]);
        foreach ($stmtTaxas->fetchAll(PDO::FETCH_ASSOC) as $row) {
            // Indexa por 'modalidade_parcelas' para busca rápida. Ignora bandeira por enquanto se não vier da UI.
            $key = $row['modalidade'] . '_' . $row['parcelas'];
            $taxaDecimal = (float)$row['taxa_percentual'] / 100;
            
            $this->taxasCartao[$row['bandeira']][$key] = $taxaDecimal;
            // Salva um default caso a bandeira não seja especificada (comum no sistema legado)
            if (!isset($this->taxasCartao['default'][$key])) {
                $this->taxasCartao['default'][$key] = $taxaDecimal;
            }
        }
    }

    /**
     * Obtém a taxa do cartão baseada na modalidade (debito/credito) e qtd de parcelas
     */
    public function getTaxaCartao(string $modalidade, int $parcelas = 1, string $bandeira = 'default'): float
    {
        $key = $modalidade . '_' . $parcelas;
        
        if (isset($this->taxasCartao[$bandeira][$key])) {
            return $this->taxasCartao[$bandeira][$key];
        }
        
        if (isset($this->taxasCartao['default'][$key])) {
            return $this->taxasCartao['default'][$key];
        }

        // Fallbacks baseados no sistema legado caso a configuração não exista no banco ainda
        if ($modalidade === 'debito') return 0.009875;
        if ($modalidade === 'credito') {
            if ($parcelas <= 1) return 0.03;
            if ($parcelas <= 6) return 0.05;
            return 0.1076;
        }

        return 0.0;
    }

    /**
     * Retorna a regra de comissão estruturada
     */
    public function getRegraComissao(): array
    {
        // Se a clínica não tiver regra cadastrada, retorna fallback do sistema legado
        if (empty($this->regrasComissao)) {
            return [
                'tipo' => 'percentual',
                'valor_regra' => 20.00,
                'valor_meta' => 10000.00,
                'percentual_bonus' => 10.00 // 20 base + 10 bônus = 30 total
            ];
        }
        return $this->regrasComissao;
    }

    /**
     * Obtém uma configuração específica de Chave-Valor
     */
    public function get(string $chave, $default = null)
    {
        return $this->configuracoes[$chave] ?? $default;
    }
}
