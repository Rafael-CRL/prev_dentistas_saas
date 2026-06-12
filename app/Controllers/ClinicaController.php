<?php

namespace App\Controllers;

use App\Models\Clinica;
use PDO;

class ClinicaController extends BaseController
{
    private Clinica $clinicaModel;

    public function __construct(PDO $pdo, int $clinica_id)
    {
        parent::__construct();
        $this->clinicaModel = new Clinica($pdo, $clinica_id);
    }

    /**
     * Exibe o painel administrativo da clínica
     */
    public function painel(): void
    {
        $dadosClinica = $this->clinicaModel->getDados();
        $configuracoes = $this->clinicaModel->getConfiguracoes();
        $regraComissao = $this->clinicaModel->getRegraComissao();
        $taxasCartao = $this->clinicaModel->getTaxasCartao();

        $this->render('clinica/painel', [
            'clinica' => $dadosClinica,
            'configs' => $configuracoes,
            'comissao' => $regraComissao,
            'taxas' => $taxasCartao
        ]);
    }

    /**
     * Salva os dados institucionais
     */
    public function salvarDados(): void
    {
        $cnpj = trim($_POST['cnpj'] ?? '');
        $cnpjSanitizado = preg_replace('/\D/', '', $cnpj);

        $data = [
            'nome_fantasia' => trim($_POST['nome_fantasia'] ?? ''),
            'razao_social' => trim($_POST['razao_social'] ?? ''),
            'cnpj' => $cnpjSanitizado
        ];

        if (empty($data['nome_fantasia'])) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Nome Fantasia é obrigatório.'];
        } else {
            if ($this->clinicaModel->atualizarDados($data)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Dados atualizados com sucesso!'];
            } else {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao atualizar dados.'];
            }
        }

        header("Location: " . BASE_URL . "clinica/painel");
        exit;
    }

    /**
     * Salva configurações genéricas e regras de comissão
     */
    public function salvarConfiguracoes(): void
    {
        // Configurações Chave-Valor
        $configs = [
            'clinica_endereco' => trim($_POST['clinica_endereco'] ?? ''),
            'clinica_telefone' => trim($_POST['clinica_telefone'] ?? ''),
            'comissao_especializado' => floatval($_POST['comissao_especializado'] ?? 0),
            'comissao_canal' => floatval($_POST['comissao_canal'] ?? 0),
            'comissao_protese' => floatval($_POST['comissao_protese'] ?? 0),
        ];

        // Validação básica de percentuais
        foreach (['comissao_especializado', 'comissao_canal', 'comissao_protese'] as $key) {
            if ($configs[$key] < 0 || $configs[$key] > 100) {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => "O percentual em {$key} deve estar entre 0 e 100."];
                header("Location: " . BASE_URL . "clinica/painel");
                exit;
            }
        }

        $this->clinicaModel->salvarConfiguracoes($configs);

        // Regra de Comissão
        $regra = [
            'tipo' => $_POST['tipo_comissao'] ?? 'percentual',
            'valor_regra' => floatval($_POST['valor_regra'] ?? 0),
            'valor_meta' => floatval($_POST['valor_meta'] ?? 0),
            'percentual_bonus' => floatval($_POST['percentual_bonus'] ?? 0),
        ];

        // Validação da regra
        if ($regra['valor_regra'] < 0 || ($regra['tipo'] === 'percentual' && $regra['valor_regra'] > 100)) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => "Valor da regra inválido."];
            header("Location: " . BASE_URL . "clinica/painel");
            exit;
        }

        if ($this->clinicaModel->salvarRegraComissao($regra)) {
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Configurações e regras salvas!'];
        } else {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao salvar regras de comissão.'];
        }

        header("Location: " . BASE_URL . "clinica/painel");
        exit;
    }

    /**
     * Salva ou atualiza uma taxa de cartão
     */
    public function salvarTaxa(): void
    {
        $data = [
            'id' => $_POST['taxa_id'] ?? null,
            'bandeira' => trim($_POST['bandeira'] ?? 'default'),
            'modalidade' => $_POST['modalidade'] ?? 'credito',
            'parcelas' => intval($_POST['parcelas'] ?? 1),
            'taxa_percentual' => floatval($_POST['taxa_percentual'] ?? 0)
        ];

        // Validações
        if ($data['parcelas'] < 1 || $data['parcelas'] > 12) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Número de parcelas deve ser entre 1 e 12.'];
        } elseif ($data['taxa_percentual'] < 0 || $data['taxa_percentual'] > 100) {
            $_SESSION['feedback'] = ['type' => 'error', 'message' => 'A taxa deve estar entre 0% e 100%.'];
        } else {
            if ($this->clinicaModel->salvarTaxaCartao($data)) {
                $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Taxa de cartão salva com sucesso!'];
            } else {
                $_SESSION['feedback'] = ['type' => 'error', 'message' => 'Erro ao salvar taxa de cartão.'];
            }
        }

        header("Location: " . BASE_URL . "clinica/painel");
        exit;
    }

    /**
     * Exclui uma taxa de cartão
     */
    public function excluirTaxa(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "clinica/painel");
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->clinicaModel->excluirTaxaCartao($id);
            $_SESSION['feedback'] = ['type' => 'success', 'message' => 'Taxa removida com sucesso!'];
        }
        header("Location: " . BASE_URL . "clinica/painel");
        exit;
    }
}
