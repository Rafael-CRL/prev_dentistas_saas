<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/controle_acesso.php';

// Apenas usuários com permissão podem acessar
if (!is_admin() && !is_recepcionista() && !is_dentista()) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';
require_once 'views/header.php';

// Paginação e Busca
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$itensPorPagina = 10;
$offset = ($pagina - 1) * $itensPorPagina;

try {
    // Contagem total para paginação
    $sqlCount = "SELECT COUNT(id) FROM pacientes WHERE 1=1";
    $params = [];
    if (!empty($busca)) {
        $sqlCount .= " AND (nome LIKE :busca OR cpf LIKE :busca)";
        $params[':busca'] = "%$busca%";
    }
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRegistros = $stmtCount->fetchColumn();
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // Busca dos pacientes paginados
    $sqlLista = "SELECT * FROM pacientes WHERE 1=1";
    if (!empty($busca)) {
        $sqlLista .= " AND (nome LIKE :busca OR cpf LIKE :busca)";
    }
    $sqlLista .= " ORDER BY nome ASC LIMIT :limit OFFSET :offset";
    
    $stmtLista = $pdo->prepare($sqlLista);
    if (!empty($busca)) {
        $stmtLista->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
    }
    $stmtLista->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmtLista->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtLista->execute();
    $pacientes = $stmtLista->fetchAll();

} catch (Exception $e) {
    echo "<p class='error'>Erro ao buscar pacientes: " . $e->getMessage() . "</p>";
    $pacientes = [];
    $totalPaginas = 0;
}
?>

<div class="card">
    <h2>Gestão de Pacientes</h2>

    <?php if (isset($_GET['erro'])):
        $erro = $_GET['erro'];
        if ($erro === 'conflito_atendimento') {
            echo "<p class='error'>Não é possível excluir o paciente, pois ele está vinculado a um ou mais atendimentos.</p>";
        }
    endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
        <p class="success">Paciente salvo com sucesso!</p>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'excluido'): ?>
        <p class="success">Paciente excluído com sucesso!</p>
    <?php endif; ?>

    <!-- Formulário para Adicionar Paciente -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Paciente</h3>
        <form action="<?= BASE_URL ?>actions/salvar_paciente.php" method="POST">
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
                    <input type="email" name="paciente_email" id="paciente_email" onblur="validarEmail(this)">
                    <span id="email-error" style="color: red; font-size: 0.8em; display: none;">E-mail inválido</span>
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
                    <input type="text" name="paciente_estado" id="paciente_estado" maxlength="2">
                </div>
            </div>
            <button type="submit" class="btn btn-success">Salvar Novo Paciente</button>
        </form>
    </div>

    <!-- Tabela de Pacientes -->
    <h3 style="margin-top: 2rem;">Pacientes Cadastrados</h3>
    <form method="GET" action="pacientes.php" style="display:flex; gap: 0.5rem; margin-bottom: 1rem;">
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
                            <a href="editar_paciente.php?id=<?= $paciente['id'] ?>" class="btn btn-primary">Editar</a>
                            <a href="<?= BASE_URL ?>actions/excluir_paciente.php?id=<?= $paciente['id'] ?>" class="btn btn-danger" onclick="return confirm('Você realmente deseja remover este paciente? Esta ação não pode ser desfeita.');">Remover</a>
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
            $url = '?' . http_build_query($queryParams);
        ?>
            <a href="<?= $url ?>" class="btn <?= $i === $pagina ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.success { color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px; }
.grid-container { display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; margin-bottom: 1rem; }
.grid-col-2 { grid-column: span 2; }
.grid-col-3 { grid-column: span 3; }
.grid-col-4 { grid-column: span 4; }
.grid-col-6 { grid-column: span 6; }
@media (max-width: 768px) {
    .grid-col-2, .grid-col-3, .grid-col-4, .grid-col-6 { grid-column: span 6; }
}
</style>

<script>
function mascaraCPF(i) {
    var v = i.value;
    v = v.replace(/\D/g, ""); //Remove tudo o que não é dígito
    v = v.replace(/(\d{3})(\d)/, "$1.$2"); //Coloca um ponto entre o terceiro e o quarto dígitos
    v = v.replace(/(\d{3})(\d)/, "$1.$2"); //Coloca um ponto entre o terceiro e o quarto dígitos
    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); //Coloca um hífen entre o terceiro e o quarto dígitos
    i.value = v;
}

function mascaraTelefone(i) {
    var v = i.value;
    v = v.replace(/\D/g, ""); //Remove tudo o que não é dígito
    v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); //Coloca parênteses em volta dos dois primeiros dígitos
    v = v.replace(/(\d)(\d{4})$/, "$1-$2"); //Coloca hífen entre o quarto e o quinto dígitos
    i.value = v;
}

function mascaraCEP(i) {
    var v = i.value;
    v = v.replace(/\D/g, ""); //Remove tudo o que não é dígito
    v = v.replace(/^(\d{5})(\d)/, "$1-$2"); //Coloca hífen entre o quinto e o sexto dígitos
    i.value = v;
}

function validarEmail(field) {
    const usuario = field.value.substring(0, field.value.indexOf("@"));
    const dominio = field.value.substring(field.value.indexOf("@")+ 1, field.value.length);
    const errorSpan = document.getElementById('email-error');

    // Remove a possível estilização inline para garantir o estilo do CSS
    field.style.borderColor = '';

    if (field.value === '') {
        errorSpan.style.display = 'none';
        return;
    }

    if ((usuario.length >=1) && (dominio.length >=3) && (usuario.search("@")==-1) && (dominio.search("@")==-1) && (usuario.search(" ")==-1) && (dominio.search(" ")==-1) && (dominio.search(".")!=-1) && (dominio.indexOf(".") >=1)&& (dominio.lastIndexOf(".") < dominio.length - 1)) {
        errorSpan.style.display = 'none';
    } else {
        errorSpan.style.display = 'block';
    }
}
</script>

<?php require_once 'views/footer.php'; ?>
