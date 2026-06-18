<?php

namespace App\Controllers;

use App\Models\Paciente;
use PDO;

class PacienteController extends BaseController
{
    private $pacienteModel;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
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
     * Exibe o relatório por paciente com odontograma
     */
    public function relatorio()
    {
        if (!\is_admin() && !\is_dentista()) {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }

        $paciente_nome = isset($_GET['paciente_nome']) ? trim($_GET['paciente_nome']) : '';
        $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
        if ($pagina < 1) $pagina = 1;

        $viewData = [
            'paciente_nome' => $paciente_nome,
            'paciente' => null,
            'procedimentos' => [],
            'procedimentos_agrupados' => [],
            'procedimentos_todos' => [],
            'dente_status_color' => [],
            'totalPaginas' => 0,
            'pagina' => $pagina
        ];

        if (!empty($paciente_nome)) {
            $paciente = $this->pacienteModel->findByName($paciente_nome);

            if ($paciente) {
                $itensPorPagina = 20;
                $offset = ($pagina - 1) * $itensPorPagina;

                $procedimentos = $this->pacienteModel->getRelatorioProcedimentos($paciente['id'], $itensPorPagina, $offset);
                $totalRegistros = $this->pacienteModel->countRelatorioProcedimentos($paciente['id']);
                
                $viewData['paciente'] = $paciente;
                $viewData['procedimentos'] = $procedimentos;
                $viewData['totalPaginas'] = ceil($totalRegistros / $itensPorPagina);
                $viewData['dente_status_color'] = $this->pacienteModel->getOdontogramaStatus($paciente['id']);

                // Agrupa procedimentos por local
                $agrupados = [];
                foreach ($procedimentos as $proc) {
                    $local = $proc['local'];
                    if (!isset($agrupados[$local])) {
                        $agrupados[$local] = [];
                    }
                    $agrupados[$local][] = $proc;
                }

                $viewData['procedimentos_todos'] = $agrupados['Todos'] ?? [];
                unset($agrupados['Todos']);
                uksort($agrupados, 'strnatcmp');
                $viewData['procedimentos_agrupados'] = $agrupados;
            }
        }

        return $this->render('pacientes/relatorio', $viewData);
    }

    /**
     * API: Remove anexo de um procedimento
     */
    public function apiRemoverAnexo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['status' => 'error', 'message' => 'Método inválido.'], 405);
        }

        $idProcedimento = $_POST['id_procedimento'] ?? null;
        if (!$idProcedimento) {
            return $this->json(['status' => 'error', 'message' => 'ID não fornecido.'], 400);
        }

        try {
            $this->pacienteModel->removerAnexo((int)$idProcedimento);
            return $this->json(['status' => 'success', 'message' => 'Arquivo removido com sucesso!']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Remove um procedimento
     */
    public function apiRemoverProcedimento()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['status' => 'error', 'message' => 'Método inválido.'], 405);
        }

        $idProcedimento = $_POST['id_procedimento'] ?? null;
        if (!$idProcedimento) {
            return $this->json(['status' => 'error', 'message' => 'ID não fornecido.'], 400);
        }

        try {
            $this->pacienteModel->removerProcedimento((int)$idProcedimento);
            return $this->json(['status' => 'success', 'message' => 'Procedimento removido com sucesso!']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Salva arquivo anexo
     */
    public function salvarArquivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "pacientes/relatorio");
            exit;
        }

        $idProcedimento = $_POST['atendimento_procedimento_id'] ?? null;
        $pacienteNomeRedirect = $_POST['paciente_nome_redirect'] ?? '';

        if (!$idProcedimento || !isset($_FILES['arquivo_procedimento'])) {
            header("Location: " . BASE_URL . "pacientes/relatorio?paciente_nome=" . urlencode($pacienteNomeRedirect) . "&erro=Dados+inválidos");
            exit;
        }

        $file = $_FILES['arquivo_procedimento'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            header("Location: " . BASE_URL . "pacientes/relatorio?paciente_nome=" . urlencode($pacienteNomeRedirect) . "&erro=Extensão+não+permitida");
            exit;
        }

        $uploadDir = __DIR__ . '/../../public/uploads/procedimentos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('proc_') . '.' . $extension;
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            $relativeUrl = 'uploads/procedimentos/' . $fileName;
            $this->pacienteModel->salvarArquivo((int)$idProcedimento, $relativeUrl);
            header("Location: " . BASE_URL . "pacientes/relatorio?paciente_nome=" . urlencode($pacienteNomeRedirect) . "&msg=upload_sucesso");
        } else {
            header("Location: " . BASE_URL . "pacientes/relatorio?paciente_nome=" . urlencode($pacienteNomeRedirect) . "&erro=Falha+no+upload");
        }
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

    /**
     * Helper para normalizar nome em português (lowercase, sem acentos, sem múltiplos espaços)
     */
    private function normalizarNome(string $nome): string
    {
        $map = [
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','Ó'=>'O','Ò'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','Ç'=>'C'
        ];
        $nome = strtr($nome, $map);
        $nome = strtolower($nome);
        $nome = preg_replace('/[^a-z\s]/', '', $nome);
        return preg_replace('/\s+/', ' ', trim($nome));
    }

    /**
     * API: Verifica duplicados de paciente
     */
    public function apiVerificarDuplicado()
    {
        $nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
        $cpf = isset($_GET['cpf']) ? preg_replace('/[^0-9]/', '', $_GET['cpf']) : '';

        if (empty($nome)) {
            return $this->json(['status' => 'success', 'duplicados' => [], 'bloqueio_rigido' => false]);
        }

        // 1. Bloqueio Rígido: Se foi informado CPF e ele já existe no banco, retorna bloqueio rígido imediato
        if (!empty($cpf)) {
            $existente = $this->pacienteModel->getByCpf($cpf);
            if ($existente) {
                return $this->json([
                    'status' => 'success',
                    'bloqueio_rigido' => true,
                    'paciente' => $existente
                ]);
            }
        }

        // 2. Busca Suave: Levenshtein no PHP com distância <= 3
        $candidatos = $this->pacienteModel->getTodosParaDuplicados();
        $duplicados = [];
        $nomeNormalizado = $this->normalizarNome($nome);

        foreach ($candidatos as $candidato) {
            $candNomeNormalizado = $this->normalizarNome($candidato['nome']);
            $distancia = levenshtein($nomeNormalizado, $candNomeNormalizado);

            if ($distancia <= 3) {
                $candidato['distancia'] = $distancia;
                $duplicados[] = $candidato;
            }
        }

        // Ordenar duplicados pela menor distância Levenshtein
        usort($duplicados, function ($a, $b) {
            return $a['distancia'] <=> $b['distancia'];
        });

        return $this->json([
            'status' => 'success',
            'bloqueio_rigido' => false,
            'duplicados' => $duplicados
        ]);
    }
}
