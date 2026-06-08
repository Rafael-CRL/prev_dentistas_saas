<?php

namespace App\Controllers;

/**
 * Classe Base para todos os Controllers do sistema.
 * Centraliza funcionalidades comuns como renderização de views.
 */
abstract class BaseController
{
    /**
     * Renderiza uma view com dados extraídos.
     * 
     * @param string $view Caminho da view relativo a app/Views/
     * @param array $data Dados a serem extraídos para a view
     * @return void
     */
    protected function render(string $view, array $data = []): void
    {
        // Extrai as chaves do array como variáveis para a view
        extract($data);

        // Define caminhos base para os componentes de UI
        $header = __DIR__ . '/../../views/header.php';
        $footer = __DIR__ . '/../../views/footer.php';
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        // Renderização sequencial (Header -> Content -> Footer)
        if (file_exists($header)) require_once $header;
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "Erro: View [{$view}] não encontrada em " . $viewFile;
        }

        if (file_exists($footer)) require_once $footer;
    }

    /**
     * Retorna uma resposta JSON e encerra a execução.
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
