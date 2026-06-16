<?php

namespace App\Controllers;

use App\Helpers\CsrfHelper;

/**
 * Classe Base para todos os Controllers do sistema.
 * Centraliza funcionalidades comuns como renderização de views e proteção de rotas.
 */
abstract class BaseController
{
    /**
     * Construtor base: Intercepta requisições POST para validar o token CSRF.
     */
    public function __construct()
    {
        // Proteção transversal: Bloqueia qualquer POST sem token válido
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            
            if (!CsrfHelper::validate($token)) {
                // Previne continuação da execução
                http_response_code(403);
                $this->renderError('Acesso Negado', 'Falha na validação de segurança (Token CSRF inválido ou expirado). Volte e tente novamente.');
                exit;
            }
        }
    }

    /**
     * Renderiza uma mensagem de erro genérica (Útil para CSRF e bloqueios de acesso)
     */
    protected function renderError(string $titulo, string $mensagem): void
    {
        $header = __DIR__ . '/../../views/header.php';
        $footer = __DIR__ . '/../../views/footer.php';

        if (file_exists($header)) require_once $header;
        
        echo "<div class='container' style='margin-top: 50px; text-align: center; padding: 40px; background: #fff0f0; border: 1px solid #ffcdd2; border-radius: 8px;'>";
        echo "<h2 style='color: #d32f2f; margin-bottom: 15px;'><i class='fas fa-shield-alt'></i> {$titulo}</h2>";
        echo "<p style='color: #555; font-size: 1.1em;'>{$mensagem}</p>";
        echo "<a href='" . BASE_URL . "' class='btn btn-primary' style='margin-top: 20px;'>Voltar ao Início</a>";
        echo "</div>";

        if (file_exists($footer)) require_once $footer;
    }

    /**
     * Renderiza uma view com dados extraídos.
     * 
     * @param string $view Caminho da view relativo a app/Views/
     * @param array $data Dados a serem extraídos para a view
     * @return void
     */
    protected function render(string $view, array $data = []): void
    {
        // Garante que o token CSRF esteja sempre disponível nas views (caso queiram usar manualmente)
        $data['csrf_token'] = CsrfHelper::getToken();

        // Extrai as chaves do array como variáveis para a view
        extract($data);

        // Define caminhos base para os componentes de UI
        $header = __DIR__ . '/../Views/partials/header.php';
        $footer = __DIR__ . '/../Views/partials/footer.php';
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
     * Renderiza uma view sem o header e o footer padrão.
     */
    protected function renderRaw(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "Erro: View [{$view}] não encontrada em " . $viewFile;
        }
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
