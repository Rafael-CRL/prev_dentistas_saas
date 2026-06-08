# Plano de Execução — Prev-Dentistas
> Refatoração para MVC + SaaS Multi-Tenant — Grupo de 5  
> Projeto Integrado II — UFPA

---

## Decisões Tomadas

| Decisão | Definição |
|---|---|
| Dentista multi-clínica | Não — `clinica_id` direto em `usuarios` |
| Banco remoto | Railway (MySQL) |
| Framework | Nenhum — PHP puro com MVC manual |
| Versionamento | Git + GitHub |
| Resposta dos Controllers | Híbrido (ver abaixo) |

---

## Padrão de Resposta dos Controllers

Todo o grupo segue essa regra sem exceção:

**`header()` para ações que encerram um fluxo:**
- Salvar paciente, excluir despesa, registrar atendimento
- Feedback via `$_SESSION['feedback']`

**JSON para buscas dinâmicas:**
- Buscar histórico do paciente sem recarregar a página
- Dados do Dashboard
- `echo json_encode(['status' => 'success', 'data' => $dados])`

---

## Infraestrutura

**Banco remoto — Railway**  
Todos apontam para o mesmo banco via `.env`. O container `db` foi removido do `docker-compose.yml`.

```
# .env (nunca sobe para o git)
DB_HOST=caboose.proxy.rlwy.net
DB_PORT=19103
DB_NAME=railway
DB_USER=root
DB_PASS=xxxxxx
```

**Fluxo de branches:**
```
main          → sempre estável
dev           → integração
feature/xxx   → onde cada um trabalha
```

Convenção: `feature/model-pacientes`, `feature/controller-atendimentos`, `fix/calculo-comissao`

**`.gitignore` essencial:**
```
.env
.gemini/
GEMINI.md
*.pdf
LOCAL_*.md
NOTAS_*.md
vendor/
.DS_Store
Thumbs.db
contextopdf/
```

---

## Estrutura de Pastas

```
/
├── app/
│   ├── Models/              ← classes de acesso ao banco
│   ├── Controllers/         ← classes de lógica e roteamento
│   ├── Views/               ← templates HTML com variáveis PHP
│   └── Services/            ← lógica de negócio complexa (ex: FinanceiroService)
├── config/                  ← configurações do sistema, protegido fora do public/
│   ├── app.php
│   ├── database.php         ← lê do .env
│   ├── seguranca.php
│   └── session.php
├── database/                ← scripts SQL, protegido fora do public/
│   ├── clinica_prev_dentistas.sql
│   └── migration.sql        ← gerado na Fase 3
├── scripts/                 ← utilitários de manutenção, não acessíveis pelo browser
│   ├── setup.php
│   ├── setup_data.php
│   └── verify_saneamento.php
├── public/                  ← único diretório exposto pelo Apache
│   ├── index.php            ← front controller, ponto de entrada único
│   ├── .htaccess            ← redireciona tudo para index.php
│   ├── uploads/             ← arquivos enviados pelos usuários (precisa ser acessível pelo browser)
│   └── assets/
│       ├── css/
│       ├── js/              ← scripts centralizados aqui, sem duplicatas
│       └── img/
├── .env.example
├── .gitignore
├── Dockerfile               ← DocumentRoot aponta para /var/www/html/public
├── docker-compose.yml
├── PLANO_DE_EXECUCAO.md
├── CHANGELOG.md
└── README.md
```

**Por que `public/` é o único diretório exposto:**  
O Apache é configurado para servir arquivos apenas de `/var/www/html/public`. Isso impede que qualquer pessoa acesse `config/database.php`, `app/Models/` ou `scripts/setup.php` diretamente pelo navegador. Sem isso, as credenciais do banco ficam expostas.

---

## Regras do MVC

```
Requisição → public/index.php → Controller → Model → Controller → View → Resposta
```

- View nunca faz query no banco
- Model nunca gera HTML
- Controller nunca contém SQL direto
- Todo acesso externo passa pelo `public/index.php`
- Todo Model filtra obrigatoriamente por `clinica_id` da sessão

---

## Banco de Dados — Estrutura Final

**Tabelas novas:**

| Tabela | Função |
|---|---|
| `clinicas` | Âncora central de todo o sistema multi-tenant |
| `clinica_configuracoes` | Chave-valor para personalizações (logomarca, cores, textos) |
| `clinica_taxas_cartao` | Taxas configuráveis por bandeira e modalidade |
| `clinica_regras_comissao` | Percentuais e metas de comissão por clínica |

**`clinica_id` adicionado nas tabelas existentes:**
`usuarios`, `pacientes`, `procedimentos`, `atendimentos`, `despesas`

**Constraints importantes:**
- `cpf` em `pacientes` → unique composto `(clinica_id, cpf)` — o mesmo CPF pode existir em clínicas diferentes
- `login` em `usuarios` → unique composto `(clinica_id, login)` — dois dentistas de clínicas diferentes podem ter o mesmo login

---

## Divisão de Módulos

| Pessoa | Módulo | Depende de |
|---|---|---|
| A | Pacientes | — |
| B | Procedimentos | — |
| C | Autenticação + Sessão | — |
| D | Atendimentos | A + B |
| E | Financeiro + Dashboard | D |

Pacientes, Procedimentos e Autenticação podem ser desenvolvidos em paralelo.  
Atendimentos só começa após A e B finalizados e em `dev`.  
Financeiro só começa após D finalizado e em `dev`.

---

## Fases de Execução

---

### Fase 1 — DER aprovado ✅
Diagrama desenhado e aprovado com as tabelas definidas acima. Nenhuma alteração estrutural no banco após essa fase sem aprovação do grupo inteiro.

---

### Fase 2 — Saneamento ✅ (CONCLUÍDA)

**Deletar arquivos duplicados**

Remover `relatorio_paciente2.php`, `relatorio_paciente3.php`, `novo_atendimento2.php`, `novo_atendimento3.php` e similares. Antes de deletar, verificar se algum possui lógica única que não existe no arquivo principal — se sim, migrar essa lógica antes de deletar.

**Centralizar JavaScript duplicado**

As funções de máscara de CPF e telefone estão copiadas em quase todos os formulários. Criar `public/assets/js/mascaras.js` com essas funções e remover as cópias inline dos arquivos PHP. Isso garante que uma correção futura acontece em um lugar só.

**Implementar Autoloader PSR-4**

Criar o arquivo `autoload.php` na raiz com a regra: tudo em `app/Models/` pertence ao namespace `App\Models`, tudo em `app/Controllers/` pertence ao namespace `App\Controllers`. Incluir esse autoloader no `public/index.php`. Após isso, nenhum arquivo de classe precisa de `require_once` — o PHP encontra automaticamente pelo nome da classe.

**Configurar o Front Controller**

Criar `public/index.php` as ponto de entrada único. Atualizar o `Dockerfile` para apontar o `DocumentRoot` do Apache para `/var/www/html/public` em vez de `/var/www/html`. Criar `.htaccess` dentro de `public/` redirecionando todas as requisições para o `index.php`. Após isso, nenhum arquivo fora de `public/` será acessível diretamente pelo navegador — `app/`, `config/` e `database.php` ficam protegidos de acesso externo.

```apache
# public/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

### Fase 3 — Migration do banco ✅ (CONCLUÍDA)

Criar um único arquivo `database/migration.sql` versionado no repositório contendo todos os `ALTER TABLE` e `CREATE TABLE` do novo DER. Não aplicar manualmente tabela por tabeta — tudo em um script só para garantir que qualquer membro do grupo pode replicar o estado do banco do zero.

Aplicar no Railway via console do próprio Railway ou via terminal:
```bash
mysql -h caboose.proxy.rlwy.net -P 19103 -u root -p railway < database/migration.sql
```

Validar que todos os 5 conseguem acessar o banco com a nova estrutura antes de avançar.

---

### Fase 4 — Infraestrutura de configuração ✅ (CONCLUÍDA)

**Classe `Config`**  
Criar `app/Models/Config.php` as Singleton — uma única instância que lê `clinica_taxas_cartao` e `clinica_regras_comissao` do banco para a clínica da sessão ativa. Todos os Controllers que precisam de taxas ou comissões consultam essa classe. Após essa fase, nenhum percentual ou taxa pode estar fixo em PHP.

**Classe `FinanceiroService`**  
Criar `app/Services/FinanceiroService.php` centralizando toda a lógica de cálculo: comissão do dentista, taxa do cartão, valor líquido da clínica. Essa classe recebe os dados brutos, consulta o `Config`, faz os cálculos e retorna os valores. O Controller apenas chama o Service e salva o resultado via Model — sem nenhum cálculo financeiro fora dessa classe.

---

### Fase 5 — Refatoração MVC

Para cada módulo, seguir obrigatoriamente essa sequência:

**1. Segurança (Transversal):**
- **Proteção CSRF:** Implementar geração e validação de tokens em todos os formulários (`POST`) para mitigar ataques de Cross-Site Request Forgery.
- **Prevenção de Session Fixation:** Garantir o uso de `session_regenerate_id(true)` no sucesso do login e em trocas de nível de acesso.

**2. Model** — criar a classe em `app/Models/`. Mover todas as queries SQL do arquivo original para métodos da classe. Todo método que busca dados deve incluir `WHERE clinica_id = ?` usando o `clinica_id` da sessão. O Model não sabe o que é HTML, não sabe o que é uma requisição HTTP — só entende de dados.

**3. Controller** — criar a classe em `app/Controllers/`. Recebe os dados da requisição (`$_POST`, `$_GET`), valida, chama o Model, decide o que fazer com o resultado. Para ações, redireciona com `header()`. Para buscas dinâmicas, retorna JSON.

**4. View** — criar o arquivo em `app/Views/`. Apenas HTML com variáveis PHP simples para exibição. Sem queries, sem lógica de negócio, sem cálculos.

Ordem dos módulos:

| # | Módulo | Por que essa ordem |
|---|---|---|
| 1 | Pacientes | Sem dependências, o mais simples para validar o padrão |
| 2 | Procedimentos | Sem dependências, pode ser feito em paralelo com Pacientes |
| 3 | Autenticação | Sem dependências, pode ser feito em paralelo |
| 4 | Atendimentos | Depende de Pacientes e Procedimentos prontos |
| 5 | Financeiro + Dashboard | Depende de Atendimentos e do FinanceiroService |

Não iniciar o próximo módulo sem o anterior testado, revisado e em `dev`.

---

### Fase 6 — Interface

Padronizar componentes visuais — botões, tabelas, cards, formulários — em arquivos parciais de View reutilizáveis. Refatorar o Dashboard para consumir dados reais dos Models Financeiros via JSON. O Dashboard não pode ter nenhuma query direta — tudo vem do `FinanceiroService` através do Controller.

---

## Metas x Fases

| Meta | Fase |
|---|---|
| Banco multi-tenant | 1 + 3 |
| Código limpo e sem duplicatas | 2 |
| Zero hardcode | 4 |
| MVC + POO + Segurança (CSRF) | 5 |
| Design profissional | 6 |

---

## Se sobrar tempo

- Remover `$e->getMessage()` das telas de erro — nunca expor detalhes do banco em produção
- Exclusão lógica com `deleted_at` em vez de `DELETE` direto — preserva histórico e evita erros de FK
- Arredondamento contábil com centavos inteiros no Financeiro — evita perda de precisão com `float`
- Remover `GROUP_CONCAT` do SQL do Dashboard — banco entrega dados puros, View decide como exibir
- Repository Pattern formal com injeção automática de `clinica_id` em todas as queries
- `percentual_bonus` e `valor_meta` em `clinica_regras_comissao`

---

*Projeto Integrado II — UFPA*
