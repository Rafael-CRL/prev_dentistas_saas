<?php

namespace App\Helpers;

/**
 * Utilitário para proteção contra Cross-Site Request Forgery (CSRF).
 */
class CsrfHelper
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Gera e armazena um token CSRF seguro na sessão, se ainda não existir.
     * 
     * @return string O token CSRF ativo.
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            // Gera um token forte (32 bytes = 64 caracteres hexadecimais)
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Valida se um token fornecido corresponde ao token da sessão.
     * 
     * @param string|null $token O token recebido via POST ou Header.
     * @return bool True se for válido, False caso contrário.
     */
    public static function validate(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // Usa hash_equals para evitar ataques de timing (Timing Attacks)
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Retorna a string HTML completa para um input hidden com o token.
     * 
     * @return string <input type="hidden" name="csrf_token" value="...">
     */
    public static function input(): string
    {
        $token = self::getToken();
        return sprintf('<input type="hidden" name="csrf_token" value="%s">', htmlspecialchars($token, ENT_QUOTES, 'UTF-8'));
    }
}
