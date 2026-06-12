<?php

namespace App\Controllers;

use App\Models\Procedimento;
use PDO;

class ProcedimentoController extends BaseController
{
    private Procedimento $procedimentoModel;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
        $this->procedimentoModel = new Procedimento($pdo, $clinica_id);
    }

    public function index(): void
    {
        try {
            $procedimentos = $this->procedimentoModel->getAll();
        } catch (\Exception $e) {
            $procedimentos = [];
            $_SESSION['feedback'] = [
                'type'    => 'error',
                'message' => 'Erro ao buscar procedimentos: ' . $e->getMessage(),
            ];
        }

        $this->render('procedimentos/index', ['procedimentos' => $procedimentos]);
    }

    public function editar(): void
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        $procedimento = $this->procedimentoModel->getById($id);
        if (!$procedimento) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Procedimento não encontrado.'];
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        $this->render('procedimentos/editar', ['procedimento' => $procedimento]);
    }

    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        $id        = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nome      = trim($_POST['nome'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $valorBase = !empty($_POST['valor_base']) ? floatval($_POST['valor_base']) : null;
        $tipo      = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;

        if (empty($nome) || empty($categoria)) {
            $_SESSION['feedback'] = [
                'type'    => 'error',
                'message' => 'Nome e categoria são obrigatórios.',
            ];
            header("Location: " . BASE_URL . ($id > 0 ? "procedimentos/editar?id=$id" : "procedimentos"));
            exit;
        }

        try {
            if ($id > 0) {
                $this->procedimentoModel->update($id, [
                    'nome'       => $nome,
                    'categoria'  => $categoria,
                    'valor_base' => $valorBase,
                    'tipo'       => $tipo,
                ]);
                $msg = 'Procedimento atualizado com sucesso!';
            } else {
                $this->procedimentoModel->create([
                    'nome'       => $nome,
                    'categoria'  => $categoria,
                    'valor_base' => $valorBase,
                    'tipo'       => $tipo,
                ]);
                $msg = 'Procedimento salvo com sucesso!';
            }

            $_SESSION['feedback'] = [
                'type'    => 'success',
                'message' => $msg,
            ];
        } catch (\Exception $e) {
            $_SESSION['feedback'] = [
                'type'    => 'error',
                'message' => 'Erro ao salvar procedimento: ' . $e->getMessage(),
            ];
        }

        header("Location: " . BASE_URL . "procedimentos");
        exit;
    }

    public function excluir(): void
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id <= 0) {
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        try {
            $this->procedimentoModel->delete($id);
            $_SESSION['feedback'] = [
                'type'    => 'success',
                'message' => 'Procedimento removido com sucesso!',
            ];
        } catch (\Exception $e) {
            $_SESSION['feedback'] = [
                'type'    => 'error',
                'message' => $e->getMessage(),
            ];
        }

        header("Location: " . BASE_URL . "procedimentos");
        exit;
    }
}
