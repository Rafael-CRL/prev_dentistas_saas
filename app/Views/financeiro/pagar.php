<div class="card">
    <h2>Confirmar Pagamento</h2>

    <form method="GET" action="<?= BASE_URL ?>financeiro/pagar" class="form-busca-paciente">
        <fieldset>
            <legend>Buscar Paciente</legend>
            <div class="form-group">
                <label for="paciente_busca">Paciente</label>
                <div style="display: flex;">
                     <input type="text" id="paciente_busca" name="paciente_nome" placeholder="Digite o nome para buscar..." autocomplete="off" style="flex-grow: 1;" value="<?= htmlspecialchars($paciente['nome'] ?? '') ?>">
                    <input type="hidden" name="paciente_id" id="paciente_id" value="<?= htmlspecialchars($paciente_id ?? '') ?>">
                    <button type="submit" class="btn btn-primary" style="margin-left: 10px;">Buscar</button>
                </div>
            </div>
        </fieldset>
    </form>


    <?php if ($paciente_id && $paciente): ?>
        <form id="form-pagamento" action="<?= BASE_URL ?>financeiro/salvar-pagamento" method="POST">
            <?= \App\Helpers\CsrfHelper::input() ?>
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

            <div class="form-group">
                <label for="valor">Valor Bruto Total (R$)</label>
                <input type="number" step="0.01" id="valor" name="valor_total" required readonly value="<?= number_format($valor_total, 2, '.', '') ?>">
            </div>
            
            <div id="pagamentos_container">
                <!-- As linhas de pagamento serão adicionadas aqui via JS -->
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

<div id="toast-notification" class="toast"></div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(document).ready(function() {
    $("#paciente_busca").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "<?= BASE_URL ?>pacientes/buscar",
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

    function createPagamentoRow() {
        const row = document.createElement('div');
        row.classList.add('pagamento-row');
        row.style.display = 'flex';
        row.style.marginBottom = '10px';

        const select = document.createElement('select');
        select.name = 'pagamentos[forma][]';
        select.className = 'form-control';
        select.required = true;
        const formas = ['dinheiro', 'pix', 'debito', 'credito'];
        formas.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f.charAt(0).toUpperCase() + f.slice(1);
            select.appendChild(opt);
        });

        const valorInput = document.createElement('input');
        valorInput.type = 'text';
        valorInput.name = 'pagamentos[valor][]';
        valorInput.className = 'form-control';
        valorInput.required = true;
        valorInput.placeholder = 'Valor';
        valorInput.style.marginLeft = '10px';
        
        const parcelasSelect = document.createElement('select');
        parcelasSelect.name = 'pagamentos[parcelas][]';
        parcelasSelect.className = 'form-control';
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

    function updatePagamentos() {
        let totalPago = 0;
        $('.pagamento-row input[name="pagamentos[valor][]"]').each(function() {
            const valor = $(this).val().replace(',', '.');
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
    
    <?php if ($valor_total > 0): ?>
        pagamentosContainer.appendChild(createPagamentoRow());
    <?php endif; ?>
    updatePagamentos();

    $('#form-pagamento').on('submit', function(event) {
        event.preventDefault();
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        
        let totalPago = 0;
        $('input[name="pagamentos[valor][]"]').each(function() {
            const valor = $(this).val().replace(',', '.');
            if (valor && !isNaN(valor)) totalPago += parseFloat(valor);
        });

        const valorTotal = parseFloat($('#valor').val());

        if (Math.abs(totalPago - valorTotal) > 0.01) {
            alert('O valor pago não corresponde ao valor total.');
            return;
        }

        submitButton.prop('disabled', true).text('Processando...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(result) {
                if (result.sucesso) {
                    showToast(result.mensagem, 'success');
                    setTimeout(() => { window.location.href = '<?= BASE_URL ?>index.php'; }, 1500);
                } else {
                    showToast(result.erro || 'Ocorreu um erro.', 'error');
                    submitButton.prop('disabled', false).text('Confirmar Pagamento');
                }
            },
            error: function(xhr) {
                const res = xhr.responseJSON;
                showToast(res?.erro || 'Erro de comunicação.', 'error');
                submitButton.prop('disabled', false).text('Confirmar Pagamento');
            }
        });
    });

    function showToast(message, type = 'success') {
        const toast = $('#toast-notification');
        toast.text(message).removeClass('success error').addClass(type).addClass('show');
        setTimeout(() => { toast.removeClass('show'); }, 5000);
    }
});
</script>
