<?php
namespace App\Controllers;

use App\Models\Usuario;

class UsuarioController extends BaseController {
    private $usuarioModel;

    public function __construct($pdo) {
        parent::__construct();
        $this->usuarioModel = new Usuario($pdo, $_SESSION['clinica_id']);
    }

    public function index() {
        if (!\is_admin()) {
            header("Location: " . BASE_URL);
            exit;
        }

        $usuarios = $this->usuarioModel->listarTodos();
        $this->render('usuarios/index', ['usuarios' => $usuarios]);
    }

    public function editar() {
        if (!\is_admin()) {
            header("Location: " . BASE_URL);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "usuarios");
            exit;
        }

        $usuario = $this->usuarioModel->buscarPorId($id);
        if (!$usuario) {
            header("Location: " . BASE_URL . "usuarios");
            exit;
        }

        $this->render('usuarios/editar', ['usuario' => $usuario]);
    }

    public function salvar() {
        if (!\is_admin()) {
            header("Location: " . BASE_URL);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $nome = trim($_POST['nome']);
            $login = trim($_POST['login']);
            $senha = $_POST['senha'];
            $perfil = $_POST['perfil'];

            // Validação
            if (empty($nome) || empty($login) || empty($perfil)) {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Nome, login e perfil são obrigatórios.'];
                $redirect = $id ? "usuarios/editar?id=$id" : "usuarios";
                header("Location: " . BASE_URL . $redirect);
                exit;
            }

            if (!$id && empty($senha)) {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Senha é obrigatória para novos usuários.'];
                header("Location: " . BASE_URL . "usuarios");
                exit;
            }

            // Verificar login duplicado
            if ($this->usuarioModel->verificarLoginDuplicado($login, $id)) {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'O login informado já está em uso nesta clínica.'];
                $redirect = $id ? "usuarios/editar?id=$id" : "usuarios";
                header("Location: " . BASE_URL . $redirect);
                exit;
            }

            $dados = [
                'id' => $id,
                'nome' => $nome,
                'login' => $login,
                'senha' => $senha,
                'perfil' => $perfil
            ];

            if ($this->usuarioModel->salvar($dados)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Usuário salvo com sucesso!'];
                header("Location: " . BASE_URL . "usuarios");
            } else {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao salvar usuário.'];
                $redirect = $id ? "usuarios/editar?id=$id" : "usuarios";
                header("Location: " . BASE_URL . $redirect);
            }
            exit;
        }
    }

    public function remover() {
        if (!\is_admin()) {
            header("Location: " . BASE_URL);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "usuarios");
            exit;
        }

        // Não pode excluir a si mesmo
        if ($id == $_SESSION['usuario_id']) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Você não pode excluir seu próprio usuário.'];
            header("Location: " . BASE_URL . "usuarios");
            exit;
        }

        // Verificar dependências
        if ($this->usuarioModel->temAtendimentos($id)) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Não é possível excluir o usuário, pois ele está vinculado a atendimentos.'];
            header("Location: " . BASE_URL . "usuarios");
            exit;
        }

        if ($this->usuarioModel->remover($id)) {
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Usuário removido com sucesso!'];
            header("Location: " . BASE_URL . "usuarios");
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao remover usuário.'];
            header("Location: " . BASE_URL . "usuarios");
        }
        exit;
    }

    public function configuracoes() {
        $usuario = $this->usuarioModel->buscarPorId($_SESSION['usuario_id']);
        if (!$usuario) {
            session_destroy();
            header("Location: " . BASE_URL . "login");
            exit;
        }

        $this->render('usuarios/configuracoes', ['usuario' => $usuario]);
    }

    public function salvarConfiguracoes() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario_id = $_SESSION['usuario_id'];
            $nome = trim($_POST['nome']);
            $senha_antiga = $_POST['senha_antiga'] ?? '';
            $nova_senha = $_POST['nova_senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';

            if (empty($nome)) {
                header("Location: " . BASE_URL . "usuarios/configuracoes?erro=geral");
                exit;
            }

            $usuario_atual = $this->usuarioModel->buscarPorId($usuario_id);
            if (!$usuario_atual) {
                session_destroy();
                header("Location: " . BASE_URL . "login");
                exit;
            }

            $senhaNova = null;
            if (!empty($senha_antiga) || !empty($nova_senha) || !empty($confirmar_senha)) {
                if (empty($senha_antiga) || empty($nova_senha) || empty($confirmar_senha)) {
                    header("Location: " . BASE_URL . "usuarios/configuracoes?erro=campos_vazios");
                    exit;
                }

                if (!password_verify($senha_antiga, $usuario_atual['senha'])) {
                    header("Location: " . BASE_URL . "usuarios/configuracoes?erro=senha_incorreta");
                    exit;
                }

                if ($nova_senha !== $confirmar_senha) {
                    header("Location: " . BASE_URL . "usuarios/configuracoes?erro=senhas_nao_coincidem");
                    exit;
                }
                $senhaNova = $nova_senha;
            }

            if ($this->usuarioModel->atualizarPerfil($usuario_id, $nome, $senhaNova)) {
                $_SESSION['usuario_nome'] = $nome;
                header("Location: " . BASE_URL . "usuarios/configuracoes?msg=sucesso");
            } else {
                header("Location: " . BASE_URL . "usuarios/configuracoes?erro=geral");
            }
            exit;
        }
    }
}
