<?php
/**
 * Autoloader PSR-4 Manual
 * Mapeia o namespace App\ para o diretório app/
 */

spl_autoload_register(function ($class) {
    // Prefixo do namespace
    $prefix = 'App\\';

    // Diretório base para o prefixo
    $base_dir = __DIR__ . '/';

    // Verifica se a classe usa o prefixo
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Pega o nome relativo da classe
    $relative_class = substr($class, $len);

    // Substitui o prefixo pelo diretório base, troca barras invertidas por separadores de diretório
    // e adiciona .php no final
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Se o arquivo existir, carrega-o
    if (file_exists($file)) {
        require $file;
    }
});
