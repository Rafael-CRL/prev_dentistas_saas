<?php

namespace App\Controllers;

use App\Models\Procedimento;
use PDO;

class ProcedimentoController extends BaseController
{
    private Procedimento $procedimentoModel;

    public function __construct(PDO $pdo, int $clinica_id)
    {
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

    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        $nome      = trim($_POST['nome'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $valorBase = !empty($_POST['valor_base']) ? floatval($_POST['valor_base']) : null;
        $tipo      = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;

        if (empty($nome) || empty($categoria)) {
            $_SESSION['feedback'] = [
                'type'    => 'error',
                'message' => 'Nome e categoria são obrigatórios.',
            ];
            header("Location: " . BASE_URL . "procedimentos");
            exit;
        }

        try {
            $this->procedimentoModel->create([
                'nome'       => $nome,
                'categoria'  => $categoria,
                'valor_base' => $valorBase,
                'tipo'       => $tipo,
            ]);

            $_SESSION['feedback'] = [
                'type'    => 'success',
                'message' => 'Procedimento salvo com sucesso!',
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
