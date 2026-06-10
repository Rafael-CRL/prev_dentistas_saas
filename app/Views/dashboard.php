<style>
    /* Estilos para o Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 25px;
        border: 1px solid #888;
        width: 80%;
        max-width: 700px;
        border-radius: 8px;
        position: relative;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
    }
    .modal-close {
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    #modalBody {
        overflow-y: auto;
        line-height: 1.6;
    }
    .modal-content .form-group {
        margin-bottom: 1rem;
    }
    .modal-content .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: var(--text-muted);
    }
    .modal-content .form-group input[readonly],
    .modal-content .form-group textarea[readonly] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Dashboard Financeiro</h1>
    <div style="display:flex; align-items:center; gap: 1rem;">
        <a href="?mes=<?= htmlspecialchars($mes_anterior) ?><?= !empty($busca) ? '&busca=' . urlencode($busca) : '' ?>" class="btn btn-secondary" title="Mês Anterior">&lt;</a>
        <h2 style="color: var(--text-muted); margin: 0;"><?= ucfirst($mesAtual) ?></h2>
        <a href="?mes=<?= htmlspecialchars($mes_proximo) ?><?= !empty($busca) ? '&busca=' . urlencode($busca) : '' ?>" class="btn btn-secondary" title="Próximo Mês">&gt;</a>
    </div>
</div>

<?php if (is_admin()): ?>
<div class="dashboard-grid" style="margin-top: 2rem;">
    <div class="stat-card">
        <h3>Faturamento Bruto</h3>
        <div class="stat-value">R$ <?= number_format($faturamentoBruto, 2, ',', '.') ?></div>
        <p class="text-muted">Total transacionado no mês</p>
    </div>
    
    <div class="stat-card" style="border-left-color: var(--danger-color);">
        <h3>Total de Despesas</h3>
        <div class="stat-value">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></div>
        <p class="text-muted">Soma de custos do mês</p>
    </div>

    <div class="stat-card" style="border-left-color: var(--success-color);">
        <h3>Resultado Líquido</h3>
        <div class="stat-value">R$ <?= number_format($lucroLiquido - $totalDespesas, 2, ',', '.') ?></div>
        <p class="text-muted">Lucro de atendimentos - despesas no mês</p>
    </div>
</div>
<?php endif; ?>

<div class="card" style="margin-top: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Histórico de Atendimentos</h3>
        <div style="display:flex; gap: 1rem; align-items:center;">
            <form method="GET" action="<?= BASE_URL ?>index.php" style="display:flex; gap: 0.5rem;">
                <input type="hidden" name="mes" value="<?= htmlspecialchars($mes_selecionado) ?>">
                <input type="text" name="busca" placeholder="Buscar por paciente..." value="<?= htmlspecialchars($busca) ?>" style="padding: 5px;">
                <button type="submit" class="btn btn-secondary">Buscar</button>
            </form>
            <?php if (is_admin()): ?>
            <a href="<?= BASE_URL ?>atendimentos/cadastrar" class="btn btn-primary">Novo Lançamento +</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="mobile-card-table" style="margin-top: 1rem;">
        <thead>
            <tr>
                <th>Data</th>
                <th>Paciente</th>
                <th>Ações</th>
                <th>Procedimentos</th>
                <th>Dentista</th>
                <?php if (is_admin()): ?>
                <th>Valor Líquido (Clínica)</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if(count($ultimosAtendimentos) > 0): ?>
                <?php foreach($ultimosAtendimentos as $at): ?>
                    <?php
                        $isNaoAplicavel = $at['status_pagamento'] === 'nao_aplicavel';
                    ?>
                <tr class="clickable-row" 
                    data-id="<?= $at['id'] ?>"
                    data-data="<?= date('d/m/Y H:i', strtotime($at['data_atendimento'])) ?>"
                    data-paciente="<?= htmlspecialchars($at['paciente_nome']) ?>"
                    data-taxa-cartao="<?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['taxa_cartao'], 2, ',', '.') ?>"
                    data-custo-auxiliar="<?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['custo_auxiliar'], 2, ',', '.') ?>"
                    data-comissao-dentista="<?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['comissao_dentista'], 2, ',', '.') ?>"
                    data-procedimentos="<?= htmlspecialchars($at['procedimentos'] ?? '') ?>"
                    data-dentista="<?= htmlspecialchars($at['dentista']) ?>"
                    data-arquivo="<?= htmlspecialchars($at['url_arquivo'] ?? '') ?>"
                    data-valor="<?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['valor_liquido_clinica'], 2, ',', '.') ?>"
                    data-bruto="<?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['valor_bruto_total'] ?? 0, 2, ',', '.') ?>"
                    style="cursor: pointer;" title="Clique para ver detalhes">
                    <td data-label="Data"><?= date('d/m/Y H:i', strtotime($at['data_atendimento'])) ?></td>
                    <td data-label="Paciente"><?= htmlspecialchars($at['paciente_nome']) ?></td>
                    <td data-label="Ações">
                        <a href="<?= BASE_URL ?>recibo.php?id=<?= $at['id'] ?>" class="btn btn-secondary" target="_blank" title="Gerar Recibo" onclick="event.stopPropagation();">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                    <td data-label="Procedimentos"><?= htmlspecialchars($at['procedimentos'] ?? '') ?></td>
                    <td data-label="Dentista"><?= htmlspecialchars($at['dentista']) ?></td>
                    <?php if (is_admin()): ?>
                    <td data-label="Valor Líquido" style="color: green; font-weight: bold;">
                        <?= $isNaoAplicavel ? 'N/A' : 'R$ ' . number_format($at['valor_liquido_clinica'], 2, ',', '.') ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="padding: 20px; text-align: center;">Nenhum atendimento registrado ainda.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
    <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <?php 
                $active = $i === $pagina ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                $queryParams = $_GET;
                $queryParams['pagina'] = $i;
                $url = '?' . http_build_query($queryParams);
            ?>
            <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de Detalhes do Atendimento -->
<div id="detalhesModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" id="modalCloseBtn">&times;</span>
        <h2>Detalhes do Atendimento</h2>
        <div id="modalBody" style="line-height: 1.6;">
            <!-- Conteúdo será preenchido via JS -->
        </div>
        <div class="modal-footer">
            <button id="modalFooterCloseBtn" class="btn btn-secondary">Fechar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detalhesModal');
    const modalBody = document.getElementById('modalBody');
    const closeModalBtns = [document.getElementById('modalCloseBtn'), document.getElementById('modalFooterCloseBtn')];

    const closeModal = () => {
        modal.style.display = 'none';
        modalBody.innerHTML = '';
    };

    closeModalBtns.forEach(btn => btn.addEventListener('click', closeModal));

    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });

    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            const data = this.dataset.data;
            const paciente = this.dataset.paciente;
            const procedimentos = this.dataset.procedimentos;
            const dentista = this.dataset.dentista;
            const valor = this.dataset.valor;
            const taxaCartao = this.dataset.taxaCartao;
            const custoAuxiliar = this.dataset.custoAuxiliar;
            const comissaoDentista = this.dataset.comissaoDentista;
            const valorBruto = this.dataset.bruto;
            const arquivo = this.dataset.arquivo;
            const isAdmin = <?= is_admin() ? 'true' : 'false' ?>;
            const baseUrl = '<?= BASE_URL ?>';
            
            let html = `
                <div class="form-group">
                    <label>Data do Atendimento</label>
                    <input type="text" value="${data}" readonly>
                </div>
                <div class="form-group">
                    <label>Paciente</label>
                    <input type="text" value="${paciente}" readonly>
                </div>
                <div class="form-group">
                    <label>Procedimentos</label>
                    <textarea readonly rows="3">${procedimentos}</textarea>
                </div>
                <div class="form-group">
                    <label>Dentista</label>
                    <input type="text" value="${dentista}" readonly>
                </div>
            `;

            if (isAdmin) {
                html += `
                    <div class="form-group">
                        <label>Valor Bruto</label>
                        <input type="text" value="${valorBruto}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Taxa do Cartão</label>
                        <input type="text" value="${taxaCartao}" readonly>
                    </div>
                `;
            }

            html += `
                <div class="form-group">
                    <label>Custo Auxiliar</label>
                    <input type="text" value="${custoAuxiliar}" readonly>
                </div>
                <div class="form-group">
                    <label>Comissão do Dentista</label>
                    <input type="text" value="${comissaoDentista}" readonly>
                </div>
            `;

            if (isAdmin) {
                html += `
                    <div class="form-group">
                        <label>Valor Líquido (Clínica)</label>
                        <input type="text" value="${valor}" readonly>
                    </div>
                `;
            }

            if (arquivo) {
                html += `
                    <div class="form-group" style="margin-top: 1rem; border-top: 1px solid #eee; padding-top: 1rem;">
                        <label>Arquivo Anexado</label>
                        <div style="display: flex; gap: 10px;">
                            <a href="${baseUrl}${arquivo}" target="_blank" class="btn btn-info" style="display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                <span style="font-size: 1.2em;">👁</span> Visualizar
                            </a>
                            <a href="${baseUrl}${arquivo}" download class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                <span style="font-size: 1.2em;">⬇</span> Download
                            </a>
                        </div>
                    </div>
                `;
            }

            modalBody.innerHTML = html;
            modal.style.display = 'block';
        });
    });
});
</script>
