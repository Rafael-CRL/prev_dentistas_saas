<?php
/**
 * Front Controller
 * Ponto de entrada único da aplicação
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../config/app.php';

// Ajusta o include_path para que os requires legados continuem funcionando
set_include_path(get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/../'));

require_once 'config/session.php';
require_once 'config/database.php';

// Por enquanto, como ainda estamos em transição, 
// o Front Controller apenas serve como infraestrutura para o futuro roteador.
// As páginas legadas continuam funcionando na raiz por enquanto, 
// mas o objetivo é migrá-las para App\Controllers.

// Exemplo de roteamento simples (Placeholder)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the BASE_URL from the URI so we get a clean relative path like "/login.php" or "/actions/verificar_login.php"
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}

// Add leading slash if missing to normalize
if (empty($uri) || $uri[0] !== '/') {
    $uri = '/' . $uri;
}

// Se a URI for vazia ou /, carrega a dashboard legada (index.php da raiz)
if ($uri === '/' || $uri === '/index.php') {
    // If the user isn't logged in, index.php will redirect to login.php
    require_once __DIR__ . '/../index.php';
    exit;
}

// Se o arquivo existir na raiz (legado), permite o acesso (Transição)
$legacy_file_path = realpath(__DIR__ . '/../' . ltrim($uri, '/'));

// Check if the file exists and is actually inside our project directory (security check)
if ($legacy_file_path && is_file($legacy_file_path) && strpos($legacy_file_path, realpath(__DIR__ . '/../')) === 0) {
    // We cannot just require_once CSS/JS/Image files, they need to be served directly
    $extension = pathinfo($legacy_file_path, PATHINFO_EXTENSION);
    $static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
    
    if (in_array(strtolower($extension), $static_extensions)) {
        // Let the web server (Apache/PHP built-in server) handle static files natively
        return false; 
    }
    
    // For PHP files, execute them
    require_once $legacy_file_path;
    exit;
}

// Caso contrário, erro 404 (Futuramente passará pelo Roteador MVC)
http_response_code(404);
echo "Página não encontrada (MVC em construção). URI: " . htmlspecialchars($uri);
