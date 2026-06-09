<?php
namespace App\Models;

use PDO;
use Exception;

class Atendimento
{
    private $pdo;
    private $clinicaId;

    public function __construct(PDO $pdo, int $clinicaId)
    {
        $this->pdo = $pdo;
        $this->clinicaId = $clinicaId;
    }

    /**
     * Calcula o faturamento bruto mensal para fins de regra de comissão.
     * Isola por clínica.
     */
    public function getFaturamentoBrutoMensal(string $dataInicio, string $dataFim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(ap.valor_procedimento) as total
            FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE a.data_atendimento BETWEEN ? AND ?
            AND a.clinica_id = ?
        ");
        $stmt->execute([$dataInicio, $dataFim, $this->clinicaId]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Deleta procedimentos pendentes que foram finalizados na sessão atual.
     */
    public function deletarProcedimentosPendentes(array $ids): void
    {
        // Filtra apenas IDs numéricos por segurança
        $idsSeguros = array_filter($ids, 'is_numeric');
        
        if (empty($idsSeguros)) {
            return;
        }

        // Para garantir que os procedimentos pertencem à clínica correta,
        // precisamos fazer um JOIN com a tabela de atendimentos na deleção.
        $inQuery = implode(',', array_fill(0, count($idsSeguros), '?'));
        
        // MariaDB suporta DELETE com JOIN
        $sql = "
            DELETE ap FROM atendimento_procedimentos ap
            JOIN atendimentos a ON ap.id_atendimento = a.id
            WHERE ap.id IN ($inQuery) AND a.clinica_id = ?
        ";
        
        $params = array_merge($idsSeguros, [$this->clinicaId]);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Insere o registro mestre de atendimento e retorna seu ID.
     */
    public function criarAtendimento(array $dados): int
    {
        $sql = "INSERT INTO atendimentos 
                (clinica_id, paciente_id, id_dentista, data_atendimento, valor_total, comissao_dentista, custo_auxiliar, valor_liquido_clinica, status_pagamento, url_arquivo) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $this->clinicaId,
            $dados['paciente_id'],
            $dados['id_dentista'],
            $dados['valor_total'] ?? 0.0,
            $dados['comissao_dentista'] ?? 0.0,
            $dados['custo_auxiliar'] ?? 0.0,
            $dados['valor_liquido_clinica'] ?? 0.0,
            $dados['status_pagamento'] ?? 'nao_aplicavel',
            $dados['url_arquivo'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Insere os procedimentos vinculados a um atendimento.
     */
    public function criarProcedimentosAtendimento(int $idAtendimento, array $procedimentos): void
    {
        $sql = "INSERT INTO atendimento_procedimentos 
                (id_atendimento, id_procedimento, quantidade, valor_procedimento, custo_auxiliar, local, descricao, status_execucao, natureza) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);

        foreach ($procedimentos as $proc) {
            $stmt->execute([
                $idAtendimento,
                $proc['id'],
                $proc['quantidade'] ?? 1,
                $proc['valor_total'] ?? 0.0,
                $proc['custo_auxiliar_manual'] ?? 0.0,
                $proc['local'] ?? null,
                $proc['descricao'] ?? null,
                $proc['status_execucao'] ?? 'pendente',
                $proc['natureza'] ?? null
            ]);
        }
    }
}
