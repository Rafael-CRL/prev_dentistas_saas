<?php

namespace App\Controllers;

use App\Models\Paciente;
use PDO;

class PacienteController extends BaseController
{
    private $pacienteModel;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        $this->pacienteModel = new Paciente($pdo, $clinica_id);
    }

    /**
     * Lista pacientes
     */
    public function index()
    {
        $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($pagina < 1) $pagina = 1;
        $itensPorPagina = 10;
        $offset = ($pagina - 1) * $itensPorPagina;

        $pacientes = $this->pacienteModel->getAll($itensPorPagina, $offset, $busca);
        $totalRegistros = $this->pacienteModel->getCount($busca);
        $totalPaginas = ceil($totalRegistros / $itensPorPagina);

        // Carrega a View (ainda usando o sistema de include por enquanto, mas passando variáveis)
        $viewData = [
            'pacientes' => $pacientes,
            'totalPaginas' => $totalPaginas,
            'pagina' => $pagina,
            'busca' => $busca,
            'totalRegistros' => $totalRegistros
        ];

        return $this->render('pacientes/index', $viewData);
    }

    /**
     * Exibe formulário de edição
     */
    public function editar($id)
    {
        $paciente = $this->pacienteModel->getById((int)$id);
        if (!$paciente) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Paciente não encontrado.'];
            header("Location: " . BASE_URL . "pacientes");
            exit;
        }

        return $this->render('pacientes/editar', ['paciente' => $paciente]);
    }

    /**
     * Salva um paciente (novo ou existente)
     */
    public function salvar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "pacientes");
            exit;
        }

        $data = [
            'id' => $_POST['paciente_id'] ?? null,
            'nome' => $_POST['paciente_nome'] ?? '',
            'cpf' => !empty($_POST['paciente_cpf']) ? $_POST['paciente_cpf'] : null,
            'data_nascimento' => !empty($_POST['paciente_data_nascimento']) ? $_POST['paciente_data_nascimento'] : null,
            'email' => !empty($_POST['paciente_email']) ? $_POST['paciente_email'] : null,
            'telefone' => !empty($_POST['paciente_telefone']) ? $_POST['paciente_telefone'] : null,
            'cep' => !empty($_POST['paciente_cep']) ? $_POST['paciente_cep'] : null,
            'endereco' => !empty($_POST['paciente_endereco']) ? $_POST['paciente_endereco'] : null,
            'numero' => !empty($_POST['paciente_numero']) ? $_POST['paciente_numero'] : null,
            'bairro' => !empty($_POST['paciente_bairro']) ? $_POST['paciente_bairro'] : null,
            'cidade' => !empty($_POST['paciente_cidade']) ? $_POST['paciente_cidade'] : null,
            'estado' => !empty($_POST['paciente_estado']) ? $_POST['paciente_estado'] : null,
        ];

        try {
            $this->pacienteModel->save($data);
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Paciente salvo com sucesso!'];
            header("Location: " . BASE_URL . "pacientes");
            exit;
        } catch (\Exception $e) {
            $msg = ($e->getCode() == '23000') ? "Erro: Já existe um paciente com este CPF nesta clínica." : "Erro ao salvar: " . $e->getMessage();
            $_SESSION['feedback'] = ['type' => 'error', 'message' => $msg];
            
            $redirect = $data['id'] ? "pacientes/editar?id=" . $data['id'] : "pacientes";
            header("Location: " . BASE_URL . $redirect);
            exit;
        }
    }

    /**
     * Exclui um paciente
     */
    public function excluir($id)
    {
        try {
            $this->pacienteModel->delete((int)$id);
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Paciente excluído com sucesso!'];
        } catch (\Exception $e) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => $e->getMessage()];
        }

        header("Location: " . BASE_URL . "pacientes");
        exit;
    }

    /**
     * Busca rápida para AJAX
     */
    public function apiBuscar()
    {
        $term = $_GET['term'] ?? '';

        if (strlen(trim($term)) < 2) {
            return $this->json([]);
        }

        $results = $this->pacienteModel->search($term);
        return $this->json($results);
    }

    /**
     * Busca histórico do paciente via AJAX
     */
    public function apiHistorico()
    {
        $pacienteId = $_GET['paciente_id'] ?? null;

        if (!$pacienteId) {
            return $this->json(['erro' => 'ID do paciente não fornecido.'], 400);
        }

        try {
            $historico = $this->pacienteModel->getHistorico((int)$pacienteId);
            return $this->json($historico);
        } catch (\Exception $e) {
            return $this->json(['erro' => 'Erro ao buscar histórico.'], 500);
        }
    }

    /**
     * Busca procedimentos pendentes via AJAX
     */
    public function apiPendentes()
    {
        $pacienteId = $_GET['paciente_id'] ?? null;

        if (!$pacienteId) {
            return $this->json([]);
        }

        try {
            $pendentes = $this->pacienteModel->getPendentes((int)$pacienteId);
            return $this->json($pendentes);
        } catch (\Exception $e) {
            return $this->json(['erro' => 'Erro ao buscar pendências.'], 500);
        }
    }
}
