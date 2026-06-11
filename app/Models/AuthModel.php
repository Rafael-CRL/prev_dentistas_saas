<?php
namespace App\Models;

use PDO;

class AuthModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /*
    // PRONTO PARA SER ATIVADO EM PRODUÇÃO MULTI-TENANT COM MÚLTIPLAS CLÍNICAS:
    // Método para buscar a clínica pelo código (ID) ou CNPJ (exato ou apenas números).
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
    */

    /**
     * Resolve o clinica_id da primeira clínica ativa no banco.
     * Comportamento temporário para manter o isolamento funcional sem exigir input do usuário.
     */
    public function findFirstActiveClinicaId(): ?int {
        $stmt = $this->pdo->prepare("SELECT id FROM clinicas WHERE status = 'ativo' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    /**
     * Autentica um usuário pelo login.
     * Comportamento temporário: busca apenas por login.
     * Código multi-tenant isolado comentado abaixo.
     */
    public function authenticate($login) {
        // COMPORTAMENTO TEMPORÁRIO (Simplificado):
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);

        /*
        // PRONTO PARA PRODUÇÃO MULTI-TENANT:
        // A query abaixo inclui clinica_id para buscar isoladamente por inquilino.
        // public function authenticate($login, int $clinicaId) {
        //     $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE login = ? AND clinica_id = ? LIMIT 1");
        //     $stmt->execute([$login, $clinicaId]);
        //     return $stmt->fetch(PDO::FETCH_ASSOC);
        // }
        */
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
