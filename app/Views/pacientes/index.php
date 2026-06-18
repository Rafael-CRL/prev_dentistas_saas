<div class="card">
    <h2>Gestão de Pacientes</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <p class="<?= $_SESSION['feedback']['type'] === 'success' ? 'success' : 'error' ?>">
            <?= $_SESSION['feedback']['message'] ?>
        </p>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

    <!-- Formulário para Adicionar Paciente -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Paciente</h3>
        <form action="<?= BASE_URL ?>pacientes/salvar" method="POST">
            <?= \App\Helpers\CsrfHelper::input() ?>
             <div class="grid-container">
                <div class="form-group grid-col-6">
                    <label for="paciente_nome">Nome Completo</label>
                    <input type="text" name="paciente_nome" id="paciente_nome" required>
                </div>
                <div class="form-group grid-col-3">
                    <label for="paciente_cpf">CPF</label>
                    <input type="text" name="paciente_cpf" id="paciente_cpf" maxlength="14" oninput="mascaraCPF(this)">
                </div>
                 <div class="form-group grid-col-3">
                    <label for="paciente_data_nascimento">Data de Nascimento</label>
                    <input type="date" name="paciente_data_nascimento" id="paciente_data_nascimento">
                </div>
                <div class="form-group grid-col-3">
                    <label for="paciente_telefone">Telefone</label>
                    <input type="text" name="paciente_telefone" id="paciente_telefone" maxlength="15" oninput="mascaraTelefone(this)">
                </div>
                <div class="form-group grid-col-3">
                    <label for="paciente_email">E-mail</label>
                    <input type="email" name="paciente_email" id="paciente_email">
                </div>
                <div class="form-group grid-col-2">
                    <label for="paciente_cep">CEP</label>
                    <input type="text" name="paciente_cep" id="paciente_cep" maxlength="9" oninput="mascaraCEP(this)">
                </div>
                <div class="form-group grid-col-4">
                    <label for="paciente_endereco">Endereço</label>
                    <input type="text" name="paciente_endereco" id="paciente_endereco">
                </div>
                <div class="form-group grid-col-2">
                    <label for="paciente_numero">Número</label>
                    <input type="text" name="paciente_numero" id="paciente_numero">
                </div>
                <div class="form-group grid-col-4">
                    <label for="paciente_bairro">Bairro</label>
                    <input type="text" name="paciente_bairro" id="paciente_bairro">
                </div>
                <div class="form-group grid-col-4">
                    <label for="paciente_cidade">Cidade</label>
                    <input type="text" name="paciente_cidade" id="paciente_cidade">
                </div>
                <div class="form-group grid-col-2">
                    <label for="paciente_estado">Estado</label>
                    <select name="paciente_estado" id="paciente_estado">
                        <option value="">Selecione...</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Salvar Novo Paciente</button>
        </form>
    </div>

    <!-- Tabela de Pacientes -->
    <h3 style="margin-top: 2rem;">Pacientes Cadastrados</h3>
    <form method="GET" action="<?= BASE_URL ?>pacientes" style="display:flex; gap: 0.5rem; margin-bottom: 1rem;">
        <input type="text" name="busca" placeholder="Buscar por nome ou CPF..." value="<?= htmlspecialchars($busca) ?>" style="padding: 5px; flex-grow: 1;">
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>

    <table class="mobile-card-table" style="margin-top: 1rem;">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pacientes) > 0): ?>
                <?php foreach ($pacientes as $paciente): ?>
                    <tr>
                        <td data-label="Nome"><?= htmlspecialchars($paciente['nome']) ?></td>
                        <td data-label="CPF"><?= htmlspecialchars($paciente['cpf'] ?? '') ?></td>
                        <td data-label="Telefone"><?= htmlspecialchars($paciente['telefone'] ?? '') ?></td>
                        <td data-label="Ações" style="display: flex; gap: 0.5rem;">
                            <a href="<?= BASE_URL ?>pacientes/editar?id=<?= $paciente['id'] ?>" class="btn btn-primary">Editar</a>
                            <a href="<?= BASE_URL ?>pacientes/excluir?id=<?= $paciente['id'] ?>" class="btn btn-danger" onclick="return confirm('Você realmente deseja remover este paciente? Esta ação não pode ser desfeita.');">Remover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum paciente encontrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
    <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
        <?php for ($i = 1; $i <= $totalPaginas; $i++):
            $queryParams = $_GET;
            $queryParams['pagina'] = $i;
            $url = BASE_URL . 'pacientes?' . http_build_query($queryParams);
        ?>
            <a href="<?= $url ?>" class="btn <?= $i === $pagina ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.success { color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px; }
.error { color: red; background: #ffebee; padding: 1rem; border-radius: 6px; }
.grid-container { display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; margin-bottom: 1rem; }
.grid-col-2 { grid-column: span 2; }
.grid-col-3 { grid-column: span 3; }
.grid-col-4 { grid-column: span 4; }
.grid-col-6 { grid-column: span 6; }
@media (max-width: 768px) {
    .grid-col-2, .grid-col-3, .grid-col-4, .grid-col-6 { grid-column: span 6; }
}
</style>
