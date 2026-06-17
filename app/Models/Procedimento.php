<?php

namespace App\Models;

use PDO;
use Exception;

class Procedimento
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM procedimentos WHERE clinica_id = ? ORDER BY nome ASC");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM procedimentos WHERE id = ? AND clinica_id = ?");
        $stmt->execute([$id, $this->clinica_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getListagemAtendimento(): array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome, categoria, valor_base, tipo FROM procedimentos WHERE clinica_id = ?");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO procedimentos (clinica_id, nome, categoria, valor_base, tipo)
                VALUES (:clinica_id, :nome, :categoria, :valor_base, :tipo)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':clinica_id' => $this->clinica_id,
            ':nome'       => $data['nome'],
            ':categoria'  => $data['categoria'],
            ':valor_base' => $data['valor_base'],
            ':tipo'       => $data['tipo'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE procedimentos SET nome = ?, categoria = ?, valor_base = ?, tipo = ? WHERE id = ? AND clinica_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome'],
            $data['categoria'],
            $data['valor_base'],
            $data['tipo'],
            $id,
            $this->clinica_id
        ]);
    }

    public function delete(int $id): bool
    {
        // Verifica se o procedimento pertence à clínica
        $stmtCheckProp = $this->pdo->prepare("SELECT id FROM procedimentos WHERE id = ? AND clinica_id = ?");
        $stmtCheckProp->execute([$id, $this->clinica_id]);
        if (!$stmtCheckProp->fetch()) {
            throw new Exception("Procedimento não encontrado ou não pertence a esta clínica.");
        }

        $stmtCheck = $this->pdo->prepare(
            "SELECT COUNT(*) FROM atendimento_procedimentos WHERE id_procedimento = ? AND clinica_id = ?"
        );
        $stmtCheck->execute([$id, $this->clinica_id]);

        if ((int) $stmtCheck->fetchColumn() > 0) {
            throw new Exception(
                "Não é possível excluir este procedimento pois ele já está vinculado a um ou mais atendimentos."
            );
        }

        $stmt = $this->pdo->prepare("DELETE FROM procedimentos WHERE id = ? AND clinica_id = ?");
        return $stmt->execute([$id, $this->clinica_id]);
    }
}
