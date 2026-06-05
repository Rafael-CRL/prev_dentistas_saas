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

// Remove the BASE_URL from the URI so we get a clean relative path
$base_path = parse_url(BASE_URL, PHP_URL_PATH);
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}

// Normalize URI
if (empty($uri) || $uri === '/') {
    $uri = '/index.php';
}

// Se o arquivo existir na raiz (legado), permite o acesso (Transição)
$legacy_file_path = realpath(__DIR__ . '/../' . ltrim($uri, '/'));

// Security check: ensure the file is within the project root and is a valid file
if ($legacy_file_path && is_file($legacy_file_path) && strpos($legacy_file_path, realpath(__DIR__ . '/../')) === 0) {
    $extension = pathinfo($legacy_file_path, PATHINFO_EXTENSION);
    $static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf'];
    
    if (in_array(strtolower($extension), $static_extensions)) {
        // Let the web server handle static files directly
        return false; 
    }
    
    require_once $legacy_file_path;
    exit;
}

// Caso contrário, erro 404 (Futuramente passará pelo Roteador MVC)
http_response_code(404);
echo "Página não encontrada (MVC em construção).";
