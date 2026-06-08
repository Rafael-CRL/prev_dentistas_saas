<?php
namespace App\Models;

use PDO;

class AuthModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Autentica um usuário pelo login.
     * Segurança SaaS: A busca é feita exclusivamente pelo login.
     * O clinica_id é obtido do registro encontrado para isolamento posterior.
     */
    public function authenticate($login) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE login = ? AND status = 1 LIMIT 1");
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Valida se um usuário pertence a uma clínica específica.
     * Útil para verificações de segurança adicionais.
     */
    public function validateUserClinic($userId, $clinicaId) {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND clinica_id = ?");
        $stmt->execute([$userId, $clinicaId]);
        return (bool) $stmt->fetch();
    }
}
