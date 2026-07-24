<?php
// config/session.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Otimização de Concorrência: se for uma requisição de API ou AJAX, liberamos a trava
// de escrita da sessão imediatamente. Isso evita que requisições assíncronas em segundo plano
// bloqueiem a navegação principal do usuário por outras telas.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isApi = (
    strpos($uri, 'api-') !== false ||
    strpos($uri, '/buscar') !== false ||
    strpos($uri, '/historico') !== false ||
    strpos($uri, '/pendentes') !== false ||
    strpos($uri, '/remover-') !== false ||
    strpos($uri, '/verificar-') !== false ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

if ($isApi && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
