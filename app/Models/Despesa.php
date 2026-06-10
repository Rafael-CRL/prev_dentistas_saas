<?php

namespace App\Models;

use PDO;

class Despesa
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    public function listarTodas(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM despesas WHERE clinica_id = ? ORDER BY data_despesa DESC");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function salvar(array $data): bool
    {
        if (isset($data['id']) && !empty($data['id'])) {
            $sql = "UPDATE despesas SET descricao = :descricao, valor = :valor, tipo = :tipo, data_despesa = :data_despesa 
                    WHERE id = :id AND clinica_id = :clinica_id";
            $params = [
                ':id' => $data['id'],
                ':clinica_id' => $this->clinica_id,
                ':descricao' => $data['descricao'],
                ':valor' => $data['valor'],
                ':tipo' => $data['tipo'],
                ':data_despesa' => $data['data_despesa']
            ];
        } else {
            $sql = "INSERT INTO despesas (clinica_id, descricao, valor, tipo, data_despesa) 
                    VALUES (:clinica_id, :descricao, :valor, :tipo, :data_despesa)";
            $params = [
                ':clinica_id' => $this->clinica_id,
                ':descricao' => $data['descricao'],
                ':valor' => $data['valor'],
                ':tipo' => $data['tipo'],
                ':data_despesa' => $data['data_despesa']
            ];
        }

        return $this->pdo->prepare($sql)->execute($params);
    }

    public function excluir(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM despesas WHERE id = ? AND clinica_id = ?");
        return $stmt->execute([$id, $this->clinica_id]);
    }

    public function obterTotalPeriodo(string $inicio, string $fim): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(valor) as total 
            FROM despesas 
            WHERE data_despesa BETWEEN ? AND ? 
            AND clinica_id = ?
        ");
        $stmt->execute([$inicio, $fim, $this->clinica_id]);
        return (float) ($stmt->fetchColumn() ?: 0.0);
    }

    public function obterContagemPeriodo(string $inicio, string $fim): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(id) 
            FROM despesas 
            WHERE data_despesa BETWEEN ? AND ? 
            AND clinica_id = ?
        ");
        $stmt->execute([$inicio, $fim, $this->clinica_id]);
        return (int) $stmt->fetchColumn();
    }

    public function listarPeriodoPaginado(string $inicio, string $fim, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM despesas 
            WHERE data_despesa BETWEEN :data_inicio AND :data_fim 
            AND clinica_id = :clinica_id 
            ORDER BY data_despesa DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':data_inicio', $inicio);
        $stmt->bindValue(':data_fim', $fim);
        $stmt->bindValue(':clinica_id', $this->clinica_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorData(string $data): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM despesas 
            WHERE data_despesa = ? 
            AND clinica_id = ? 
            ORDER BY descricao
        ");
        $stmt->execute([$data, $this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
