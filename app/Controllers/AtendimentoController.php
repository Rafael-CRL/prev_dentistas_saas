<?php
namespace App\Controllers;

use PDO;
use Exception;
use finfo;
use App\Models\Config;
use App\Models\Atendimento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Services\FinanceiroService;

class AtendimentoController extends BaseController
{
    private $atendimentoModel;
    private $pacienteModel;
    private $procedimentoModel;
    private $financeiroService;
    private $clinicaId;

    public function __construct(PDO $pdo, int $clinicaId)
    {
        parent::__construct($pdo);
        $this->clinicaId = $clinicaId;
        $this->atendimentoModel = new Atendimento($pdo, $clinicaId);
        $this->pacienteModel = new Paciente($pdo, $clinicaId);
        $this->procedimentoModel = new Procedimento($pdo, $clinicaId);
        
        $config = Config::getInstance($pdo, $clinicaId);
        $this->financeiroService = new FinanceiroService($config);
    }

    /**
     * Exibe o formulário de cadastro de atendimento
     */
    public function cadastrar()
    {
        // Busca dados para preencher os selects
        $stmtDentistas = $this->pdo->prepare("SELECT id, nome FROM usuarios WHERE perfil = 'dentista' AND clinica_id = ?");
        $stmtDentistas->execute([$this->clinicaId]);
        $dentistas = $stmtDentistas->fetchAll();

        $stmtProc = $this->pdo->prepare("SELECT id, nome, categoria, valor_base, tipo FROM procedimentos WHERE clinica_id = ?");
        $stmtProc->execute([$this->clinicaId]);
        $procedimentos = $stmtProc->fetchAll();

        return $this->render('atendimentos/cadastrar', [
            'dentistas' => $dentistas,
            'procedimentos' => $procedimentos
        ]);
    }

    /**
     * Verifica se o paciente possui pagamentos pendentes (API)
     */
    public function verificarPagamentoPendente()
    {
        $pacienteId = $_GET['paciente_id'] ?? null;

        if (!$pacienteId || !is_numeric($pacienteId)) {
            return $this->json(['sucesso' => false, 'erro' => 'ID do paciente inválido.'], 400);
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM atendimentos 
            WHERE paciente_id = ? 
            AND status_pagamento = 'pendente'
            AND clinica_id = ?
        ");
        $stmt->execute([$pacienteId, $this->clinicaId]);
        $count = $stmt->fetchColumn();

        return $this->json(['pendente' => $count > 0]);
    }

    /**
     * Processa o salvamento do atendimento (Transacional)
     */
    public function salvar()
    {
        // Garantir o fuso horário correto
        date_default_timezone_set('America/Sao_Paulo');

        try {
            $this->pdo->beginTransaction();

            // 1. Deletar procedimentos pendentes finalizados
            if (!empty($_POST['procedimentos_a_deletar'])) {
                $this->atendimentoModel->deletarProcedimentosPendentes($_POST['procedimentos_a_deletar']);
            }

            // 2. Calcular Faturamento Bruto para Comissão
            $dataInicioMes = date('Y-m-01 00:00:00');
            $dataFimMes = date('Y-m-t 23:59:59');
            $faturamentoBrutoMensal = $this->atendimentoModel->getFaturamentoBrutoMensal($dataInicioMes, $dataFimMes);

            // 3. Receber e Validar Dados Básicos
            $pacienteId = !empty($_POST['paciente_id']) ? trim($_POST['paciente_id']) : null;
            $pacienteNome = trim($_POST['paciente_nome'] ?? '');
            $idDentista = $_POST['id_dentista'] ?? null;
            $procedimentosInput = $_POST['procedimentos'] ?? [];

            if ((empty($pacienteId) && empty($pacienteNome)) || empty($idDentista) || empty($procedimentosInput['id'] ?? [])) {
                throw new Exception("Erro: Paciente, dentista e pelo menos um procedimento são obrigatórios.");
            }

            // 4. Obter/Criar Paciente
            if (!$pacienteId) {
                // TODO: Adicionar método criar() no App\Models\Paciente se não existir, ou usar query direta temporariamente
                $stmtPaciente = $this->pdo->prepare("INSERT INTO pacientes (nome, clinica_id) VALUES (?, ?)");
                $stmtPaciente->execute([$pacienteNome, $this->clinicaId]);
                $pacienteId = $this->pdo->lastInsertId();
            } else {
                 $stmtNome = $this->pdo->prepare("SELECT nome FROM pacientes WHERE id = ? AND clinica_id = ?");
                 $stmtNome->execute([$pacienteId, $this->clinicaId]);
                 $pacienteNome = $stmtNome->fetchColumn();
            }

            if (!$pacienteId) {
                throw new Exception("Falha ao obter o ID do paciente.");
            }

            // 5. Upload de Arquivo (Raio-X)
            $urlArquivo = $this->processarUploadArquivo($pacienteNome);

            // 6. Processar Procedimentos
            $procedimentosFinalizados = [];
            $procedimentosPendentes = [];
            $valorBrutoTotal = 0.0;

            if (!empty($procedimentosInput['id']) && is_array($procedimentosInput['id'])) {
                foreach ($procedimentosInput['id'] as $key => $idProcedimento) {
                    $quantidade = intval($procedimentosInput['quantidade'][$key]);
                    if (!$idProcedimento || $quantidade <= 0) continue;

                    // Busca detalhes do procedimento garantindo o tenant
                    $stmtProc = $this->pdo->prepare("SELECT id, nome, categoria, valor_base FROM procedimentos WHERE id = ? AND clinica_id = ?");
                    $stmtProc->execute([$idProcedimento, $this->clinicaId]);
                    $procedimento = $stmtProc->fetch();
                    
                    if (!$procedimento) throw new Exception("Procedimento com ID $idProcedimento não encontrado nesta clínica.");

                    $procParaSalvar = [
                        'id' => $idProcedimento,
                        'quantidade' => $quantidade,
                        'valor_total' => floatval($procedimentosInput['valor'][$key]),
                        'categoria' => $procedimento['categoria'],
                        'custo_auxiliar_manual' => isset($procedimentosInput['custo_auxiliar'][$key]) ? floatval($procedimentosInput['custo_auxiliar'][$key]) : 0.0,
                        'local' => trim($procedimentosInput['local'][$key] ?? ''),
                        'descricao' => trim($procedimentosInput['descricao'][$key] ?? ''),
                        'status_execucao' => trim($procedimentosInput['status_execucao'][$key]),
                        'natureza' => trim($procedimentosInput['natureza'][$key] ?? '')
                    ];

                    if ($procParaSalvar['status_execucao'] === 'finalizado') {
                        $procedimentosFinalizados[] = $procParaSalvar;
                        $valorBrutoTotal += $procParaSalvar['valor_total'];
                    } else {
                        $procedimentosPendentes[] = $procParaSalvar;
                    }
                }
            }

            if (empty($procedimentosFinalizados) && empty($procedimentosPendentes)) {
                throw new Exception("Nenhum procedimento válido foi adicionado.");
            }

            // 7. Salvar Atendimento Finalizado
            if (!empty($procedimentosFinalizados)) {
                $totalComissaoDentista = 0.0;
                $totalCustoAuxiliar = 0.0;

                // Processa cálculos financeiros usando o Service
                foreach ($procedimentosFinalizados as &$proc) {
                    $resComissao = $this->financeiroService->calcularComissao(
                        $proc['valor_total'], 
                        $proc['categoria'], 
                        $faturamentoBrutoMensal, 
                        $proc['custo_auxiliar_manual'], 
                        $proc['natureza']
                    );
                    $proc['comissao_calculada'] = $resComissao['dentista'];
                    $proc['custo_auxiliar_calculado'] = $resComissao['auxiliar'] ?? 0.0;
                    
                    $totalComissaoDentista += $proc['comissao_calculada'];
                    $totalCustoAuxiliar += $proc['custo_auxiliar_calculado'];
                }

                $valorLiquidoClinica = $valorBrutoTotal - $totalComissaoDentista - $totalCustoAuxiliar;
                $statusPagamento = $valorBrutoTotal > 0 ? 'pendente' : 'pago';

                // Cria o registro principal
                $idAtendimentoPrincipal = $this->atendimentoModel->criarAtendimento([
                    'paciente_id' => $pacienteId,
                    'id_dentista' => $idDentista,
                    'valor_total' => $valorBrutoTotal,
                    'comissao_dentista' => $totalComissaoDentista,
                    'custo_auxiliar' => $totalCustoAuxiliar,
                    'valor_liquido_clinica' => $valorLiquidoClinica,
                    'status_pagamento' => $statusPagamento,
                    'url_arquivo' => $urlArquivo
                ]);

                // Ajusta os campos para a inserção (merge do manual com o calculado, priorizando o final)
                // O Service decide se usa o manual ou calcula.
                $procedimentosFormatadosParaInsert = array_map(function($p) {
                    return array_merge($p, ['custo_auxiliar_manual' => $p['custo_auxiliar_calculado']]);
                }, $procedimentosFinalizados);

                $this->atendimentoModel->criarProcedimentosAtendimento($idAtendimentoPrincipal, $procedimentosFormatadosParaInsert);
            }
            
            // 8. Salvar Atendimento Pendente
            if (!empty($procedimentosPendentes)) {
                $idAtendimentoPendente = $this->atendimentoModel->criarAtendimento([
                    'paciente_id' => $pacienteId,
                    'id_dentista' => $idDentista,
                    'valor_total' => 0.0,
                    'comissao_dentista' => 0.0,
                    'custo_auxiliar' => 0.0,
                    'valor_liquido_clinica' => 0.0,
                    'status_pagamento' => 'nao_aplicavel',
                    'url_arquivo' => $urlArquivo
                ]);

                $this->atendimentoModel->criarProcedimentosAtendimento($idAtendimentoPendente, $procedimentosPendentes);
            }
            
            $this->pdo->commit();
            
            return $this->json([
                'sucesso' => true, 
                'mensagem' => 'Atendimento lançado com sucesso!', 
                'redirectUrl' => BASE_URL . 'index.php'
            ]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erro no AtendimentoController: " . $e->getMessage());
            return $this->json(['sucesso' => false, 'erro' => "Erro interno: " . $e->getMessage()], 500);
        }
    }

    /**
     * Isola a lógica de upload de arquivo
     */
    private function processarUploadArquivo(string $pacienteNome): ?string
    {
        if (isset($_FILES['raio_x_file']) && $_FILES['raio_x_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = realpath(__DIR__ . '/../../public/uploads/') . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['raio_x_file']['tmp_name']);
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg', 
                'image/png'  => 'png', 
                'image/gif'  => 'gif', 
                'application/pdf' => 'pdf'
            ];

            if (!array_key_exists($mimeType, $allowedMimeTypes)) {
                throw new Exception("Formato de arquivo não permitido.");
            }

            $extension = $allowedMimeTypes[$mimeType];
            $safePaciente = preg_replace('/[^a-zA-Z0-9-_]/', '', str_replace(' ', '_', $pacienteNome));
            $fileName = uniqid() . '_' . $safePaciente . '.' . $extension;
            $uploadFile = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['raio_x_file']['tmp_name'], $uploadFile)) {
                throw new Exception("Falha ao mover o arquivo enviado.");
            }

            return 'uploads/' . $fileName;
        }

        return null;
    }
}
