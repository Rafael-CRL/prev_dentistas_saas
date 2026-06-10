<?php

namespace App\Controllers;

use App\Models\Pagamento;
use App\Models\Despesa;
use App\Models\Atendimento;
use App\Models\Paciente;
use App\Models\Config;
use App\Services\FinanceiroService;
use PDO;

class FinanceiroController extends BaseController
{
    private PDO $pdo;
    private int $clinica_id;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->clinica_id = $clinica_id;
    }

    /**
     * Exibe a tela de confirmação de pagamento.
     */
    public function showPagar(): void
    {
        $paciente_id = $_GET['paciente_id'] ?? null;
        $atendimentoModel = new Atendimento($this->pdo, $this->clinica_id);
        $pacienteModel = new Paciente($this->pdo, $this->clinica_id);

        $data = [
            'paciente_id' => $paciente_id,
            'paciente' => null,
            'atendimentos' => [],
            'valor_total' => 0,
            'ultimo_atendimento_id' => null
        ];

        if ($paciente_id) {
            $data['paciente'] = $pacienteModel->buscarPorId($paciente_id);
            // Reutiliza lógica de buscar último atendimento pendente
            $data['ultimo_atendimento_id'] = $atendimentoModel->buscarUltimoPendente($paciente_id);

            if ($data['ultimo_atendimento_id']) {
                $procedimentos = $atendimentoModel->buscarProcedimentosFinalizados($data['ultimo_atendimento_id']);
                $data['atendimentos'] = $procedimentos;
                
                foreach ($procedimentos as $proc) {
                    $data['valor_total'] += $proc['valor_procedimento'];
                }
            }
        }

        $this->render('financeiro/pagar', $data);
    }

    /**
     * Processa a confirmação do pagamento via POST.
     */
    public function salvarPagamento(): void
    {
        $atendimento_id = $_POST['atendimento_id'] ?? null;
        $pagamentos = $_POST['pagamentos'] ?? null;

        if (!$atendimento_id || !$pagamentos) {
            $this->json(['sucesso' => false, 'erro' => 'Dados incompletos.'], 400);
        }

        try {
            $this->pdo->beginTransaction();

            $pagamentoModel = new Pagamento($this->pdo, $this->clinica_id);
            
            // 1. Registrar os pagamentos individuais
            $pagamentoModel->registrarPagamentos((int)$atendimento_id, $pagamentos);

            // 2. Atualizar status do atendimento para pago
            $pagamentoModel->atualizarStatusAtendimento((int)$atendimento_id, 'pago');

            $this->pdo->commit();
            $this->json(['sucesso' => true, 'mensagem' => 'Pagamento confirmado com sucesso!']);

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            $this->json(['sucesso' => false, 'erro' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gestão de Despesas.
     */
    public function despesas(): void
    {
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);
        $this->render('financeiro/despesas', [
            'despesas' => $despesaModel->listarTodas()
        ]);
    }

    public function salvarDespesa(): void
    {
        $despesaModel = new Despesa($this->pdo, $this->clinica_id);
        $sucesso = $despesaModel->salvar($_POST);

        if ($sucesso) {
            $_SESSION['feedback'] = "Despesa salva com sucesso!";
            header("Location: " . BASE_URL . "financeiro/despesas");
        } else {
            $_SESSION['feedback_erro'] = "Erro ao salvar despesa.";
            header("Location: " . BASE_URL . "financeiro/despesas");
        }
        exit;
    }

    public function excluirDespesa(): void
    {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $despesaModel = new Despesa($this->pdo, $this->clinica_id);
            $despesaModel->excluir((int)$id);
        }
        header("Location: " . BASE_URL . "financeiro/despesas");
        exit;
    }
}
