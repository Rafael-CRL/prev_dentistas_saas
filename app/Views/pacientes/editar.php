<div class="card">
    <h2>Editar Paciente</h2>

    <?php if (isset($_SESSION['feedback'])): ?>
        <p class="<?= $_SESSION['feedback']['type'] === 'success' ? 'success' : 'error' ?>">
            <?= $_SESSION['feedback']['message'] ?>
        </p>
        <?php unset($_SESSION['feedback']); ?>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>pacientes/salvar" method="POST">
        <?= \App\Helpers\CsrfHelper::input() ?>
        <input type="hidden" name="paciente_id" value="<?= $paciente['id'] ?>">
        
        <div class="grid-container">
            <div class="form-group grid-col-6">
                <label for="paciente_nome">Nome Completo</label>
                <input type="text" name="paciente_nome" id="paciente_nome" value="<?= htmlspecialchars($paciente['nome']) ?>" required>
            </div>
            <div class="form-group grid-col-3">
                <label for="paciente_cpf">CPF</label>
                <input type="text" name="paciente_cpf" id="paciente_cpf" value="<?= htmlspecialchars($paciente['cpf'] ?? '') ?>" maxlength="14" oninput="mascaraCPF(this)">
            </div>
            <div class="form-group grid-col-3">
                <label for="paciente_data_nascimento">Data de Nascimento</label>
                <input type="date" name="paciente_data_nascimento" id="paciente_data_nascimento" value="<?= $paciente['data_nascimento'] ?>">
            </div>
            <div class="form-group grid-col-3">
                <label for="paciente_telefone">Telefone</label>
                <input type="text" name="paciente_telefone" id="paciente_telefone" value="<?= htmlspecialchars($paciente['telefone'] ?? '') ?>" maxlength="15" oninput="mascaraTelefone(this)">
            </div>
            <div class="form-group grid-col-3">
                <label for="paciente_email">E-mail</label>
                <input type="email" name="paciente_email" id="paciente_email" value="<?= htmlspecialchars($paciente['email'] ?? '') ?>">
            </div>
            <div class="form-group grid-col-2">
                <label for="paciente_cep">CEP</label>
                <input type="text" name="paciente_cep" id="paciente_cep" value="<?= htmlspecialchars($paciente['cep'] ?? '') ?>" maxlength="9" oninput="mascaraCEP(this)">
            </div>
            <div class="form-group grid-col-4">
                <label for="paciente_endereco">Endereço</label>
                <input type="text" name="paciente_endereco" id="paciente_endereco" value="<?= htmlspecialchars($paciente['endereco'] ?? '') ?>">
            </div>
            <div class="form-group grid-col-2">
                <label for="paciente_numero">Número</label>
                <input type="text" name="paciente_numero" id="paciente_numero" value="<?= htmlspecialchars($paciente['numero'] ?? '') ?>">
            </div>
            <div class="form-group grid-col-4">
                <label for="paciente_bairro">Bairro</label>
                <input type="text" name="paciente_bairro" id="paciente_bairro" value="<?= htmlspecialchars($paciente['bairro'] ?? '') ?>">
            </div>
            <div class="form-group grid-col-4">
                <label for="paciente_cidade">Cidade</label>
                <input type="text" name="paciente_cidade" id="paciente_cidade" value="<?= htmlspecialchars($paciente['cidade'] ?? '') ?>">
            </div>
            <div class="form-group grid-col-2">
                <label for="paciente_estado">Estado</label>
                <?php $uf_atual = $paciente['estado'] ?? ''; ?>
                <select name="paciente_estado" id="paciente_estado">
                    <option value="">Selecione...</option>
                    <option value="AC" <?= $uf_atual === 'AC' ? 'selected' : '' ?>>Acre</option>
                    <option value="AL" <?= $uf_atual === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                    <option value="AP" <?= $uf_atual === 'AP' ? 'selected' : '' ?>>Amapá</option>
                    <option value="AM" <?= $uf_atual === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                    <option value="BA" <?= $uf_atual === 'BA' ? 'selected' : '' ?>>Bahia</option>
                    <option value="CE" <?= $uf_atual === 'CE' ? 'selected' : '' ?>>Ceará</option>
                    <option value="DF" <?= $uf_atual === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                    <option value="ES" <?= $uf_atual === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                    <option value="GO" <?= $uf_atual === 'GO' ? 'selected' : '' ?>>Goiás</option>
                    <option value="MA" <?= $uf_atual === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                    <option value="MT" <?= $uf_atual === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                    <option value="MS" <?= $uf_atual === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                    <option value="MG" <?= $uf_atual === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                    <option value="PA" <?= $uf_atual === 'PA' ? 'selected' : '' ?>>Pará</option>
                    <option value="PB" <?= $uf_atual === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                    <option value="PR" <?= $uf_atual === 'PR' ? 'selected' : '' ?>>Paraná</option>
                    <option value="PE" <?= $uf_atual === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                    <option value="PI" <?= $uf_atual === 'PI' ? 'selected' : '' ?>>Piauí</option>
                    <option value="RJ" <?= $uf_atual === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                    <option value="RN" <?= $uf_atual === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                    <option value="RS" <?= $uf_atual === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                    <option value="RO" <?= $uf_atual === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                    <option value="RR" <?= $uf_atual === 'RR' ? 'selected' : '' ?>>Roraima</option>
                    <option value="SC" <?= $uf_atual === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                    <option value="SP" <?= $uf_atual === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                    <option value="SE" <?= $uf_atual === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                    <option value="TO" <?= $uf_atual === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="<?= BASE_URL ?>pacientes" class="btn btn-secondary">Voltar</a>
        </div>
    </form>
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
