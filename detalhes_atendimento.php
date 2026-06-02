<?php
// detalhes_atendimento.php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';
require_once 'config/controle_acesso.php';

// Apenas usuários logados podem acessar
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$id_atendimento = $_GET['id'] ?? null;

if (!$id_atendimento) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

try {
    // Buscar detalhes do atendimento, juntando com pacientes e usuários
    $stmt = $pdo->prepare(
        "SELECT 
            a.*, 
            u.nome as dentista_nome,
            p.nome as paciente_nome,
            p.cpf as paciente_cpf,
            p.telefone as paciente_telefone,
            p.email as paciente_email
         FROM atendimentos a
         JOIN usuarios u ON a.id_dentista = u.id
         JOIN pacientes p ON a.paciente_id = p.id
         WHERE a.id = ?");
    $stmt->execute([$id_atendimento]);
    $atendimento = $stmt->fetch();

    if (!$atendimento) {
        echo "<div class='card'><p class='error'>Atendimento não encontrado.</p></div>";
        require_once 'views/footer.php';
        exit;
    }

    // Controle de Acesso: Apenas admin, recepcionista ou o dentista responsável podem ver
    if (!is_admin() && !is_recepcionista() && $_SESSION['user_id'] != $atendimento['id_dentista']) {
         header('Location: ' . BASE_URL . 'index.php');
         exit;
    }
    
    // Buscar procedimentos do atendimento
    $stmtProc = $pdo->prepare(
       "SELECT p.nome, ap.quantidade, ap.valor_procedimento, p.categoria
        FROM atendimento_procedimentos ap
        JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE ap.id_atendimento = ?"
    );
    $stmtProc->execute([$id_atendimento]);
    $procedimentos = $stmtProc->fetchAll();

    // Buscar pagamentos do atendimento
    $stmtPag = $pdo->prepare("SELECT * FROM atendimento_pagamentos WHERE id_atendimento = ?");
    $stmtPag->execute([$id_atendimento]);
    $pagamentos = $stmtPag->fetchAll();

} catch (Exception $e) {
    echo "<div class='card'><p class='error'>Erro ao carregar detalhes: " . $e->getMessage() . "</p></div>";
    require_once 'views/footer.php';
    exit;
}
?>

<div class="card">
    <h2>Detalhes do Atendimento #<?= $atendimento['id'] ?></h2>

    <div class="detalhes-grid">
        <div><strong>Paciente:</strong> <?= htmlspecialchars($atendimento['paciente_nome']) ?></div>
        <div><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($atendimento['data_atendimento'])) ?></div>
        <div><strong>Dentista:</strong> <?= htmlspecialchars($atendimento['dentista_nome']) ?></div>
        <div><strong>Valor Total:</strong> R$ <?= number_format($atendimento['valor_total'], 2, ',', '.') ?></div>
        
        <?php if (!empty($atendimento['paciente_telefone'])): ?>
            <div><strong>Telefone:</strong> <?= htmlspecialchars($atendimento['paciente_telefone']) ?></div>
        <?php endif; ?>

        <?php if (!empty($atendimento['paciente_email'])): ?>
            <div><strong>E-mail:</strong> <?= htmlspecialchars($atendimento['paciente_email']) ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($atendimento['url_arquivo'])): ?>
        <div class="detalhes-arquivo">
            <strong>Arquivo de Raio-X:</strong>
            <a href="<?= BASE_URL . htmlspecialchars($atendimento['url_arquivo']) ?>" target="_blank" class="btn btn-info">
                Ver Arquivo
            </a>
        </div>
    <?php endif; ?>

    <hr>

    <h3>Procedimentos Realizados</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Procedimento</th>
                <th>Categoria</th>
                <th>Quantidade</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($procedimentos as $proc): ?>
                <tr>
                    <td><?= htmlspecialchars($proc['nome']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($proc['categoria'])) ?></td>
                    <td><?= $proc['quantidade'] ?></td>
                    <td>R$ <?= number_format($proc['valor_procedimento'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3>Pagamentos Efetuados</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Forma</th>
                <th>Valor Pago</th>
                <th>Parcelas</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalPago = 0;
            foreach ($pagamentos as $pag): 
                $totalPago += $pag['valor'];
            ?>
                <tr>
                    <td><?= htmlspecialchars(ucfirst($pag['forma_pagamento'])) ?></td>
                    <td>R$ <?= number_format($pag['valor'], 2, ',', '.') ?></td>
                    <td><?= $pag['forma_pagamento'] == 'credito' ? $pag['qtd_parcelas'] . 'x' : 'N/A' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; font-weight: bold;">Total Pago:</td>
                <td><strong>R$ <?= number_format($totalPago, 2, ',', '.') ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <hr>
    
    <a href="<?= BASE_URL ?>index.php" class="btn">Voltar</a>
</div>

<style>
    .detalhes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .detalhes-grid div {
        background-color: #f9f9f9;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #eee;
    }
    .detalhes-arquivo {
        margin: 20px 0;
        padding: 15px;
        background-color: #eef7ff;
        border: 1px solid #b7d7f7;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    hr {
        border: 0;
        border-top: 1px solid #eee;
        margin: 20px 0;
    }
</style>

<?php require_once 'views/footer.php'; ?>
