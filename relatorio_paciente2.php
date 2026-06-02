<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';
require_once 'config/controle_acesso.php';

if (!is_admin()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Inicializa variáveis
$paciente_nome = '';
$paciente = null;
$procedimentos = [];
$totalPaginas = 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;

if (isset($_GET['paciente_nome'])) {
    $paciente_nome = trim($_GET['paciente_nome']);

    if (!empty($paciente_nome)) {
        // Busca o paciente
        $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE LOWER(nome) LIKE LOWER(?)");
        $stmt->execute(['%' . $paciente_nome . '%']);
        $paciente = $stmt->fetch();

        if ($paciente) {
            $itensPorPagina = 20;
            $offset = ($pagina - 1) * $itensPorPagina;

            // Contagem total para paginação
            $stmt_count = $pdo->prepare("
                SELECT COUNT(ap.id)
                FROM atendimento_procedimentos ap
                JOIN atendimentos a ON ap.id_atendimento = a.id
                WHERE a.paciente_id = ?
            ");
            $stmt_count->execute([$paciente['id']]);
            $totalRegistros = $stmt_count->fetchColumn();
            $totalPaginas = ceil($totalRegistros / $itensPorPagina);

            // Busca procedimentos paginados
            $stmt_procedimentos = $pdo->prepare("
                SELECT 
                    ap.id as atendimento_procedimento_id,
                    proc.nome as procedimento_nome,
                    ap.local, 
                    ap.descricao,
                    a.data_atendimento,
                    ap.status_execucao,
                    a.status_pagamento,
                    ap.url_arquivo
                FROM atendimento_procedimentos ap
                JOIN atendimentos a ON ap.id_atendimento = a.id
                JOIN procedimentos proc ON ap.id_procedimento = proc.id
                WHERE a.paciente_id = :paciente_id
                ORDER BY a.data_atendimento DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt_procedimentos->bindValue(':paciente_id', $paciente['id'], PDO::PARAM_INT);
            $stmt_procedimentos->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
            $stmt_procedimentos->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt_procedimentos->execute();
            $procedimentos = $stmt_procedimentos->fetchAll();

            // --- INÍCIO: Lógica para colorir o odontograma ---
            $stmt_status = $pdo->prepare("
                SELECT
                    ap.local,
                    ap.status_execucao
                FROM atendimento_procedimentos ap
                JOIN atendimentos a ON ap.id_atendimento = a.id
                WHERE a.paciente_id = ? AND ap.local IS NOT NULL AND ap.local != ''
            ");
            $stmt_status->execute([$paciente['id']]);
            $all_procedures_for_status = $stmt_status->fetchAll();

            $dente_status_raw = [];
            foreach ($all_procedures_for_status as $proc) {
                $local = $proc['local'];
                if (!isset($dente_status_raw[$local])) {
                    $dente_status_raw[$local] = ['pendente' => false, 'feito' => false];
                }
                // 'feito' é o estado final pago
                if ($proc['status_execucao'] === 'feito') {
                    $dente_status_raw[$local]['feito'] = true;
                }
                // 'pendente' ou 'finalizado' (feito mas não pago) são considerados pendentes para a cor
                if ($proc['status_execucao'] === 'pendente' || $proc['status_execucao'] === 'finalizado') {
                    $dente_status_raw[$local]['pendente'] = true;
                }
            }

            $dente_status_color = [];
            foreach ($dente_status_raw as $local => $statuses) {
                if ($statuses['feito'] && $statuses['pendente']) {
                    $dente_status_color[$local] = 'yellow'; // Amarelo para misto
                } elseif ($statuses['feito']) {
                    $dente_status_color[$local] = 'green'; // Verde para concluído
                } elseif ($statuses['pendente']) {
                    $dente_status_color[$local] = 'red'; // Vermelho para pendente
                }
            }
            // --- FIM: Lógica para colorir o odontograma ---

            // Agrupa procedimentos por local
            $procedimentos_agrupados = [];
            foreach ($procedimentos as $proc) {
                $local = $proc['local'];
                if (!isset($procedimentos_agrupados[$local])) {
                    $procedimentos_agrupados[$local] = [];
                }
                $procedimentos_agrupados[$local][] = $proc;
            }

            $procedimentos_todos = $procedimentos_agrupados['Todos'] ?? [];
            unset($procedimentos_agrupados['Todos']);
            uksort($procedimentos_agrupados, 'strnatcmp');
        }
    }
}
?>

<div class="card">
    <h2>Relatório por Paciente</h2>

    <form method="GET" action="relatorio_paciente2.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="form-group" style="flex-grow: 1;">
                <label for="paciente_nome">Buscar Paciente</label>
                <input type="text" name="paciente_nome" id="paciente_nome" value="<?= htmlspecialchars($paciente_nome) ?>" placeholder="Digite o nome do paciente">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'upload_sucesso'): ?>
        <p style="color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px;">Arquivo enviado com sucesso!</p>
    <?php elseif (isset($_GET['erro'])): ?>
        <p class="error"><?= htmlspecialchars($_GET['erro']) ?></p>
    <?php endif; ?>

    <?php if ($paciente): ?>
        <div style="margin-top: 2rem; text-align: center;">
            <h3>Odontograma de <?= htmlspecialchars($paciente['nome']) ?></h3>
            <div class="canvas-container">
                <img src="assets/img/odontograma.png" usemap="#image-map" class="img-odontograma" id="odontograma-img">
                <canvas id="odontograma-canvas"></canvas>
                <map name="image-map">
                    <!-- Arcada Superior -->
                    <area data-dente="18" target="" onclick="abrirModal(18, 'Arcada Superior')" alt="Molar 18" id="d18" title="3º Molar" coords="53,251,99,157" shape="rect">
                    <area data-dente="17" target="" onclick="abrirModal(17, 'Arcada Superior')" alt="Molar 17" id="d17" title="2º Molar" coords="147,156,103,249" shape="rect">
                    <area data-dente="16" target="" onclick="abrirModal(16, 'Arcada Superior')" alt="Molar 16" title="Molar 16" coords="207,155,151,246" shape="rect">
                    <area data-dente="15" target="" onclick="abrirModal(15, 'Arcada Superior')" alt="Premolar 15" title="Premolar 15" coords="241,152,209,242" shape="rect">
                    <area data-dente="14" target="" onclick="abrirModal(14, 'Arcada Superior')" alt="Premolar 14" title="Premolar 14" coords="274,149,246,241" shape="rect">
                    <area data-dente="13" target="" onclick="abrirModal(13, 'Arcada Superior')" alt="Canino 13" title="Canino 13" coords="314,148,277,238" shape="rect">
                    <area data-dente="12" target="" onclick="abrirModal(12, 'Arcada Superior')" alt="Inciso 12" title="Inciso 12" coords="352,152,317,243" shape="rect">
                    <area data-dente="11" target="" onclick="abrirModal(11, 'Arcada Superior')" alt="Inciso 11" title="Inciso 11" coords="397,153,355,246" shape="rect">
                    <area data-dente="21" target="" onclick="abrirModal(21, 'Arcada Superior')" alt="Inciso 21" title="Inciso 21" coords="442,154,403,244" shape="rect">
                    <area data-dente="22" target="" onclick="abrirModal(22, 'Arcada Superior')" alt="Inciso 22" title="Inciso 22" coords="479,153,446,243" shape="rect">
                    <area data-dente="23" target="" onclick="abrirModal(23, 'Arcada Superior')" alt="Canino 23" title="Canino 23" coords="521,142,481,243" shape="rect">
                    <area data-dente="24" target="" onclick="abrirModal(24, 'Arcada Superior')" alt="Premolar 24" title="Premolar 24" coords="561,146,525,239" shape="rect">
                    <area data-dente="25" target="" onclick="abrirModal(25, 'Arcada Superior')" alt="Premolar 25" title="Premolar 25" coords="590,146,564,237" shape="rect">
                    <area data-dente="26" target="" onclick="abrirModal(26, 'Arcada Superior')" alt="Molar 26" title="Molar 26" coords="648,148,593,238" shape="rect">
                    <area data-dente="27" target="" onclick="abrirModal(27, 'Arcada Superior')" alt="Molar 27" title="Molar 27" coords="703,151,653,239" shape="rect">
                    <area data-dente="28" target="" onclick="abrirModal(28, 'Arcada Superior')" alt="Molar 28" id="d28" title="3º Molar" coords="741,149,705,241" shape="rect">

                    <!-- Arcada Inferior -->
                    <area data-dente="48" target="" onclick="abrirModal(48, 'Arcada Inferior')" alt="Molar 48" id="d48" title="Molar 48" coords="51,285,103,360" shape="rect">
                    <area data-dente="47" target="" onclick="abrirModal(47, 'Arcada Inferior')" alt="Molar 47" title="Molar 47" coords="109,284,160,363" shape="rect">
                    <area data-dente="46" target="" onclick="abrirModal(46, 'Arcada Inferior')" alt="Molar 46" title="Molar 46" coords="167,281,219,363" shape="rect">
                    <area data-dente="45" target="" onclick="abrirModal(45, 'Arcada Inferior')" alt="Premolar 45" title="Premolar 45" coords="221,278,258,378" shape="rect">
                    <area data-dente="44" target="" onclick="abrirModal(44, 'Arcada Inferior')" alt="Premolar 44" title="Premolar 44" coords="260,275,296,390" shape="rect">
                    <area data-dente="43" target="" onclick="abrirModal(43, 'Arcada Inferior')" alt="Canino 43" title="Canino 43" coords="298,276,336,384" shape="rect">
                    <area data-dente="42" target="" onclick="abrirModal(42, 'Arcada Inferior')" alt="Inciso 42" title="Inciso 42" coords="338,275,368,384" shape="rect">
                    <area data-dente="41" target="" onclick="abrirModal(41, 'Arcada Inferior')" alt="Inciso 41" title="Inciso 41" coords="370,276,395,383" shape="rect">
                    <area data-dente="31" target="" onclick="abrirModal(31, 'Arcada Inferior')" alt="Inciso 31" title="Inciso 31" coords="398,275,426,380" shape="rect">
                    <area data-dente="32" target="" onclick="abrirModal(32, 'Arcada Inferior')" alt="Inciso 32" title="Inciso 32" coords="428,275,454,382" shape="rect">
                    <area data-dente="33" target="" onclick="abrirModal(33, 'Arcada Inferior')" alt="Canino 33" title="Canino 33" coords="456,274,493,391" shape="rect">
                    <area data-dente="34" target="" onclick="abrirModal(34, 'Arcada Inferior')" alt="Premolar 34" title="Premolar 34" coords="496,274,531,383" shape="rect">
                    <area data-dente="35" target="" onclick="abrirModal(35, 'Arcada Inferior')" alt="Premolar 35" title="Premolar 35" coords="534,274,571,379" shape="rect">
                    <area data-dente="36" target="" onclick="abrirModal(36, 'Arcada Inferior')" alt="Molar 36" title="Molar 36" coords="575,274,636,384" shape="rect">
                    <area data-dente="37" target="" onclick="abrirModal(37, 'Arcada Inferior')" alt="Molar 37" title="Molar 37" coords="640,274,688,384" shape="rect">
                    <area data-dente="38" target="" onclick="abrirModal(38, 'Arcada Inferior')" alt="Molar 38" title="Molar 38" coords="694,272,742,375" shape="rect">

                    <!-- Geral -->
                    <area data-dente="Todos" target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="85,31,727,83" shape="rect">
                    <area data-dente="Todos" target="" onclick="abrirModal('Todos', 'Geral')" alt="Todos" title="Todos" coords="72,449,727,498" shape="rect">
                </map>
            </div>
        </div>

       <div style="margin-top: 2rem;">
            <h3>Histórico de Procedimentos</h3>

            <?php if (empty($procedimentos)): ?>
                <p style="text-align: center; margin-top: 1rem;">Nenhum procedimento encontrado para este paciente.</p>
            <?php else: ?>

                <?php if (!empty($procedimentos_todos)): ?>
                    <div class="card" style="margin-top: 1rem; border-left: 5px solid var(--primary-color);">
                        <h4>Tratamentos Gerais (Todos)</h4>
                        <?php foreach($procedimentos_todos as $proc): ?>
                            <div class="sub-card">
                                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($proc['data_atendimento'])) ?></p>
                                <p><strong>Procedimento:</strong> <?= htmlspecialchars($proc['procedimento_nome']) ?></p>
                                <p><strong>Descrição:</strong> <?= htmlspecialchars($proc['descricao'] ?: 'N/A') ?></p>
                                <p><strong>Status Execução:</strong> <span class="status-badge status-<?= strtolower($proc['status_execucao']) ?>"><?= htmlspecialchars(ucfirst($proc['status_execucao'])) ?></span></p>
                                <p><strong>Status Pagamento:</strong> <span class="status-badge status-<?= strtolower($proc['status_pagamento']) ?>"><?= htmlspecialchars(ucfirst($proc['status_pagamento'])) ?></span></p>
                                <div class="arquivo-container" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($proc['url_arquivo'])): ?>
                                        <strong>Arquivo:</strong>
                                        <a href="<?= BASE_URL . htmlspecialchars($proc['url_arquivo']) ?>" target="_blank" class="btn btn-info btn-sm" style="padding: 5px 10px;">Visualizar</a>
                                        <a href="<?= BASE_URL . htmlspecialchars($proc['url_arquivo']) ?>" download class="btn btn-secondary btn-sm" style="padding: 5px 10px;">Baixar</a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" style="padding: 5px 10px;" onclick="abrirModalUpload(<?= $proc['atendimento_procedimento_id'] ?>)">Anexar Arquivo</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php foreach($procedimentos_agrupados as $local => $procs_dente): ?>
                    <div class="card" style="margin-top: 1rem; border-left: 5px solid var(--secondary-color);">
                        <h4>Dente <?= htmlspecialchars($local) ?></h4>
                        <?php foreach($procs_dente as $proc): ?>
                            <div class="sub-card">
                                <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($proc['data_atendimento'])) ?></p>
                                <p><strong>Procedimento:</strong> <?= htmlspecialchars($proc['procedimento_nome']) ?></p>
                                <p><strong>Descrição:</strong> <?= htmlspecialchars($proc['descricao'] ?: 'N/A') ?></p>
                                <p><strong>Status Execução:</strong> <span class="status-badge status-<?= strtolower($proc['status_execucao']) ?>"><?= htmlspecialchars(ucfirst($proc['status_execucao'])) ?></span></p>
                                <p><strong>Status Pagamento:</strong> <span class="status-badge status-<?= strtolower($proc['status_pagamento']) ?>"><?= htmlspecialchars(ucfirst($proc['status_pagamento'])) ?></span></p>
                                <div class="arquivo-container" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; display: flex; align-items: center; gap: 10px;">
                                    <?php if (!empty($proc['url_arquivo'])): ?>
                                        <strong>Arquivo:</strong>
                                        <a href="<?= BASE_URL . htmlspecialchars($proc['url_arquivo']) ?>" target="_blank" class="btn btn-info btn-sm" style="padding: 5px 10px;">Visualizar</a>
                                        <a href="<?= BASE_URL . htmlspecialchars($proc['url_arquivo']) ?>" download class="btn btn-secondary btn-sm" style="padding: 5px 10px;">Baixar</a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" style="padding: 5px 10px;" onclick="abrirModalUpload(<?= $proc['atendimento_procedimento_id'] ?>)">Anexar Arquivo</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
            <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $totalPaginas; $i++):
                    $queryParams = $_GET;
                    $queryParams['pagina'] = $i;
                    $url = '?' . http_build_query($queryParams);
                ?>
                    <a href="<?= $url ?>" class="btn <?= $i === $pagina ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif (isset($_GET['paciente_nome'])): ?>
        <p style="margin-top: 2rem;">Nenhum paciente encontrado com o nome "<?= htmlspecialchars($paciente_nome) ?>".</p>
    <?php endif; ?>
</div>

<div id="modalTratamento" class="modal">
    <div class="modal-content">
        <h3 id="modal-title"></h3>
        <div id="modal-body" class="modal-body-content"></div>
        <div class="btn-group" style="margin-top: 1rem;">
            <button type="button" onclick="fecharModal()" class="btn-cancel">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal de Upload de Arquivo -->
<div id="modalUpload" class="modal">
    <div class="modal-content">
        <h3>Anexar Arquivo ao Procedimento</h3>
        <form id="form-upload-arquivo" action="<?= BASE_URL ?>actions/salvar_arquivo_procedimento.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="atendimento_procedimento_id" id="upload_atendimento_procedimento_id">
            <input type="hidden" name="paciente_nome_redirect" value="<?= htmlspecialchars($paciente_nome) ?>">
            <div class="form-group">
                <label for="arquivo_procedimento">Selecione o arquivo (PDF, JPG, PNG)</label>
                <input type="file" name="arquivo_procedimento" id="arquivo_procedimento" accept=".pdf,image/jpeg,image/png" required>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-save">Enviar</button>
                <button type="button" onclick="fecharModalUpload()" class="btn-cancel">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .canvas-container {
        position: relative;
        text-align: center;
        background: #fff;
        padding: 10px;
        border-radius: 8px;
        display: inline-block;
        line-height: 0;
    }

    #odontograma-canvas {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none; /* Clicks go through to the map */
    }

    .img-odontograma {
        max-width: 100%;
        height: auto;
        transition: filter 0.3s;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
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
        width: 90%;
        max-width: 600px; /* Aumentar um pouco a largura */
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        max-height: 90vh; /* Limitar altura */
        display: flex;
        flex-direction: column;
    }

    .modal-body-content {
        overflow-y: auto; /* Adicionar scroll se o conteúdo for grande */
        margin-top: 1rem;
        padding-right: 10px; /* Espaço para a barra de rolagem */
    }
    .btn-group {
        margin-top: 20px;
        display: flex;
        gap: 10px;
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

    .sub-card {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .sub-card:last-child {
        margin-bottom: 0;
    }
    .sub-card p {
        margin: 0 0 0.5rem 0;
    }
    .sub-card p:last-child {
        margin-bottom: 0;
    }

    .status-badge {
        padding: 0.2em 0.6em;
        border-radius: 10px;
        font-size: 0.8em;
        font-weight: bold;
        color: white;
    }
    .status-feito, .status-pago { background-color: var(--success-color); }
    .status-pendente { background-color: var(--warning-color); color: #333; }
    .status-finalizado { background-color: var(--info-color); }
    .status-nao_aplicavel { background-color: var(--secondary-color); }
</style>

<script>
    // Funções do Modal (escopo global para serem acessíveis pelo onclick)
     const modal = document.getElementById('modalTratamento');
     const modalTitle = document.getElementById('modal-title');
     const modalBody = document.getElementById('modal-body');
     const modalUpload = document.getElementById('modalUpload');

    <?php if ($paciente): ?>
    // Passa os dados do PHP para o JS
    const procedimentosPorLocal = <?= json_encode($procedimentos_agrupados ?? []) ?>;
    procedimentosPorLocal['Todos'] = <?= json_encode($procedimentos_todos ?? []) ?>;
    <?php endif; ?>

    function formatStatus(status) {
        if (!status) return 'N/A';
        const formatted = status.replace(/_/g, ' ');
        return formatted.charAt(0).toUpperCase() + formatted.slice(1);
    }

    function abrirModal(numero, arcada) {
        modal.classList.add('show');
        modalBody.innerHTML = ''; // Limpa o conteúdo anterior

        const titulo = (arcada === 'Geral') ? 'Tratamentos Gerais' : `${arcada} - Dente ${numero}`;
        modalTitle.innerText = titulo;

        // Verifica se os dados existem antes de tentar acessar
        if (typeof procedimentosPorLocal !== 'undefined') {
            const procedimentos = procedimentosPorLocal[numero] || [];

            if (procedimentos.length > 0) {
                procedimentos.forEach(proc => {
                    const data = new Date(proc.data_atendimento);
                    const dataFormatada = data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                    const procHtml = `
                        <div class="sub-card">
                            <p><strong>Data:</strong> ${dataFormatada}</p>
                            <p><strong>Procedimento:</strong> ${proc.procedimento_nome || ''}</p>
                            <p><strong>Descrição:</strong> ${proc.descricao || 'N/A'}</p>
                            <p><strong>Status Execução:</strong> <span class="status-badge status-${proc.status_execucao.toLowerCase()}">${formatStatus(proc.status_execucao)}</span></p>
                            <p><strong>Status Pagamento:</strong> <span class="status-badge status-${proc.status_pagamento.toLowerCase()}">${formatStatus(proc.status_pagamento)}</span></p>
                        </div>
                    `;
                    modalBody.innerHTML += procHtml;
                });
            } else {
                modalBody.innerHTML = '<p>Nenhum procedimento registrado para este local.</p>';
            }
        } else {
             modalBody.innerHTML = '<p>Nenhum paciente selecionado.</p>';
        }
    }

     function fecharModal() {
         modal.classList.remove('show');
     }

     function abrirModalUpload(atendimentoProcedimentoId) {
        if (modalUpload) {
            document.getElementById('upload_atendimento_procedimento_id').value = atendimentoProcedimentoId;
            modalUpload.classList.add('show');
        }
     }

     function fecharModalUpload() {
        if (modalUpload) {
            modalUpload.classList.remove('show');
        }
     }

     window.onclick = function(event) {
         if (event.target == modal) {
             fecharModal();
         }
         if (event.target == modalUpload) {
             fecharModalUpload();
         }
     }

    // Lógica do Canvas
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($paciente): ?>
        const denteStatus = <?= json_encode($dente_status_color ?? []) ?>;
        const img = document.getElementById('odontograma-img');
        const canvas = document.getElementById('odontograma-canvas');
        const ctx = canvas.getContext('2d');
        const map = document.querySelector('map[name="image-map"]');

        const colors = {
            red: 'rgba(220, 53, 69, 0.5)',
            green: 'rgba(40, 167, 69, 0.5)',
            yellow: 'rgba(255, 193, 7, 0.5)'
        };

        function drawHighlights() {
            canvas.width = img.clientWidth;
            canvas.height = img.clientHeight;

            const scaleX = img.clientWidth / img.naturalWidth;
            const scaleY = img.clientHeight / img.naturalHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (const local in denteStatus) {
                if (denteStatus.hasOwnProperty(local)) {
                    const color = denteStatus[local];
                    const areas = map.querySelectorAll(`area[data-dente="${local}"]`);

                    areas.forEach(area => {
                        const coords = area.coords.split(',').map(Number);
                        if (coords.length === 4) { // rect
                            const x1 = coords[0] * scaleX;
                            const y1 = coords[1] * scaleY;
                            const x2 = coords[2] * scaleX;
                            const y2 = coords[3] * scaleY;
                            
                            const width = Math.abs(x2 - x1);
                            const height = Math.abs(y2 - y1);
                            const startX = Math.min(x1, x2);
                            const startY = Math.min(y1, y2);

                            ctx.fillStyle = colors[color];
                            ctx.fillRect(startX, startY, width, height);
                        }
                    });
                }
            }
        }

        if (img.complete) {
            drawHighlights();
        } else {
            img.onload = drawHighlights;
        }

        window.addEventListener('resize', drawHighlights);

        <?php endif; ?>
    });
</script>

<?php require_once 'views/footer.php'; ?>
