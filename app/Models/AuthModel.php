<?php
namespace App\Models;

use PDO;

class AuthModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca a clínica pelo código (ID) ou CNPJ (exato ou apenas números).
     */
    public function findClinicaId(string $identificador): ?int {
        $stmt = $this->pdo->prepare("
            SELECT id FROM clinicas 
            WHERE id = ? 
               OR cnpj = ? 
               OR REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '') = ? 
            LIMIT 1
        ");
        $normalized = preg_replace('/\D/', '', $identificador);
        $stmt->execute([$identificador, $identificador, $normalized]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Autentica um usuário pelo login e clinica_id.
     * Segurança SaaS: A busca é feita isolada por clínica.
     */
    public function authenticate($login, int $clinicaId) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE login = ? AND clinica_id = ? LIMIT 1");
        $stmt->execute([$login, $clinicaId]);
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
