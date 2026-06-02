<?php
// views/confirmar_pagamento.php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/controle_acesso.php';

// Acesso permitido para os perfis: proprietario (admin), recepcionista e dentista.
if (!is_admin() && !is_recepcionista() && !is_dentista()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
require_once 'header.php';

$paciente_id = $_GET['paciente_id'] ?? null;
$paciente_nome = '';
$atendimentos = [];
$valor_total = 0;
$ultimo_atendimento_id = null;


if ($paciente_id) {
    try {
        // Buscar nome do paciente
        $stmt = $pdo->prepare("SELECT nome FROM pacientes WHERE id = ?");
        $stmt->execute([$paciente_id]);
        $paciente_nome = $stmt->fetchColumn();

        // Buscar o ID do último atendimento PENDENTE do paciente
        $stmt_ultimo_atendimento = $pdo->prepare("SELECT id FROM atendimentos WHERE paciente_id = ? AND status_pagamento = 'pendente' ORDER BY id DESC LIMIT 1");
        $stmt_ultimo_atendimento->execute([$paciente_id]);
        $ultimo_atendimento_id = $stmt_ultimo_atendimento->fetchColumn();


        if ($ultimo_atendimento_id) {
            // Buscar procedimentos finalizados vinculados ao último atendimento
            $stmt = $pdo->prepare("
                SELECT ap.id, p.nome, ap.valor_procedimento, ap.quantidade
                FROM atendimento_procedimentos ap
                JOIN procedimentos p ON ap.id_procedimento = p.id
                WHERE ap.id_atendimento = ? AND ap.status_execucao = 'finalizado'
            ");
            $stmt->execute([$ultimo_atendimento_id]);
            $atendimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular valor total
            foreach ($atendimentos as $at) {
                $valor_total += $at['valor_procedimento'];
            }
        }

    } catch (Exception $e) {
        echo "<p class='error'>Erro ao buscar dados do paciente: " . $e->getMessage() . "</p>";
    }
}
?>


<div id="toast-notification" class="toast"></div>
<style>
    .toast { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 5px; color: white; font-size: 16px; z-index: 9999; opacity: 0; visibility: hidden; transition: opacity 0.5s, visibility 0.5s, transform 0.5s; transform: translateX(100%); }
    .toast.show { opacity: 1; visibility: visible; transform: translateX(0); }
    .toast.error { background-color: #dc3545; } /* red */
    .toast.success { background-color: #28a745; } /* green */
</style>
<div class="card">
    <h2>Confirmar Pagamento</h2>

    <form method="GET" action="confirmar_pagamento.php" class="form-busca-paciente">
        <fieldset>
            <legend>Buscar Paciente</legend>
            <div class="form-group">
                <label for="paciente_busca">Paciente</label>
                <div style="display: flex;">
                     <input type="text" id="paciente_busca" name="paciente_nome" placeholder="Digite o nome para buscar..." autocomplete="off" style="flex-grow: 1;" value="<?= htmlspecialchars($paciente_nome ?? '') ?>">
                    <input type="hidden" name="paciente_id" id="paciente_id" value="<?= htmlspecialchars($paciente_id ?? '') ?>">
                    <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Buscar</button>
                </div>
            </div>
        </fieldset>
    </form>


    <?php if ($paciente_id && $paciente_nome): ?>
        <form id="form-pagamento" action="<?= BASE_URL ?>actions/salvar_pagamento.php" method="POST">
             <input type="hidden" name="paciente_id" value="<?= htmlspecialchars($paciente_id ?? '') ?>">
            <input type="hidden" name="atendimento_id" value="<?= htmlspecialchars($ultimo_atendimento_id ?? '') ?>">


            <h3>Procedimentos Realizados</h3>
            <?php if (!empty($atendimentos)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Procedimento</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atendimentos as $at): ?>
                            <tr>
                                <td><?= htmlspecialchars($at['nome']) ?> (x<?= htmlspecialchars($at['quantidade']) ?>)</td>
                                <td>R$ <?= number_format($at['valor_procedimento'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum procedimento finalizado encontrado para este paciente no último atendimento.</p>
            <?php endif; ?>

            <?php
            // Debugging information
          /*  if ($ultimo_atendimento_id) {
                // Get the current gross monthly revenue
                $data_inicio_mes = date('Y-m-01 00:00:00');
                $data_fim_mes = date('Y-m-t 23:59:59');
                $stmtFaturamento = $pdo->prepare(
                    "SELECT SUM(ap.valor_procedimento) as total
                        FROM atendimento_procedimentos ap
                        JOIN atendimentos a ON ap.id_atendimento = a.id
                        WHERE a.data_atendimento BETWEEN ? AND ? AND a.status_pagamento = 'pago' AND ap.status_execucao = 'feito'"
                );
                $stmtFaturamento->execute([$data_inicio_mes, $data_fim_mes]);
                $faturamentoBrutoMensal = $stmtFaturamento->fetchColumn() ?? 0;

                // Add the current attendance value
                $faturamentoBrutoMensal += $valor_total;

                // Get the commission percentage
                $taxaComissao = ($faturamentoBrutoMensal >= 10000.00) ? 0.30 : 0.20;
                $comissaoDentista = $valor_total * $taxaComissao;
                $lucroClinica = $valor_total - $comissaoDentista;
            ?>
                <div class="card" style="margin-top: 2rem; background-color: #f0f0f0; padding: 1rem;">
                    <h3>Informações de Depuração</h3>
                    <p><strong>Faturamento Bruto Mensal (com este atendimento):</strong> R$ <?= number_format($faturamentoBrutoMensal, 2, ',', '.') ?></p>
                    <p><strong>Percentual de Comissão do Dentista:</strong> <?= ($taxaComissao * 100) ?>%</p>
                    <p><strong>Valor da Comissão do Dentista:</strong> R$ <?= number_format($comissaoDentista, 2, ',', '.') ?></p>
                    <p><strong>Lucro da Clínica (neste atendimento):</strong> R$ <?= number_format($lucroClinica, 2, ',', '.') ?></p>
                </div>
            <?php } */?>

            <div class="form-group">
                <label for="valor">Valor Bruto Total (R$)</label>
                <input type="number" step="0.01" id="valor" name="valor_total" required readonly value="<?= number_format($valor_total, 2, '.', '') ?>">
            </div>
            
            <div id="pagamentos_container">
                <!-- As linhas de pagamento serão adicionadas aqui -->
            </div>

            <div class="form-group">
                <button type="button" id="add_pagamento" class="btn btn-info">Adicionar Pagamento</button>
            </div>

            <div class="form-group">
                <p>Total Pago: <span id="total_pago">R$ 0,00</span></p>
                <p>Restante a Pagar: <span id="restante_pagar">R$ 0,00</span></p>
            </div>

            <button type="submit" class="btn btn-success" style="width: 100%;">Confirmar Pagamento</button>
        </form>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    // Autocomplete for patient search remains the same
    $("#paciente_busca").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "<?= BASE_URL ?>actions/buscar_paciente.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response($.map(data, function(item) {
                        return { label: item.nome, value: item.id };
                    }));
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            $("#paciente_busca").val(ui.item.label);
            $("#paciente_id").val(ui.item.value);
            $("#paciente_busca").closest('form').submit();
            return false;
        }
    });

    const valorTotalInput = document.getElementById('valor');
    const pagamentosContainer = document.getElementById('pagamentos_container');
    const addPagamentoButton = document.getElementById('add_pagamento');
    const totalPagoSpan = document.getElementById('total_pago');
    const restantePagarSpan = document.getElementById('restante_pagar');

    // Function to create a new payment row (remains the same)
    function createPagamentoRow() {
        const row = document.createElement('div');
        row.classList.add('pagamento-row');
        row.style.display = 'flex';
        row.style.marginBottom = '10px';

        const select = document.createElement('select');
        select.name = 'pagamentos[forma][]';
        select.required = true;
        const formas = ['dinheiro', 'pix', 'debito', 'credito'];
        formas.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f.charAt(0).toUpperCase() + f.slice(1);
            select.appendChild(opt);
        });

        const valorInput = document.createElement('input');
        valorInput.type = 'text'; // Changed to text to better handle comma entry
        valorInput.name = 'pagamentos[valor][]';
        valorInput.required = true;
        valorInput.placeholder = 'Valor';
        valorInput.style.marginLeft = '10px';
        
        const parcelasSelect = document.createElement('select');
        parcelasSelect.name = 'pagamentos[parcelas][]';
        parcelasSelect.style.display = 'none';
        parcelasSelect.style.marginLeft = '10px';
        for (let i = 1; i <= 12; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = `${i}x`;
            parcelasSelect.appendChild(opt);
        }

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remover';
        removeButton.classList.add('btn', 'btn-danger');
        removeButton.style.marginLeft = '10px';
        removeButton.onclick = () => {
            row.remove();
            updatePagamentos();
        };

        row.appendChild(select);
        row.appendChild(valorInput);
        row.appendChild(parcelasSelect);
        row.appendChild(removeButton);

        select.addEventListener('change', () => {
            parcelasSelect.style.display = select.value === 'credito' ? 'block' : 'none';
        });
        valorInput.addEventListener('input', updatePagamentos);
        return row;
    }

    // Function to update payment totals (remains mostly the same)
    function updatePagamentos() {
        let totalPago = 0;
        pagamentosContainer.querySelectorAll('.pagamento-row').forEach(row => {
            const valor = row.querySelector('input[type="text"]').value.replace(',', '.');
            if (valor && !isNaN(valor)) totalPago += parseFloat(valor);
        });

        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        const restante = valorTotal - totalPago;

        totalPagoSpan.textContent = `R$ ${totalPago.toFixed(2).replace('.', ',')}`;
        restantePagarSpan.textContent = `R$ ${restante.toFixed(2).replace('.', ',')}`;
        restantePagarSpan.style.color = Math.abs(restante) < 0.01 ? 'green' : 'red';
    }

    addPagamentoButton.addEventListener('click', () => {
        pagamentosContainer.appendChild(createPagamentoRow());
    });
    
    if(<?= $valor_total ?> > 0){
        pagamentosContainer.appendChild(createPagamentoRow());
    }
    updatePagamentos();

    // AJAX form submission
    $('#form-pagamento').on('submit', function(event) {
        event.preventDefault(); // Stop standard submission

        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        
        let totalPago = 0;
        $('input[name="pagamentos[valor][]"]').each(function() {
            const valor = $(this).val().replace(',', '.');
            if (valor && !isNaN(valor)) {
                totalPago += parseFloat(valor);
            }
        });

        const valorTotal = parseFloat($('#valor').val());

        if (Math.abs(totalPago - valorTotal) > 0.01) {
            alert('O valor pago não corresponde ao valor total.');
            return; // Stop submission if validation fails
        }

        submitButton.prop('disabled', true).text('Processando...');

        const formData = new FormData(form[0]);

        fetch(form.attr('action'), {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            // Try to parse JSON, but if it fails, get text for better error logging
            const isJson = response.headers.get('content-type')?.includes('application/json');
            const data = isJson ? await response.json() : await response.text();

            if (!response.ok) {
                 // For non-JSON responses, the text is the error
                const error = (data && data.erro) || (isJson ? 'Erro desconhecido' : data);
                throw new Error(error);
            }
            return data;
        })
        .then(result => {
            if (result.sucesso) {
                showToast(result.mensagem, 'success');
                setTimeout(() => {
                    window.location.href = '<?= BASE_URL ?>index.php'; // Redirect on success
                }, 1500);
            } else {
                showToast(result.erro || 'Ocorreu um erro desconhecido.', 'error');
                submitButton.prop('disabled', false).text('Confirmar Pagamento');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast(error.message || 'Erro de comunicação. Tente novamente.', 'error');
            submitButton.prop('disabled', false).text('Confirmar Pagamento');
        });
    });

    function showToast(message, type = 'success') {
        const toast = $('#toast-notification');
        
        toast.text(message).removeClass('success error').addClass(type).addClass('show');
        
        setTimeout(() => {
            toast.removeClass('show');
        }, 5000);
    }
});
</script>

<?php require_once 'footer.php'; ?>
