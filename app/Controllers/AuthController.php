<?php
namespace App\Controllers;

use App\Models\AuthModel;
use PDO;

class AuthController extends BaseController {
    private $authModel;

    public function __construct(PDO $pdo) {
        $this->authModel = new AuthModel($pdo);
    }

    /**
     * Exibe a página de login.
     */
    public function showLogin() {
        if (isset($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "index.php");
            exit;
        }
        
        // Renderiza a view de login sem header/footer padrão, pois o login tem layout próprio
        $viewFile = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            echo "Erro: View de login não encontrada.";
        }
    }

    /**
     * Processa a tentativa de login.
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "login.php");
            exit;
        }

        $login = $_POST['login'] ?? '';
        $senha = $_POST['senha'] ?? '';

        $usuario = $this->authModel->authenticate($login);

        // Verificação de segurança rigorosa
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Prevenção de Session Fixation
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            $_SESSION['clinica_id'] = $usuario['clinica_id'];
            
            header("Location: " . BASE_URL . "index.php");
            exit;
        } else {
            header("Location: " . BASE_URL . "login.php?erro=1");
            exit;
        }
    }

    /**
     * Realiza o logout do usuário.
     */
    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}
