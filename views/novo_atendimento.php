<?php
// views/novo_atendimento.php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once 'header.php';
require_once '../config/controle_acesso.php';

if (!is_admin() && !is_dentista() && !is_recepcionista()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Busca dados para preencher os selects (Dentistas e Procedimentos)
try {
    $stmtDentistas = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'dentista'");
    $dentistas = $stmtDentistas->fetchAll();

    $stmtProc = $pdo->query("SELECT id, nome, categoria, valor_base, tipo FROM procedimentos");
    $procedimentos = $stmtProc->fetchAll();
} catch (Exception $e) {
    echo "<p class='error'>Erro ao carregar dados: " . $e->getMessage() . "</p>";
    $dentistas = []; $procedimentos = [];
}
?>

<div id="toast-notification" class="toast"></div>

<div class="card">
    <h2>Novo Lançamento de Atendimento</h2>
    <form id="form-atendimento" action="<?= BASE_URL ?>actions/salvar_atendimento.php" method="POST" enctype="multipart/form-data">
        
        <!-- SEÇÃO DE PACIENTE -->
        <fieldset>
            <legend>Dados do Paciente</legend>
            <input type="hidden" name="paciente_id" id="paciente_id">
            
            <div class="form-group">
                <label for="paciente_busca">Buscar Paciente</label>
                <div style="display: flex;">
                    <input type="text" id="paciente_busca" placeholder="Digite o nome ou CPF para buscar..." autocomplete="off" style="flex-grow: 1;">
                    <button type="button" class = "btn btn-danger" id="btn_limpar_paciente">Limpar Seleção</button>
                </div>
            </div>

            <div id="dados_paciente_container" style="display: none;">
                    <div class="form-group">
                        <label for="paciente_nome">Nome Completo</label>
                        <input type="text" name="paciente_nome" id="paciente_nome" required>
                    </div>
            </div>
        </fieldset>

        <!-- SEÇÃO DE ATENDIMENTO -->
        <fieldset>
            <legend>Dados do Atendimento</legend>
            <div class="form-group">
                <label for="dentista">Dentista Responsável</label>
                <select name="id_dentista" id="dentista" required>
                    <option value="">Selecione...</option>
                    <?php foreach($dentistas as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="procedimentos_container">
                <!-- As linhas de procedimento serão adicionadas aqui -->
            </div>

            <div class="form-group" id="raio_x_upload_container" style="display: none;">
                <label>Upload do Arquivo</label>
                <div class="drop-zone" id="drop-zone">
                    <input type="file" name="raio_x_file" id="raio_x_file" accept="image/*,.pdf" hidden>
                    <label for="raio_x_file" class="drop-zone__prompt">
                        <span class="drop-zone__icon">📂</span>
                        <p><strong>Arraste o Arquivo</strong> ou clique para buscar</p>
                        <span class="drop-zone__specs">PDF, PNG ou JPG</span>
                    </label>
                </div>
                <div id="file-name-display" style="margin-top: 8px; font-size: 0.9rem; color: #3b82f6; font-weight: bold;"></div>
            </div>

            <div class="form-group">
                <button type="button" id="add_procedimento" class="btn btn-info">Adicionar Procedimento</button>
            </div>

            <div class="form-group">
                <label for="valor">Valor Bruto Total (R$)</label>
                <input type="number" step="0.01" id="valor" required readonly placeholder="0.00">
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
        </fieldset>

        <button type="submit" class="btn btn-success" style="width: 100%;">Lançar Atendimento</button>
    </form>
</div>

<style>
    .grid-container { display: grid; grid-template-columns: repeat(12, 1fr); gap: 1rem; }
    .grid-col-2 { grid-column: span 2; }
    .grid-col-3 { grid-column: span 3; }
    .grid-col-4 { grid-column: span 4; }
    .grid-col-6 { grid-column: span 6; }
    fieldset { border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
    legend { font-weight: bold; padding: 0 0.5rem; }
    .procedimento-row, .pagamento-row { display: flex; align-items: center; margin-bottom: 10px; }
    .procedimento-row select, .procedimento-row input, .pagamento-row select, .pagamento-row input { margin-right: 10px; }

    #btn_limpar_paciente { width: 140px; margin-left: 10px; }
    .toast { position: fixed; top: 20px; right: 20px; padding: 15px 20px; border-radius: 5px; color: white; font-size: 16px; z-index: 9999; opacity: 0; visibility: hidden; transition: opacity 0.5s, visibility 0.5s, transform 0.5s; transform: translateX(100%); }
    .toast.show { opacity: 1; visibility: visible; transform: translateX(0); }
    .toast.error { background-color: #c0392b; }
    .toast.success { background-color: #27ae60; }
</style>

<!-- Adicionando jQuery e jQuery UI para o Autocomplete -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script>
$(document).ready(function() {
    const pacienteIdInput = $('#paciente_id');
    const pacienteBuscaInput = $('#paciente_busca');
    const dadosPacienteContainer = $('#dados_paciente_container');
    const btnLimparPaciente = $('#btn_limpar_paciente');

    // Mapeamento dos campos do formulário de paciente
    const pacienteFields = {
        nome: $('#paciente_nome'),
        cpf: $('#paciente_cpf'),
        telefone: $('#paciente_telefone'),
        email: $('#paciente_email'),
        cep: $('#paciente_cep'),
        data_nascimento: $('#paciente_data_nascimento'),
        endereco: $('#paciente_endereco'),
        numero: $('#paciente_numero'),
        bairro: $('#paciente_bairro'),
        cidade: $('#paciente_cidade'),
        estado: $('#paciente_estado')
    };

    // Função para habilitar/desabilitar e limpar campos de paciente
    function setPacienteFieldsState(enabled, clear = false) {
        dadosPacienteContainer.show();
        for (const key in pacienteFields) {
            pacienteFields[key].prop('readonly', !enabled);
            if (clear) {
                pacienteFields[key].val('');
            }
        }
    }

    // Inicializa o autocomplete no campo de busca
    pacienteBuscaInput.autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "<?= BASE_URL ?>actions/buscar_paciente.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    if (!data.length) {
                        response([{ label: 'Nenhum paciente encontrado. Cadastrar novo?', value: 'new' }]);
                    } else {
                        response($.map(data, function(item) {
                            return { label: `${item.nome} (${item.cpf || 'sem CPF'})`, value: item.id, patient: item };
                        }));
                    }
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            if (ui.item.value === 'new') {
                // Cadastrar novo paciente
                pacienteIdInput.val('');
                setPacienteFieldsState(true, true);
                pacienteFields.nome.val(pacienteBuscaInput.val()); // Preenche o nome com o que foi digitado
                pacienteBuscaInput.hide();
                btnLimparPaciente.show();
                return false;
            } else {
                // Selecionou um paciente existente
                const patient = ui.item.patient;
                pacienteIdInput.val(patient.id);
                
                // Preenche os campos
                for (const key in pacienteFields) {
                    if (pacienteFields[key].length) {
                        pacienteFields[key].val(patient[key] || '');
                    }
                }
                
                setPacienteFieldsState(false); // Bloqueia os campos
                pacienteBuscaInput.val(ui.item.patient.nome).prop('readonly', true);
                btnLimparPaciente.show();
            }
            return false; // Previne que o valor (ID) seja inserido no campo de busca
        }
    });

    // Ação do botão de limpar
    btnLimparPaciente.on('click', function() {
        pacienteIdInput.val('');
        pacienteBuscaInput.val('').prop('readonly', false).show().focus();
        setPacienteFieldsState(true, true); // Limpa e habilita os campos
        dadosPacienteContainer.hide();
        btnLimparPaciente.hide();
    });
    
    // Inicia com o formulário de dados do paciente oculto
    dadosPacienteContainer.hide();
    
    // Restante do seu script original (procedimentos, pagamentos, etc.)
    // ... (cole o restante do script a partir da definição de `procedimentos`)
    const procedimentos = <?= json_encode($procedimentos) ?>;
    const procContainer = document.getElementById('procedimentos_container');
    const addProcButton = document.getElementById('add_procedimento');
    const valorTotalInput = document.getElementById('valor');

    const pagamentosContainer = document.getElementById('pagamentos_container');
    const addPagamentoButton = document.getElementById('add_pagamento');
    const totalPagoSpan = document.getElementById('total_pago');
    const restantePagarSpan = document.getElementById('restante_pagar');

    const isAdmin = <?= is_admin() ? 'true' : 'false' ?>;
     function createProcedimentoRow() {
        const row = document.createElement('div');
        row.classList.add('procedimento-row');

        const select = document.createElement('select');
        select.name = 'procedimentos[id][]';
        select.required = true;
        
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Selecione...';
        select.appendChild(option);

        procedimentos.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.nome} (${p.categoria})`;
            opt.dataset.valor = p.valor_base;
            opt.dataset.categoria = p.categoria;
            opt.dataset.nome = p.nome;
            opt.dataset.tipo = p.tipo;
            select.appendChild(opt);
        });

        const quantidadeInput = document.createElement('input');
        quantidadeInput.type = 'number';
        quantidadeInput.name = 'procedimentos[quantidade][]';
        quantidadeInput.min = 1;
        quantidadeInput.value = 1;
        quantidadeInput.required = true;
        quantidadeInput.style.width = '80px';

        const custoProteticoInput = document.createElement('input');
        custoProteticoInput.type = 'number';
        custoProteticoInput.name = 'procedimentos[custo_protetico][]';
        custoProteticoInput.step = '0.01';
        custoProteticoInput.placeholder = 'Custo Protético (R$)';
        custoProteticoInput.style.display = 'none';
        custoProteticoInput.style.width = '150px';

        const valorPersonalizadoInput = document.createElement('input');
        valorPersonalizadoInput.type = 'number';
        valorPersonalizadoInput.name = 'procedimentos[valor_personalizado][]';
        valorPersonalizadoInput.step = '0.01';
        valorPersonalizadoInput.placeholder = 'Novo Valor (R$)';
        valorPersonalizadoInput.style.width = '120px';
        valorPersonalizadoInput.disabled = true; // Começa desabilitado

        const alterarValorButton = document.createElement('button');
        alterarValorButton.type = 'button';
        alterarValorButton.textContent = 'Alterar Valor';
        alterarValorButton.classList.add('btn', 'btn-info');
        alterarValorButton.style.marginLeft = '5px';
        if (!isAdmin) {
            alterarValorButton.style.display = 'none';
        }
        alterarValorButton.addEventListener('click', () => {
            valorPersonalizadoInput.disabled = !valorPersonalizadoInput.disabled;
            if (valorPersonalizadoInput.disabled) {
                valorPersonalizadoInput.value = ''; // Limpa o valor ao desabilitar
            } else {
                valorPersonalizadoInput.focus();
            }
            updateTotal();
        });


        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remover';
        removeButton.classList.add('btn', 'btn-danger');
        removeButton.addEventListener('click', () => {
            row.remove();
            updateTotal();
            checkRaioX();
        });

        row.appendChild(select);
        row.appendChild(quantidadeInput);
        row.appendChild(custoProteticoInput);
        row.appendChild(alterarValorButton);
        row.appendChild(valorPersonalizadoInput);
        row.appendChild(removeButton);

        select.addEventListener('change', () => {
            updateTotal();
            
            valorPersonalizadoInput.value = '';
            valorPersonalizadoInput.disabled = true;

            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.categoria === 'protese') {
                custoProteticoInput.style.display = 'inline-block';
                custoProteticoInput.required = true;
            } else {
                custoProteticoInput.style.display = 'none';
                custoProteticoInput.required = false;
                custoProteticoInput.value = '';
            }
        });
        quantidadeInput.addEventListener('input', updateTotal);
        valorPersonalizadoInput.addEventListener('input', updateTotal);

        return row;
    }

    function createPagamentoRow() {
        const row = document.createElement('div');
        row.classList.add('pagamento-row');

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
        valorInput.type = 'number';
        valorInput.name = 'pagamentos[valor][]';
        valorInput.step = '0.01';
        valorInput.required = true;
        valorInput.placeholder = 'Valor';
        
        const parcelasSelect = document.createElement('select');
        parcelasSelect.name = 'pagamentos[parcelas][]';
        parcelasSelect.style.display = 'none';
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
        removeButton.addEventListener('click', () => {
            row.remove();
            updatePagamentos();
        });

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

    function updateTotal() {
        let total = 0;
        const rows = procContainer.querySelectorAll('.procedimento-row');
        rows.forEach(row => {
            const select = row.querySelector('select[name="procedimentos[id][]"]');
            const quantidadeInput = row.querySelector('input[name="procedimentos[quantidade][]"]');
            const valorPersonalizadoInput = row.querySelector('input[name="procedimentos[valor_personalizado][]"]');
            
            const selectedOption = select.options[select.selectedIndex];
            const quantidade = quantidadeInput ? parseInt(quantidadeInput.value) : 0;
            
            if (selectedOption && selectedOption.dataset.valor && quantidade > 0) {
                let valorProcedimento = parseFloat(selectedOption.dataset.valor);

                if (valorPersonalizadoInput && !valorPersonalizadoInput.disabled && valorPersonalizadoInput.value !== '') {
                    valorProcedimento = parseFloat(valorPersonalizadoInput.value);
                }
                
                total += valorProcedimento * quantidade;
            }
        });
        valorTotalInput.value = total.toFixed(2);
        updatePagamentos();
        checkRaioX();
    }

    function updatePagamentos() {
        let totalPago = 0;
        const rows = pagamentosContainer.querySelectorAll('.pagamento-row');
        rows.forEach(row => {
            const valor = row.querySelector('input[type="number"]').value;
            if (valor) {
                totalPago += parseFloat(valor);
            }
        });

        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        const restante = valorTotal - totalPago;

        totalPagoSpan.textContent = `R$ ${totalPago.toFixed(2)}`;
        restantePagarSpan.textContent = `R$ ${restante.toFixed(2)}`;

        if (restante.toFixed(2) == 0) {
            restantePagarSpan.style.color = 'green';
        } else {
            restantePagarSpan.style.color = 'red';
        }
    }

    addProcButton.addEventListener('click', () => {
        procContainer.appendChild(createProcedimentoRow());
    });
    
    addPagamentoButton.addEventListener('click', () => {
        pagamentosContainer.appendChild(createPagamentoRow());
    });

    procContainer.appendChild(createProcedimentoRow());
    pagamentosContainer.appendChild(createPagamentoRow());
    updateTotal();
    updatePagamentos();

    const toast = document.getElementById('toast-notification');
    function showToast(message, type = 'error') {
        toast.textContent = message;
        toast.className = 'toast show ' + type;

        setTimeout(() => {
            toast.className = toast.className.replace('show', '');
        }, 5000);
    }
    const form = document.getElementById('form-atendimento');
    form.addEventListener('submit', async function(event) {
        event.preventDefault(); 

        const submitButton = form.querySelector('button[type="submit"]');
        
        if (!pacienteIdInput.val() && !pacienteFields.nome.val()) {
            showToast('É necessário selecionar ou cadastrar um paciente.', 'error');
            return;
        }

        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        let totalPago = 0;
        pagamentosContainer.querySelectorAll('.pagamento-row').forEach(row => {
            const valor = row.querySelector('input[name="pagamentos[valor][]"]').value;
            if (valor) {
                totalPago += parseFloat(valor);
            }
        });

        if (valorTotal <= 0) {
            showToast('O valor total do atendimento deve ser maior que zero.', 'error');
            return;
        }

        if (Math.abs(valorTotal - totalPago) > 0.01) {
            showToast(`A soma dos pagamentos (R$ ${totalPago.toFixed(2)}) não corresponde ao valor total (R$ ${valorTotal.toFixed(2)}).`, 'error');
            return;
        }
       
        submitButton.disabled = true;
        submitButton.textContent = 'Salvando...';

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.sucesso) {
                showToast(result.mensagem, 'success');
                setTimeout(() => {
                    window.location.href = result.redirectUrl || '<?= BASE_URL ?>index.php';
                }, 1500);
            } else {
                showToast(result.erro || 'Ocorreu um erro desconhecido.', 'error');
                submitButton.disabled = false;
                submitButton.textContent = 'Lançar Atendimento';
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showToast('Ocorreu um erro de comunicação. Verifique o console para detalhes.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = 'Lançar Atendimento';
        }
    });

    function checkRaioX() {
        const raioXContainer = document.getElementById('raio_x_upload_container');
        const raioXInput = document.getElementById('raio_x_file');
        let show = false;
        const rows = procContainer.querySelectorAll('.procedimento-row');
        rows.forEach(row => {
            const select = row.querySelector('select[name="procedimentos[id][]"]');
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.tipo == 1) {
                show = true;
            }
        });

        if (show) {
            raioXContainer.style.display = 'block';
        } else {
            raioXContainer.style.display = 'none';
            raioXInput.value = '';
        }
    }
    const dropZone = document.getElementById("drop-zone");
    const fileInput = document.getElementById("raio_x_file");
    const fileNameDisplay = document.getElementById("file-name-display");

    dropZone.addEventListener("click", () => fileInput.click());

    fileInput.addEventListener("change", () => {
        if (fileInput.files.length) {
            fileNameDisplay.textContent = `Arquivo selecionado: ${fileInput.files[0].name}`;
        }
    });

    ["dragover", "dragleave", "drop"].forEach(type => {
        dropZone.addEventListener(type, (e) => {
            e.preventDefault();
            if (type === "dragover") {
                dropZone.classList.add("drop-zone--over");
            } else {
                dropZone.classList.remove("drop-zone--over");
            }
        });
    });

    dropZone.addEventListener("drop", (e) => {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileNameDisplay.textContent = `Arquivo selecionado: ${e.dataTransfer.files[0].name}`;
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>