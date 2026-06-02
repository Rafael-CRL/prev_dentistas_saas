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
                    proc.nome as procedimento_nome,
                    ap.local, 
                    ap.descricao,
                    a.data_atendimento,
                    ap.status_execucao,
                    a.status_pagamento
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
        }
    }
}
?>

<div class="card">
    <h2>Relatório por Paciente</h2>

    <form method="GET" action="relatorio_paciente.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="form-group" style="flex-grow: 1;">
                <label for="paciente_nome">Buscar Paciente</label>
                <input type="text" name="paciente_nome" id="paciente_nome" value="<?= htmlspecialchars($paciente_nome) ?>" placeholder="Digite o nome do paciente">
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <?php if ($paciente): ?>
        <div style="margin-top: 2rem;">
            <h3>Odontograma de <?= htmlspecialchars($paciente['nome']) ?></h3>
            <div class="canvas-container">
                <img src="assets/img/odontograma.png" usemap="#image-map" class="img-odontograma">
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

        <div style="margin-top: 2rem;">
            <h3>Histórico de Procedimentos</h3>
            <table class="mobile-card-table" style="margin-top: 1rem;">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Procedimento</th>
                        <th>Local</th>
                        <th>Descrição</th>
                        <th>Status Execução</th>
                        <th>Status Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($procedimentos)): ?>
                        <?php foreach($procedimentos as $proc): ?>
                        <tr>
                            <td data-label="Data"><?= date('d/m/Y H:i', strtotime($proc['data_atendimento'])) ?></td>
                            <td data-label="Procedimento"><?= htmlspecialchars($proc['procedimento_nome']) ?></td>
                            <td data-label="Local"><?= htmlspecialchars($proc['local']) ?></td>
                            <td data-label="Descrição"><?= htmlspecialchars($proc['descricao']) ?></td>
                            <td data-label="Status Execução"><?= htmlspecialchars(ucfirst($proc['status_execucao'])) ?></td>
                            <td data-label="Status Pagamento"><?= htmlspecialchars(ucfirst($proc['status_pagamento'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">Nenhum procedimento encontrado para este paciente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

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
        <h3><span id="modal-title"></span></h3>
        <div class="btn-group">
            <button type="button" onclick="fecharModal()" class="btn-cancel">Fechar</button>
        </div>
    </div>
</div>

<style>
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
        width: 500px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
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
</style>

<script>
    const modal = document.getElementById('modalTratamento');
    const modalTitle = document.getElementById('modal-title');

    function abrirModal(numero, arcada) {
        modal.classList.add('show');
        if (arcada === 'Geral') {
            modalTitle.innerText = 'Tratamento geral';
        } else {
            modalTitle.innerText = arcada + ' - Dente ' + numero;
        }
    }

    function fecharModal() {
        modal.classList.remove('show');
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            fecharModal();
        }
    }
</script>

<?php require_once 'views/footer.php'; ?>
