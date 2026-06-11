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
            <strong>Arquivo Anexo:</strong>
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
            <?php foreach ($atendimento['procedimentos'] as $proc): ?>
                <tr>
                    <td><?= htmlspecialchars($proc['nome']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($proc['categoria'] ?? 'N/A')) ?></td>
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
    
    <div style="display: flex; gap: 1rem;">
        <a href="<?= BASE_URL ?>" class="btn btn-secondary">Voltar ao Dashboard</a>
        <a href="<?= BASE_URL ?>atendimentos/recibo?id=<?= $atendimento['id'] ?>" target="_blank" class="btn btn-primary">Ver Recibo</a>
    </div>
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
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .table th, .table td {
        padding: 10px;
        border: 1px solid #eee;
        text-align: left;
    }
    .table th {
        background-color: #f8f9fa;
    }
</style>
