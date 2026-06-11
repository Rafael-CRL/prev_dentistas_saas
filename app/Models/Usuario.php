<?php
namespace App\Models;

use PDO;

class Usuario {
    private $pdo;
    private $clinica_id;

    public function __construct(PDO $pdo, $clinica_id) {
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    public function listarTodos() {
        $stmt = $this->pdo->prepare("SELECT id, nome, login, perfil FROM usuarios WHERE clinica_id = ? ORDER BY nome ASC");
        $stmt->execute([$this->clinica_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, nome, login, perfil, senha FROM usuarios WHERE id = ? AND clinica_id = ?");
        $stmt->execute([$id, $this->clinica_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar($dados) {
        if (isset($dados['id']) && !empty($dados['id'])) {
            return $this->atualizar($dados);
        } else {
            return $this->inserir($dados);
        }
    }

    private function inserir($dados) {
        $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (nome, login, senha, perfil, clinica_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dados['nome'],
            $dados['login'],
            $senhaHash,
            $dados['perfil'],
            $this->clinica_id
        ]);
    }

    private function atualizar($dados) {
        $sql = "UPDATE usuarios SET nome = ?, login = ?, perfil = ? WHERE id = ? AND clinica_id = ?";
        $params = [$dados['nome'], $dados['login'], $dados['perfil'], $dados['id'], $this->clinica_id];

        if (!empty($dados['senha'])) {
            $senhaHash = password_hash($dados['senha'], PASSWORD_BCRYPT);
            $sql = "UPDATE usuarios SET nome = ?, login = ?, perfil = ?, senha = ? WHERE id = ? AND clinica_id = ?";
            $params = [$dados['nome'], $dados['login'], $dados['perfil'], $senhaHash, $dados['id'], $this->clinica_id];
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function remover($id) {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ? AND clinica_id = ?");
        return $stmt->execute([$id, $this->clinica_id]);
    }

    public function atualizarPerfil($id, $nome, $senhaNova = null) {
        $sql = "UPDATE usuarios SET nome = ? WHERE id = ? AND clinica_id = ?";
        $params = [$nome, $id, $this->clinica_id];

        if ($senhaNova) {
            $senhaHash = password_hash($senhaNova, PASSWORD_BCRYPT);
            $sql = "UPDATE usuarios SET nome = ?, senha = ? WHERE id = ? AND clinica_id = ?";
            $params = [$nome, $senhaHash, $id, $this->clinica_id];
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function verificarLoginDuplicado($login, $idExcluir = null) {
        $sql = "SELECT id FROM usuarios WHERE login = ? AND clinica_id = ?";
        $params = [$login, $this->clinica_id];

        if ($idExcluir) {
            $sql .= " AND id <> ?";
            $params[] = $idExcluir;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function temAtendimentos($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE id_dentista = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() > 0;
    }
}
