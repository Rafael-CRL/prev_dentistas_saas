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
    <form id="form-atendimento" action="<?= BASE_URL ?>actions/salvar_atendimento2.php" method="POST" enctype="multipart/form-data">
        
        <!-- SEÇÃO DE PACIENTE -->
        <fieldset>
            <legend>Dados do Paciente</legend>
            <input type="hidden" name="paciente_id" id="paciente_id">
            
            <div class="form-group">
                <label for="paciente_busca">Buscar Paciente</label>
                <div style="display: flex;">
                    <input type="text" id="paciente_busca" name="paciente_nome" placeholder="Digite o nome para buscar ou cadastrar..." autocomplete="off" style="flex-grow: 1;">
                    <button type="button" class = "btn btn-danger" id="btn_limpar_paciente">Limpar Seleção</button>
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

            <div class="card">
                <h2>Odontograma do Paciente</h2>
                <p>Selecione um dente para registrar o tratamento:</p>

                <div class="canvas-container">
                    <img src="../assets/img/odontograma.png" usemap="#image-map" class="img-odontograma">
                    <map name="image-map">
                        <!-- Arcada Superior -->
                        <area target="" onclick="abrirModal(18, 'Arcada Superior')" alt="Molar 18" id="d18" title="3º Molar" coords="53,251,99,157" shape="rect">
                        <area target="" onclick="abrirModal(17, 'Arcada Superior')" alt="Molar 17" id="d17" title="2º Molar" coords="147,156,103,249" shape="rect">
                        <area target="" onclick="abrirModal(16, 'Arcada Superior')" alt="Molar 16" title="Molar 16" coords="207,155,151,246" shape="rect">
                        <area target="" onclick="abrirModal(15, 'Arcada Superior')" alt="Premolar 15" title="Premolar 15" coords="241,152,209,242" shape="rect">
                        <area target="" onclick="abrirModal(14, 'Arcada Superior')" alt="Premolar 14" title="Premolar 14" coords="274,149,246,241" shape="rect">
                        <area target="" onclick="abrirModal(13, 'Arcada Superior')" alt="Canino 13" title="Canino 13" coords="314,148,277,238" shape="rect">
                        <area target="" onclick="abrirModal(12, 'Arcada Superior')" alt="Inciso 12" title="Inciso 12" coords="352,152,317,243" shape="rect">
                        <area target="" onclick="abrirModal(11, 'Arcada Superior')" alt="Inciso 11" title="Inciso 11" coords="397,153,355,246" shape="rect">
                        <area target="" onclick="abrirModal(21, 'Arcada Superior')" alt="Inciso 21" title="Inciso 21" coords="442,154,403,244" shape="rect">
                        <area target="" onclick="abrirModal(22, 'Arcada Superior')" alt="Inciso 22" title="Inciso 22" coords="479,153,446,243" shape="rect">
                        <area target="" onclick="abrirModal(23, 'Arcada Superior')" alt="Canino 23" title="Canino 23" coords="521,142,481,243" shape="rect">
                        <area target="" onclick="abrirModal(24, 'Arcada Superior')" alt="Premolar 24" title="Premolar 24" coords="561,146,525,239" shape="rect">
                        <area target="" onclick="abrirModal(25, 'Arcada Superior')" alt="Premolar 25" title="Premolar 25" coords="590,146,564,237" shape="rect">
                        <area target="" onclick="abrirModal(26, 'Arcada Superior')" alt="Molar 26" title="Molar 26" coords="648,148,593,238" shape="rect">
                        <area target="" onclick="abrirModal(27, 'Arcada Superior')" alt="Molar 27" title="Molar 27" coords="703,151,653,239" shape="rect">
                        <area target="" onclick="abrirModal(28, 'Arcada Superior')" alt="Molar 28" id="d28" title="3º Molar" coords="741,149,705,241" shape="rect">

                        <!-- Arcada Inferior -->
                        <area target="" onclick="abrirModal(48, 'Arcada Inferior')" alt="Molar 48" id="d48" title="Molar 48" coords="51,285,103,360" shape="rect">
                        <area target="" onclick="abrirModal(47, 'Arcada Inferior')" alt="Molar 47" title="Molar 47" coords="109,284,160,363" shape="rect">
                        <area target="" onclick="abrirModal(46, 'Arcada Inferior')" alt="Molar 46" title="Molar 46" coords="167,281,219,363" shape="rect">
                        <area target="" onclick="abrirModal(45, 'Arcada Inferior')" alt="Premolar 45" title="Premolar 45" coords="221,278,258,378" shape="rect">
                        <area target="" onclick="abrirModal(44, 'Arcada Inferior')" alt="Premolar 44" title="Premolar 44" coords="260,275,296,390" shape="rect">
                        <area target="" onclick="abrirModal(43, 'Arcada Inferior')" alt="Canino 43" title="Canino 43" coords="298,276,336,384" shape="rect">
                        <area target="" onclick="abrirModal(42, 'Arcada Inferior')" alt="Inciso 42" title="Inciso 42" coords="338,275,368,384" shape="rect">
                        <area target="" onclick="abrirModal(41, 'Arcada Inferior')" alt="Inciso 41" title="Inciso 41" coords="370,276,395,383" shape="rect">
                        <area target="" onclick="abrirModal(31, 'Arcada Inferior')" alt="Inciso 31" title="Inciso 31" coords="398,275,426,380" shape="rect">
                        <area target="" onclick="abrirModal(32, 'Arcada Inferior')" alt="Inciso 32" title="Inciso 32" coords="428,275,454,382" shape="rect">
                        <area target="" onclick="abrirModal(33, 'Arcada Inferior')" alt="Canino 33" title="Canino 33" coords="456,274,493,391" shape="rect">
                        <area target="" onclick="abrirModal(34, 'Arcada Inferior')" alt="Premolar 34" title="Premolar 34" coords="496,274,531,383" shape="rect">
                        <area target="" onclick="abrirModal(35, 'Arcada Inferior')" alt="Premolar 35" title="Premolar 35" coords="534,274,571,379" shape="rect">
                        <area target="" onclick="abrirModal(36, 'Arcada Inferior')" alt="Molar 36" title="Molar 36" coords="575,274,636,384" shape="rect">
                        <area target="" onclick="abrirModal(37, 'Arcada Inferior')" alt="Molar 37" title="Molar 37" coords="640,274,688,384" shape="rect">
                        <area target="" onclick="abrirModal(38, 'Arcada Inferior')" alt="Molar 38" title="Molar 38" coords="694,272,742,375" shape="rect">

                        <!-- Geral -->
                        <area target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="85,31,727,83" shape="rect">
                        <area target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="72,449,727,498" shape="rect">
                    </map>
                </div>
            </div>

            <div id="procedimentos_pendentes_container" style="margin-top: 1rem;">
                <!-- Procedimentos pendentes carregados via AJAX virão para cá -->
            </div>

            <div id="procedimentos_adicionados_container">
                <h3>Procedimentos Adicionados</h3>
                <!-- Procedimentos do modal virão para cá -->
            </div>

            <div id="total-procedimentos-container" style="text-align: right; margin-top: 10px; font-size: 1.5em;">
                <strong>Total a Pagar:</strong> <span id="total-procedimentos-valor">R$ 0,00</span>
            </div>

        <div id="procedimentos_a_deletar_container"></div>

        <button type="submit" class="btn btn-success" style="width: 100%;">Lançar Atendimento</button>
    </form>

<div id="modalTratamento" class="modal">
    <div class="modal-content">
        <h3><span id="modal-title"></span></h3>
        <form id="form-tratamento-modal">
            <input type="hidden" name="dente_id" id="inputDente">
            <input type="hidden" name="arcada" id="inputArcada">

            <div id="procedimentos-modal-container">
                <!-- Linhas de procedimento do modal serão adicionadas aqui -->
            </div>

            <button type="button" id="add-procedimento-modal" class="btn btn-info">Adicionar Procedimento</button>

            <div style="text-align: right; margin-top: 10px; font-size: 1.2em;">
                <strong>Total:</strong> <span id="modal-total-valor">R$ 0,00</span>
            </div>

            <div class="btn-group">
                <button type="button" id="salvar-tratamento-modal" class="btn-save">Salvar</button>
                <button type="button" onclick="fecharModal()" class="btn-cancel">Cancelar</button>
            </div>
        </form>
    </div>
</div>
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

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 10000; /* Increased z-index to be on top of everything */
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background-color: white;
        padding: 25px;
        border-radius: 8px;
        width: 500px; /* Increased width */
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    }

    .btn-group {
        margin-top: 20px;
        display: flex;
        gap: 10px;
    }

    .btn-save {
        background: #2ecc71;
        color: white;
        border: none;
        padding: 12px;
        cursor: pointer;
        border-radius: 4px;
        flex: 1;
        font-size: 16px;
    }

    .btn-cancel {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 12px;
        cursor: pointer;
        border-radius: 4px;
        flex: 1;
        font-size: 16px;
    }

    /* Odontogram */
    .canvas-container {
        position: relative;
        text-align: center;
        background: #fff;
        padding: 10px;
        border-radius: 8px;
    }

    .img-odontograma {
        max-width: 100%;
        height: auto;
        transition: filter 0.3s;
    }

    area {
        cursor: pointer;
        outline: none;
    }
</style>

<!-- Adicionando jQuery e jQuery UI para o Autocomplete -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script>
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-notification');
    if (toast) {
        toast.textContent = message;
        toast.className = 'toast show ' + type; // Add type for styling
        setTimeout(() => {
            toast.className = toast.className.replace(' show', '');
        }, 5000); // Hide after 5 seconds
    }
}

$(document).ready(function() {
    const pacienteIdInput = $('#paciente_id');
    const pacienteBuscaInput = $('#paciente_busca');
    const btnLimparPaciente = $('#btn_limpar_paciente');

    // Inicializa o autocomplete no campo de busca
    pacienteBuscaInput.autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "<?= BASE_URL ?>actions/buscar_paciente.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    if (!data.length) {
                        response([{ label: 'Nenhum paciente encontrado.', value: 'new' }]);
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
                // Novo paciente: mantém o nome digitado e trava o campo
                pacienteIdInput.val('');
                pacienteBuscaInput.prop('readonly', true);
                btnLimparPaciente.show();
                return false;
            } else {
                // Paciente existente: preenche os dados e carrega pendentes
                const patient = ui.item.patient;
                pacienteIdInput.val(patient.id);
                pacienteBuscaInput.val(ui.item.patient.nome).prop('readonly', true);
                btnLimparPaciente.show();
                carregarProcedimentosPendentes(patient.id);
            }
            return false;
        }
    });

    // Ação do botão de limpar
    btnLimparPaciente.on('click', function() {
        pacienteIdInput.val('');
        pacienteBuscaInput.val('').prop('readonly', false).show().focus();
        btnLimparPaciente.hide();
        $('#procedimentos_pendentes_container').empty();
        $('#procedimentos_adicionados_container').html('<h3>Procedimentos Adicionados</h3>');
    });
    
    const procedimentos = <?= json_encode($procedimentos) ?>;

    function carregarProcedimentosPendentes(pacienteId) {
        const container = $('#procedimentos_pendentes_container');
        container.html('<h4>Carregando procedimentos pendentes...</h4>');

        $.ajax({
            url: "<?= BASE_URL ?>actions/buscar_procedimentos_pendentes.php",
            dataType: "json",
            data: { paciente_id: pacienteId },
            success: function(data) {
                container.empty();
                if (data && data.length > 0) {
                    container.append('<h3>Procedimentos Pendentes Anteriores</h3>');
                    data.forEach(proc => {
                        const procHtml = `
                            <div class="procedimento-pendente-item" id="pendente-${proc.atendimento_procedimento_id}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 5px;">
                                <div>
                                    <strong>Procedimento:</strong> ${proc.procedimento_nome} (${proc.categoria})<br>
                                    <strong>Local:</strong> ${proc.local} | <strong>Descrição:</strong> ${proc.descricao || 'N/A'}
                                </div>
                                <button type="button" class="btn btn-success btn-sm finalizar-pendente-btn" 
                                        data-proc-id="${proc.id_procedimento}"
                                        data-proc-nome="${proc.procedimento_nome} (${proc.categoria})"
                                        data-proc-valor="${proc.valor_procedimento / proc.quantidade}"
                                        data-proc-local="${proc.local}"
                                        data-proc-descricao="${proc.descricao}"
                                        data-original-id="${proc.atendimento_procedimento_id}">Finalizar Agora</button>
                            </div>
                        `;
                        container.append(procHtml);
                    });
                }
            },
            error: () => container.html('<p class="error">Erro ao carregar pendências.</p>')
        });
    }

    $(document).on('click', '.finalizar-pendente-btn', function() {
        const btn = $(this);
        const originalId = btn.data('original-id');

        // Adiciona o procedimento à lista principal como 'finalizado'
        criarLinhaProcedimentoPrincipal(
            btn.data('proc-id'), 
            btn.data('proc-nome'), 
            1, 
            btn.data('proc-valor'),
            btn.data('proc-local'), 
            btn.data('proc-descricao'), 
            'finalizado',
            originalId // Passa o originalId
        );
        
        // Adiciona o ID original a um campo hidden para ser deletado no backend
        $('#procedimentos_a_deletar_container').append(
            `<input type="hidden" name="procedimentos_a_deletar[]" value="${originalId}" id="delete-${originalId}">`
        );

        // Remove o item da lista de pendentes na UI
        $(`#pendente-${originalId}`).remove();
    });

    // Odontogram Modal
    const modal = document.getElementById('modalTratamento');
    const modalTitle = document.getElementById('modal-title');
    const inputDente = document.getElementById('inputDente');
    const inputArcada = document.getElementById('inputArcada');
    const procedimentosModalContainer = document.getElementById('procedimentos-modal-container');
    const salvarTratamentoModalButton = document.getElementById('salvar-tratamento-modal');

    // Abre o modal
    window.abrirModal = function(numero, arcada) {
        modal.classList.add('show');
        if (arcada === 'Geral') {
            modalTitle.innerText = 'Tratamento geral';
        } else {
            modalTitle.innerText = arcada + ' - Dente ' + numero;
        }
        inputDente.value = numero;
        inputArcada.value = arcada;
        procedimentosModalContainer.innerHTML = '';
        adicionarLinhaProcedimentoModal();
        updateModalTotal(); 
    }

    // Fecha o modal
    window.fecharModal = function() {
        modal.classList.remove('show');
    }

    // Fecha o modal ao clicar fora
    window.onclick = function(event) {
        if (event.target == modal) {
            fecharModal();
        }
    }

    function adicionarLinhaProcedimentoModal() {
        const row = document.createElement('div');
        row.classList.add('procedimento-row');
        row.style.marginBottom = '15px';

        const select = document.createElement('select');
        select.name = 'procedimentos_modal[id][]';
        select.required = true;
        let option = document.createElement('option');
        option.value = '';
        option.textContent = 'Selecione...';
        select.appendChild(option);
        procedimentos.forEach(p => {
            let opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.nome} (${p.categoria})`;
            opt.dataset.valor = p.valor_base;
            select.appendChild(opt);
        });

        const valorSpan = document.createElement('span');
        valorSpan.classList.add('procedimento-valor-span');
        valorSpan.style.marginLeft = '10px';
        valorSpan.style.fontWeight = 'bold';

        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const valor = selectedOption.dataset.valor || 0;
            valorSpan.textContent = `R$ ${parseFloat(valor).toFixed(2)}`;
            updateModalTotal();
        });

        const descricao = document.createElement('textarea');
        descricao.name = 'procedimentos_modal[descricao][]';
        descricao.placeholder = 'Descrição';
        descricao.rows = 2;
        descricao.style.width = '100%';
        descricao.style.marginTop = '5px';

        const finalizadoLabel = document.createElement('label');
        finalizadoLabel.style.display = 'flex';
        finalizadoLabel.style.alignItems = 'center';
        finalizadoLabel.style.marginTop = '5px';
        const finalizadoCheckbox = document.createElement('input');
        finalizadoCheckbox.type = 'checkbox';
        finalizadoCheckbox.name = 'procedimentos_modal[finalizado][]';
        finalizadoCheckbox.value = '1';
        finalizadoCheckbox.checked = true;
        finalizadoCheckbox.addEventListener('change', updateModalTotal);
        finalizadoLabel.appendChild(finalizadoCheckbox);
        finalizadoLabel.append(' Finalizado');

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remover';
        removeButton.classList.add('btn', 'btn-danger', 'btn-sm');
        removeButton.style.marginLeft = '10px';
        removeButton.onclick = () => {
            row.remove();
            updateModalTotal();
        };
        
        const controls = document.createElement('div');
        controls.style.display = 'flex';
        controls.style.alignItems = 'center';
        
        const mainContent = document.createElement('div');
        mainContent.style.flexGrow = '1';
        mainContent.appendChild(select);
        mainContent.appendChild(valorSpan);
        mainContent.appendChild(descricao);
        mainContent.appendChild(finalizadoLabel);
        
        controls.appendChild(mainContent);
        controls.appendChild(removeButton);
        row.appendChild(controls);

        procedimentosModalContainer.appendChild(row);
        updateModalTotal();
    }

    function updateModalTotal() {
        let total = 0;
        const rows = procedimentosModalContainer.querySelectorAll('.procedimento-row');
        rows.forEach(row => {
            const finalizadoCheckbox = row.querySelector('input[type="checkbox"]');
            if (finalizadoCheckbox && finalizadoCheckbox.checked) {
                const select = row.querySelector('select');
                const selectedOption = select.options[select.selectedIndex];
                if (selectedOption && selectedOption.dataset.valor) {
                    total += parseFloat(selectedOption.dataset.valor);
                }
            }
        });
        document.getElementById('modal-total-valor').textContent = `R$ ${total.toFixed(2)}`;
    }

    $('#add-procedimento-modal').on('click', adicionarLinhaProcedimentoModal);

    function criarLinhaProcedimentoPrincipal(procedimentoId, procedimentoNome, quantidade, valor, local, descricao, status_execucao, originalId = null) {
        const container = $('#procedimentos_adicionados_container');
        const uniqueId = 'proc-' + Date.now() + Math.random().toString(36).substr(2, 9);
        const isAdmin = <?= is_admin() ? 'true' : 'false' ?>;

        const isChecked = status_execucao === 'finalizado';
        const originalIdAttr = originalId ? `data-original-id="${originalId}"` : '';

        let alterarValorHtml = '';
        if (isAdmin) {
            alterarValorHtml = `
                <div class="valor-container" style="margin-top: 5px;">
                    <strong>Valor:</strong> 
                    <span class="valor-display">R$ ${parseFloat(valor).toFixed(2)}</span>
                    <button type="button" class="btn btn-info btn-sm alterar-valor-btn" style="margin-left: 10px;">Alterar Valor</button>
                    <div class="alterar-valor-form" style="display: none; margin-top: 5px;">
                        <input type="number" step="0.01" class="novo-valor-input" style="width: 100px;">
                        <button type="button" class="btn btn-success btn-sm salvar-valor-btn" style="margin-left: 5px;">Salvar</button>
                    </div>
                </div>
            `;
        }

        const procHtml = `
            <div class="procedimento-row-principal" id="${uniqueId}" 
                 data-valor="${valor}" data-status_execucao="${status_execucao}"
                 data-proc-id="${procedimentoId}" data-proc-nome="${procedimentoNome}"
                 data-proc-local="${local}" data-proc-descricao="${descricao}"
                 ${originalIdAttr}
                 style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 5px;">
                
                <input type="hidden" name="procedimentos[id][]" value="${procedimentoId}">
                <input type="hidden" name="procedimentos[quantidade][]" value="${quantidade}">
                <input type="hidden" class="valor-input" name="procedimentos[valor][]" value="${valor}">
                <input type="hidden" name="procedimentos[local][]" value="${local}">
                <input type="hidden" name="procedimentos[descricao][]" value="${descricao}">
                <input type="hidden" class="status-execucao-input" name="procedimentos[status_execucao][]" value="${status_execucao}">

                <div>
                    <strong>Procedimento:</strong> ${procedimentoNome}<br>
                    <strong>Local:</strong> ${local} | <strong>Status:</strong> <span class="status-text">${status_execucao}</span> <br>
                    <strong>Descrição:</strong> ${descricao || 'N/A'}
                    ${alterarValorHtml}
                </div>
                <div>
                    <label style="display: flex; align-items: center;">
                        <input type="checkbox" class="finalizado-checkbox" ${isChecked ? 'checked' : ''}>
                        Finalizado
                    </label>
                </div>
            </div>
        `;
        const procElement = $(procHtml);

        procElement.find('.finalizado-checkbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            const newStatus = isChecked ? 'finalizado' : 'pendente';
            const parentRow = $(this).closest('.procedimento-row-principal');
            
            parentRow.attr('data-status_execucao', newStatus);
            parentRow.find('.status-execucao-input').val(newStatus);
            parentRow.find('.status-text').text(newStatus);

            updateTotalAPagar();
        });

        // Lógica para alterar valor
        procElement.find('.alterar-valor-btn').on('click', function() {
            $(this).hide();
            procElement.find('.valor-display').hide();
            procElement.find('.alterar-valor-form').show();
            procElement.find('.novo-valor-input').val(procElement.attr('data-valor')).focus();
        });

        procElement.find('.salvar-valor-btn').on('click', function() {
            const novoValor = procElement.find('.novo-valor-input').val();
            if (novoValor && !isNaN(novoValor) && parseFloat(novoValor) >= 0) {
                const novoValorFloat = parseFloat(novoValor);
                procElement.attr('data-valor', novoValorFloat);
                procElement.find('.valor-input').val(novoValorFloat);
                procElement.find('.valor-display').text(`R$ ${novoValorFloat.toFixed(2)}`);
                updateTotalAPagar();
            }
            procElement.find('.alterar-valor-form').hide();
            procElement.find('.alterar-valor-btn').show();
            procElement.find('.valor-display').show();
        });

        container.append(procElement);
        updateTotalAPagar();
    }

    function updateTotalAPagar() {
        let total = 0;
        $('.procedimento-row-principal').each(function() {
            const row = $(this);
            if (row.attr('data-status_execucao') === 'finalizado') {
                total += parseFloat(row.attr('data-valor'));
            }
        });
        $('#total-procedimentos-valor').text(`R$ ${total.toFixed(2)}`);
    }

    function removerProcedimento(elementId) {
        const procElement = $('#' + elementId);
        if (!procElement.length) return;

        const originalId = procElement.data('original-id');

        // Se tem ID original, significa que veio da lista de pendentes
        if (originalId) {
            const container = $('#procedimentos_pendentes_container');
            if (container.find(`#pendente-${originalId}`).length === 0) {
                // Remonta o HTML do item pendente
                 const procHtml = `
                    <div class="procedimento-pendente-item" id="pendente-${originalId}" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border: 1px solid #ddd; margin-bottom: 5px; border-radius: 5px;">
                        <div>
                            <strong>Procedimento:</strong> ${procElement.data('proc-nome')}<br>
                            <strong>Local:</strong> ${procElement.data('proc-local')} | <strong>Descrição:</strong> ${procElement.data('proc-descricao') || 'N/A'}
                        </div>
                        <button type="button" class="btn btn-success btn-sm finalizar-pendente-btn" 
                                data-proc-id="${procElement.data('proc-id')}"
                                data-proc-nome="${procElement.data('proc-nome')}"
                                data-proc-valor="${procElement.data('valor')}"
                                data-proc-local="${procElement.data('proc-local')}"
                                data-proc-descricao="${procElement.data('proc-descricao')}"
                                data-original-id="${originalId}">Finalizar Agora</button>
                    </div>
                `;
                container.append(procHtml);
            }
            // Remove o input que marcava para deletar no backend
            $(`#delete-${originalId}`).remove();
        }

        procElement.remove();
        updateTotalAPagar();
    }

    salvarTratamentoModalButton.addEventListener('click', function() {
        const dente = inputDente.value;
        const arcada = inputArcada.value;
        const local = (arcada === 'Geral') ? 'Todos' : dente;

        const rows = procedimentosModalContainer.querySelectorAll('.procedimento-row');
        rows.forEach(row => {
            const select = row.querySelector('select');
            const selectedOption = select.options[select.selectedIndex];
            if (!selectedOption.value) return;

            const descricao = row.querySelector('textarea').value;
            const finalizado = row.querySelector('input[type="checkbox"]').checked;
            const status_execucao = finalizado ? 'finalizado' : 'pendente';
            const procedimentoId = selectedOption.value;
            const procedimentoNome = selectedOption.textContent;
            const valor = selectedOption.dataset.valor;

            criarLinhaProcedimentoPrincipal(procedimentoId, procedimentoNome, 1, valor, local, descricao, status_execucao);
        });

        fecharModal();
        updateTotalAPagar();
    });

    const form = document.getElementById('form-atendimento');
    form.addEventListener('submit', async function(event) {
        event.preventDefault(); 
        
        const submitButton = form.querySelector('button[type="submit"]');
        const pacienteId = pacienteIdInput.val();
        const pacienteNome = pacienteBuscaInput.val();

        if (!pacienteId && !pacienteNome) {
            showToast('É necessário selecionar ou cadastrar um paciente.', 'error');
            return;
        }

        // 1. Verificar pagamento pendente
        if (pacienteId) {
            try {
                const response = await fetch(`<?= BASE_URL ?>actions/verificar_pagamento_pendente.php?paciente_id=${pacienteId}`);
                const data = await response.json();

                if (data.pendente) {
                    alert(`O(A) paciente ${pacienteNome} tem um pagamento pendente de outro procedimento. Necessário finalizar o anterior para Lançar Novos.`);
                    return; // Bloqueia o envio
                }
            } catch (error) {
                console.error('Erro ao verificar pagamento pendente:', error);
                showToast('Não foi possível verificar pagamentos pendentes. Verifique o console.', 'error');
                return; // Bloqueia por segurança
            }
        }
       
        // 2. Prosseguir com o envio do formulário
        submitButton.disabled = true;
        submitButton.textContent = 'Salvando...';

        const formData = new FormData(form);

        try {
            const response = await fetch('<?= BASE_URL ?>actions/salvar_atendimento2.php', {
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
});
</script>

<?php require_once 'footer.php'; ?>