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
     * Implementação "Login Inteligente": Busca o usuário globalmente e retorna
     * seus dados, incluindo o clinica_id ao qual ele pertence.
     */
    public function authenticate($login) {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE login = ? LIMIT 1");
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getClinicaName(int $clinicaId): ?string {
        $stmt = $this->pdo->prepare("SELECT nome_fantasia FROM clinicas WHERE id = ? LIMIT 1");
        $stmt->execute([$clinicaId]);
        return $stmt->fetchColumn() ?: null;
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
