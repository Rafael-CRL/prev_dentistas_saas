<?php
// actions/salvar_atendimento.php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once '../config/database.php';
require_once '../config/app.php'; // Para usar a BASE_URL
require_once 'Financeiro.php';

// Garantir o fuso horário correto para funções de data (NOW, date)
date_default_timezone_set('America/Sao_Paulo');

function send_json_error($message, $code = 400) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }
    echo json_encode(['sucesso' => false, 'erro' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // --- INÍCIO: Deletar procedimentos pendentes que foram finalizados agora ---
        if (!empty($_POST['procedimentos_a_deletar'])) {
            $idsParaDeletar = $_POST['procedimentos_a_deletar'];
            // Garante que são todos números para segurança
            $idsParaDeletar = array_filter($idsParaDeletar, 'is_numeric');
            
            if (count($idsParaDeletar) > 0) {
                $inQuery = implode(',', array_fill(0, count($idsParaDeletar), '?'));
                $stmtDelete = $pdo->prepare("DELETE FROM atendimento_procedimentos WHERE id IN ($inQuery)");
                $stmtDelete->execute($idsParaDeletar);
            }
        }
        // --- FIM: Deleção ---

        // --- INÍCIO: Lógica para Regra de Comissão (inalterada) ---

        $data_inicio_mes = date('Y-m-01 00:00:00');

        $data_fim_mes = date('Y-m-t 23:59:59');

        

        $stmtFaturamento = $pdo->prepare(

            "SELECT SUM(ap.valor_procedimento) as total

                FROM atendimento_procedimentos ap

                JOIN atendimentos a ON ap.id_atendimento = a.id

                WHERE a.data_atendimento BETWEEN ? AND ?"

        );

        $stmtFaturamento->execute([$data_inicio_mes, $data_fim_mes]);

        $faturamentoBrutoMensal = $stmtFaturamento->fetchColumn() ?? 0;

        // --- FIM: Lógica para Regra de Comissão ---

        

        // 1. Receber dados do formulário

        $pacienteId = !empty($_POST['paciente_id']) ? trim($_POST['paciente_id']) : null;

        $pacienteNome = trim($_POST['paciente_nome'] ?? '');

        $idDentista = $_POST['id_dentista'] ?? null;

        $procedimentosInput = $_POST['procedimentos'] ?? [];        //$pagamentosInput = $_POST['pagamentos'] ?? []; // Removido

        // Validações básicas
        if ((empty($pacienteId) && empty($pacienteNome)) || empty($idDentista) || empty($procedimentosInput['id'] ?? [])) {
            throw new Exception("Erro: Paciente, dentista e pelo menos um procedimento são obrigatórios.");
        }

        // Bloco para obter/criar o ID do paciente
        if (!$pacienteId) {
            // É um novo paciente, vamos cadastrá-lo apenas com o nome.
            // Os demais dados podem ser preenchidos na tela de edição de pacientes.
            $stmtPaciente = $pdo->prepare(
                "INSERT INTO pacientes (nome) VALUES (?)"
            );
            $stmtPaciente->execute([$pacienteNome]);
            $pacienteId = $pdo->lastInsertId();
        } else {
             // Paciente existente, apenas garantimos que o nome esteja correto para o upload
             $stmtNome = $pdo->prepare("SELECT nome FROM pacientes WHERE id = ?");
             $stmtNome->execute([$pacienteId]);
             $pacienteNome = $stmtNome->fetchColumn();
        }

        if (!$pacienteId) {
            throw new Exception("Falha ao obter o ID do paciente.");
        }

        // Lógica de Upload (inalterada, mas usando $pacienteNome de forma segura)
        $urlArquivo = null;
        if (isset($_FILES['raio_x_file']) && $_FILES['raio_x_file']['error'] === UPLOAD_ERR_OK) {
            // ... (a lógica de upload permanece a mesma, já é segura)
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($_FILES['raio_x_file']['tmp_name']);
            $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png'  => 'png', 'image/gif'  => 'gif', 'application/pdf' => 'pdf'];
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
            $urlArquivo = 'uploads/' . $fileName;
        }

        // 2. Processar e separar procedimentos
        $procedimentosFinalizados = [];
        $procedimentosPendentes = [];
        $valorBrutoTotal = 0;

        $stmtProc = $pdo->prepare("SELECT id, nome, categoria, valor_base FROM procedimentos WHERE id = ?");
        
        if (!empty($procedimentosInput['id']) && is_array($procedimentosInput['id'])) {
            foreach ($procedimentosInput['id'] as $key => $idProcedimento) {
                $quantidade = intval($procedimentosInput['quantidade'][$key]);
                if (!$idProcedimento || $quantidade <= 0) continue;

                $stmtProc->execute([$idProcedimento]);
                $procedimento = $stmtProc->fetch();
                if (!$procedimento) throw new Exception("Procedimento com ID $idProcedimento não encontrado.");

                $procParaSalvar = [
                    'id' => $idProcedimento,
                    'quantidade' => $quantidade,
                    'valor_total' => floatval($procedimentosInput['valor'][$key]),
                    'categoria' => $procedimento['categoria'],
                    'custo_auxiliar_manual' => isset($procedimentosInput['custo_auxiliar'][$key]) ? floatval($procedimentosInput['custo_auxiliar'][$key]) : 0.0,
                    'local' => trim($procedimentosInput['local'][$key]),
                    'descricao' => trim($procedimentosInput['descricao'][$key]),
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

        // Se não houver procedimentos finalizados, não há o que cobrar.
        // Mas podemos ter apenas procedimentos pendentes, que devem ser salvos.
        if (empty($procedimentosFinalizados) && empty($procedimentosPendentes)) {
            throw new Exception("Nenhum procedimento válido foi adicionado.");
        }

        // 4. Salvar o atendimento principal (APENAS se houver procedimentos finalizados)
        $idAtendimentoPrincipal = null;
        if (!empty($procedimentosFinalizados)) {
            $sqlAtendimento = "INSERT INTO atendimentos (paciente_id, id_dentista, data_atendimento, url_arquivo) VALUES (?, ?, NOW(), ?)";
            $stmtAtendimento = $pdo->prepare($sqlAtendimento);
            $stmtAtendimento->execute([$pacienteId, $idDentista, $urlArquivo]);
            $idAtendimentoPrincipal = $pdo->lastInsertId();

            $totalComissaoDentista = 0;
            $totalCustoAuxiliar = 0;
            $sqlProcAtendimento = "INSERT INTO atendimento_procedimentos (id_atendimento, id_procedimento, quantidade, valor_procedimento, custo_auxiliar, local, descricao, status_execucao, natureza) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtProcAtendimento = $pdo->prepare($sqlProcAtendimento);

            foreach ($procedimentosFinalizados as $proc) {
                $resComissao = Financeiro::calcularComissao($proc['valor_total'], $proc['categoria'], $faturamentoBrutoMensal, $proc['custo_auxiliar_manual'], $proc['natureza']);
                $comissaoProcedimento = $resComissao['dentista'];
                $custoAuxiliarProcedimento = $resComissao['auxiliar'] ?? 0.0;
                $totalComissaoDentista += $comissaoProcedimento;
                $totalCustoAuxiliar += $custoAuxiliarProcedimento;
                
                $stmtProcAtendimento->execute([
                    $idAtendimentoPrincipal, $proc['id'], $proc['quantidade'], $proc['valor_total'], 
                    $custoAuxiliarProcedimento, $proc['local'], $proc['descricao'], $proc['status_execucao'], $proc['natureza']
                ]);
            }

            $valorLiquidoClinica = $valorBrutoTotal - $totalComissaoDentista - $totalCustoAuxiliar;
            $statusPagamento = $valorBrutoTotal > 0 ? 'pendente' : 'pago';

            $sqlUpdAtendimento = "UPDATE atendimentos SET valor_total = ?, comissao_dentista = ?, custo_auxiliar = ?, valor_liquido_clinica = ?, status_pagamento = ? WHERE id = ?";
            $stmtUpdAtendimento = $pdo->prepare($sqlUpdAtendimento);
            $stmtUpdAtendimento->execute([$valorBrutoTotal, $totalComissaoDentista, $totalCustoAuxiliar, $valorLiquidoClinica, $statusPagamento, $idAtendimentoPrincipal]);
        }
        
        // 6. Salvar um novo atendimento para os procedimentos pendentes
        if (!empty($procedimentosPendentes)) {
            $sqlAtendimentoPendente = "INSERT INTO atendimentos (paciente_id, id_dentista, data_atendimento, valor_total, status_pagamento, url_arquivo) VALUES (?, ?, NOW(), 0, 'nao_aplicavel', ?)";
            $stmtAtendimentoPendente = $pdo->prepare($sqlAtendimentoPendente);
            // Reusa a URL do arquivo se houver, ou null.
            $stmtAtendimentoPendente->execute([$pacienteId, $idDentista, $urlArquivo]);
            $idAtendimentoPendente = $pdo->lastInsertId();

            $sqlProcPendente = "INSERT INTO atendimento_procedimentos (id_atendimento, id_procedimento, quantidade, valor_procedimento, custo_auxiliar, local, descricao, status_execucao, natureza) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtProcPendente = $pdo->prepare($sqlProcPendente);
            foreach ($procedimentosPendentes as $proc) {
                 $stmtProcPendente->execute([
                    $idAtendimentoPendente, $proc['id'], $proc['quantidade'], $proc['valor_total'],
                    $proc['custo_auxiliar_manual'], $proc['local'], $proc['descricao'], $proc['status_execucao'], $proc['natureza']
                ]);
            }
        }
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Atendimento lançado com sucesso!', 'redirectUrl' => BASE_URL . 'index.php']);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $post_data = json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        error_log("Erro em salvar_atendimento.php (Code: " . $e->getCode() . "): " . $e->getMessage() . "
Dados recebidos:
" . $post_data);
        send_json_error("Ocorreu um erro interno ao salvar o atendimento: " . $e->getMessage(), 500);
    }
}
?>