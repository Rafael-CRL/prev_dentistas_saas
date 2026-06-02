<?php
require_once 'config/session.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    die("ID do atendimento não fornecido.");
}

$id_atendimento = $_GET['id'];

try {
    // Buscar dados do atendimento, paciente e dentista
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.data_atendimento,
            p.nome AS paciente_nome,
            p.cpf AS paciente_cpf,
            p.endereco,
            p.numero,
            p.bairro,
            p.cidade,
            p.estado,
            u.nome AS dentista_nome,
            SUM(ap.valor_procedimento) AS valor_total
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        JOIN usuarios u ON a.id_dentista = u.id AND u.perfil = 'dentista'
        LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        WHERE a.id = ? AND ap.status_execucao = 'feito'
        GROUP BY a.id
    ");
    $stmt->execute([$id_atendimento]);
    $atendimento = $stmt->fetch();

    if (!$atendimento) {
        die("Atendimento não encontrado.");
    }

    // Buscar procedimentos realizados
    $stmt_proc = $pdo->prepare("
        SELECT proc.nome, ap.valor_procedimento
        FROM atendimento_procedimentos ap
        JOIN procedimentos proc ON ap.id_procedimento = proc.id
        WHERE ap.id_atendimento = ? AND ap.status_execucao = 'feito'
    ");
    $stmt_proc->execute([$id_atendimento]);
    $procedimentos = $stmt_proc->fetchAll();

} catch (Exception $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}

// Dados da clínica (exemplo, idealmente viria de um config)
$clinica_nome = "Clínica Odontológica Prev Dentistas";
$clinica_endereco = "Rua União 1, Esquina com a Rua D - Atalaia, Ananindeua - PA, 67013-350";
$clinica_cnpj = "29.249738/0001-79";
$clinica_telefone = "(91) 98306-7459";

// Função para formatar o valor por extenso
function valorPorExtenso($valor) {
    // Implementação simples, pode ser trocada por uma biblioteca mais robusta
    $unidades = ["", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove"];
    $dezenas = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $centenas = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    
    $valor = number_format($valor, 2, ',', '.');
    list($reais, $centavos) = explode(',', $valor);

    $extenso = "";

    // Lógica para converter reais em extenso (simplificada)
    // ...

    $reais_texto = $reais > 1 ? "reais" : "real";
    $centavos_texto = $centavos > 1 ? "centavos" : "centavo";
    
    // Retorno simplificado
    return "$reais $reais_texto e $centavos $centavos_texto";
}

// Formata a data por extenso
$formatter = new IntlDateFormatter(
    'pt_BR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'America/Sao_Paulo',
    IntlDateFormatter::GREGORIAN,
    'd \'de\' MMMM \'de\' yyyy'
);
$data_atendimento_formatada = $formatter->format(strtotime($atendimento['data_atendimento']));

$dentista_nome = $atendimento['dentista_nome'] ?? 'Não informado';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            max-width: 800px;
        }
        #recibo {
            background: white;
            padding: 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .header-recibo {
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .header-recibo h1 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        .header-recibo p {
            margin: 0.3rem 0;
            color: #666;
        }
        .section {
            margin-bottom: 1.5rem;
        }
        .section h2 {
            font-size: 1.2rem;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .info-item strong {
            display: block;
            color: #555;
        }
        .procedimentos-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .procedimentos-table th,
        .procedimentos-table td {
            padding: 0.8rem;
            border: 1px solid #e9ecef;
            text-align: left;
        }
        .procedimentos-table th {
            background-color: #f8f9fa;
        }
        .total {
            text-align: right;
            margin-top: 1.5rem;
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--success-color);
        }
        .declaracao {
            margin-top: 2rem;
            text-align: justify;
        }
        .assinatura {
            margin-top: 3rem;
            text-align: center;
        }
        .assinatura-linha {
            border-bottom: 1px solid #333;
            width: 300px;
            margin: 0 auto;
        }
        .actions {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .btn {
            margin: 0 0.5rem;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .container {
                max-width: 100%;
                box-shadow: none;
            }
            .actions {
                display: none;
            }
            #recibo {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div id="recibo">
            <div class="header-recibo">
                <h1><?= htmlspecialchars($clinica_nome) ?></h1>
                <p><?= htmlspecialchars($clinica_endereco ?? '') ?></p>
                <p>CNPJ: <?= htmlspecialchars($clinica_cnpj) ?> | Telefone: <?= htmlspecialchars($clinica_telefone) ?></p>
            </div>

            <h2 style="text-align:center; margin-bottom: 2rem; font-weight: 500;">RECIBO DE PAGAMENTO</h2>

            <div class="section">
                <p class="declaracao">
                    Recebemos de <strong><?= htmlspecialchars($atendimento['paciente_nome']) ?></strong>,
                    CPF/CNPJ nº <strong><?= htmlspecialchars($atendimento['paciente_cpf'] ?? '') ?></strong>,
                    a importância de <strong>R$ <?= number_format($atendimento['valor_total'], 2, ',', '.') ?>
                    (<?= valorPorExtenso($atendimento['valor_total']) ?>)</strong>,
                    referente aos serviços odontológicos abaixo descritos.
                </p>
            </div>
            
            <div class="section">
                <h2>Serviços Prestados</h2>
                <table class="procedimentos-table">
                    <thead>
                        <tr>
                            <th>Procedimento</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($procedimentos as $proc): ?>
                        <tr>
                            <td><?= htmlspecialchars($proc['nome']) ?></td>
                            <td>R$ <?= number_format($proc['valor_procedimento'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total">
                    Total: R$ <?= number_format($atendimento['valor_total'], 2, ',', '.') ?>
                </div>
            </div>

            <div class="section">
                <p>
                    Para clareza, firmamos o presente.
                </p>
                <p style="text-align: right;">
                    <?= htmlspecialchars($atendimento['cidade'] ?? '') ?>, <?= $data_atendimento_formatada ?>.
                </p>
            </div>

            <div class="assinatura">
                <div class="assinatura-linha"></div>
                <p style="margin-top: 0.5rem;"><?= htmlspecialchars($clinica_nome ?? '') ?></p>
                <p style="font-size: 0.9rem; color: #666;"><?= htmlspecialchars($dentista_nome) ?></p>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
            <button id="download-btn" class="btn btn-secondary">Baixar PDF</button>
        </div>
    </div>

    <script>
        document.getElementById('download-btn').addEventListener('click', function () {
            const recibo = document.getElementById('recibo');
            const opt = {
                margin:       0.5,
                filename:     'recibo_<?= str_replace(' ', '_', $atendimento['paciente_nome']) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().from(recibo).set(opt).save();
        });
    </script>
</body>
</html>
