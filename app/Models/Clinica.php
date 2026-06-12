<?php

namespace App\Models;

use PDO;
use Exception;

class Clinica
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Obtém os dados da clínica (Tabela clinicas)
     */
    public function getDados(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clinicas WHERE id = ?");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Atualiza os dados institucionais da clínica
     */
    public function atualizarDados(array $data): bool
    {
        $stmt = $this->pdo->prepare("UPDATE clinicas SET nome_fantasia = ?, razao_social = ?, cnpj = ? WHERE id = ?");
        return $stmt->execute([
            $data['nome_fantasia'],
            $data['razao_social'] ?? null,
            $data['cnpj'] ?? null,
            $this->clinica_id
        ]);
    }

    /**
     * Obtém todas as configurações (Chave-Valor)
     */
    public function getConfiguracoes(): array
    {
        $stmt = $this->pdo->prepare("SELECT chave, valor FROM clinica_configuracoes WHERE clinica_id = ?");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Salva ou atualiza configurações (Chave-Valor)
     */
    public function salvarConfiguracoes(array $configs): void
    {
        foreach ($configs as $chave => $valor) {
            $stmt = $this->pdo->prepare("INSERT INTO clinica_configuracoes (clinica_id, chave, valor) 
                                        VALUES (?, ?, ?) 
                                        ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $stmt->execute([$this->clinica_id, $chave, $valor]);
        }
    }

    /**
     * Obtém a regra de comissão
     */
    public function getRegraComissao(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clinica_regras_comissao WHERE clinica_id = ? LIMIT 1");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Salva ou atualiza a regra de comissão
     */
    public function salvarRegraComissao(array $data): bool
    {
        $stmtCheck = $this->pdo->prepare("SELECT id FROM clinica_regras_comissao WHERE clinica_id = ?");
        $stmtCheck->execute([$this->clinica_id]);
        $id = $stmtCheck->fetchColumn();

        if ($id) {
            $sql = "UPDATE clinica_regras_comissao SET tipo = ?, valor_regra = ?, valor_meta = ?, percentual_bonus = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$data['tipo'], $data['valor_regra'], $data['valor_meta'], $data['percentual_bonus'], $id]);
        } else {
            $sql = "INSERT INTO clinica_regras_comissao (clinica_id, tipo, valor_regra, valor_meta, percentual_bonus) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$this->clinica_id, $data['tipo'], $data['valor_regra'], $data['valor_meta'], $data['percentual_bonus']]);
        }
    }

    /**
     * Obtém todas as taxas de cartão
     */
    public function getTaxasCartao(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM clinica_taxas_cartao WHERE clinica_id = ? ORDER BY modalidade, parcelas ASC");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva ou atualiza uma taxa de cartão específica
     */
    public function salvarTaxaCartao(array $data): bool
    {
        if (isset($data['id']) && !empty($data['id'])) {
            $stmt = $this->pdo->prepare("UPDATE clinica_taxas_cartao SET bandeira = ?, modalidade = ?, parcelas = ?, taxa_percentual = ? WHERE id = ? AND clinica_id = ?");
            return $stmt->execute([$data['bandeira'], $data['modalidade'], $data['parcelas'], $data['taxa_percentual'], $data['id'], $this->clinica_id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO clinica_taxas_cartao (clinica_id, bandeira, modalidade, parcelas, taxa_percentual) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$this->clinica_id, $data['bandeira'], $data['modalidade'], $data['parcelas'], $data['taxa_percentual']]);
        }
    }

    /**
     * Remove uma taxa de cartão
     */
    public function excluirTaxaCartao(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM clinica_taxas_cartao WHERE id = ? AND clinica_id = ?");
        return $stmt->execute([$id, $this->clinica_id]);
    }
}
