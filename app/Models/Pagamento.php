<?php

namespace App\Models;

use PDO;

class Pagamento
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Registra os múltiplos pagamentos de um atendimento.
     * 
     * @param int $atendimento_id
     * @param array $pagamentos Array de pagamentos ['forma' => [], 'valor' => [], 'parcelas' => []]
     * @return bool
     */
    public function registrarPagamentos(int $atendimento_id, array $pagamentos): bool
    {
        $sql = "INSERT INTO atendimento_pagamentos (clinica_id, id_atendimento, forma_pagamento, valor_pago, parcelas) 
                VALUES (:clinica_id, :atendimento_id, :forma, :valor, :parcelas)";
        
        $stmt = $this->pdo->prepare($sql);

        foreach ($pagamentos['forma'] as $index => $forma) {
            $valor = str_replace(',', '.', $pagamentos['valor'][$index]);
            $parcelas = $pagamentos['parcelas'][$index] ?? 1;

            $stmt->execute([
                ':clinica_id' => $this->clinica_id,
                ':atendimento_id' => $atendimento_id,
                ':forma' => $forma,
                ':valor' => $valor,
                ':parcelas' => $parcelas
            ]);
        }

        return true;
    }

    /**
     * Atualiza o status de pagamento do atendimento principal.
     */
    public function atualizarStatusAtendimento(int $atendimento_id, string $status = 'pago'): bool
    {
        $sql = "UPDATE atendimentos SET status_pagamento = :status WHERE id = :id AND clinica_id = :clinica_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => $atendimento_id,
            ':clinica_id' => $this->clinica_id
        ]);
    }
}
