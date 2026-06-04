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
$uri = str_replace(BASE_URL, '', $uri);

// Se a URI for vazia ou /, carrega a dashboard legada
if ($uri === '' || $uri === '/') {
    require_once __DIR__ . '/../index.php';
    exit;
}

// Se o arquivo existir na raiz (legado), permite o acesso (Transição)
if (file_exists(__DIR__ . '/../' . $uri) && is_file(__DIR__ . '/../' . $uri)) {
    require_once __DIR__ . '/../' . $uri;
    exit;
}

// Caso contrário, erro 404 (Futuramente passará pelo Roteador MVC)
http_response_code(404);
echo "Página não encontrada (MVC em construção).";
