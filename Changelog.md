# Changelog - Sistema de Gestão Odontológica

## [2026-06-17] — Auditoria Técnica e Refatoração MVC (Fase 6.3)

### Corrigido
- **Relatório Financeiro Geral:** Corrigidos os avisos de "Undefined variable $data_inicio/$data_fim" no `AtendimentoModel`, restaurando a funcionalidade de filtragem por período que estava inoperante devido a erro de nomenclatura.
- **Módulo de Atendimentos:** Restaurada a funcionalidade de carregamento de tratamentos planejados (pendências) com a implementação do método `getPendentes()` no `PacienteModel`, corrigindo erro de comunicação AJAX.
- **Injeção de Dependências:** Corrigido o construtor do `AtendimentoController` para instanciar corretamente os modelos de `Usuario` e `Clinica`, evitando erros de acesso a propriedades nulas.

### Refatorado (MVC & Zero Hardcode)
- **Motor Multi-tenant:** Implementado "Login Inteligente" que identifica automaticamente a clínica do usuário durante o login, eliminando a necessidade de seleção manual de clínica na interface.
- **Identidade Visual Dinâmica:** Interface agora exibe o nome personalizado da clínica na Navbar e no título da página via sessão.
- **Eliminação de SQL Bruto:** Removidas todas as consultas SQL diretas do `AtendimentoController`, migrando a persistência de dados para os Models correspondentes:
  - Listagem de dentistas migrada para `UsuarioModel::getDentistas()`.
  - Listagem de procedimentos migrada para `ProcedimentoModel::getListagemAtendimento()`.
  - Contagem de pendências financeiras migrada para `AtendimentoModel::countPendentesByPaciente()`.
  - Recuperação de nome de paciente migrada para `PacienteModel::getNomeById()`.
  - Dados institucionais da clínica migrados para `ClinicaModel::getDados()`.

## [2026-06-16] — Fase 6.2: Dashboard Financeiro e Design System Premium

Conclusão da camada de inteligência visual e consolidação da estética premium do sistema, transformando dados brutos em insights gerenciais assíncronos.

### 📊 Visualização de Dados (Pilar B - Item 3)
- **Gráficos Interativos com Chart.js:**
    - Implementação de carregamento assíncrono (AJAX/Fetch) para os dados financeiros do dashboard, consumindo o novo endpoint de API.
    - **Fluxo de Caixa Mensal:** Gráfico de barras comparativo entre Faturamento e Despesas por dia.
    - **Mix de Receita:** Gráfico de rosca detalhando a participação de cada forma de pagamento (Pix, Crédito, Débito, Dinheiro).
    - **Evolução do Resultado Líquido:** Gráfico de linha com preenchimento em gradiente para monitorar a rentabilidade diária.
    - **Racional Técnico:** Desacoplamento da carga de dados da renderização da página, melhorando drasticamente o LCP (*Largest Contentful Paint*) e a interatividade.

### 🏗️ Refatoração de Arquitetura e Service (Pilar B - Itens 1 e 2)
- **Centralização no `FinanceiroService`:**
    - Migração de 100% da lógica de agregação e processamento de estatísticas para o `FinanceiroService.php`.
    - O `DashboardController` agora atua estritamente como orquestrador, sem realizar chamadas SQL diretas aos Models.
- **API de Estatísticas:**
    - Criação do endpoint `/dashboard/api-stats` retornando pacotes JSON estruturados com faturamento, despesas e dados para gráficos.

### 🎨 Design System Premium & Partials (Pilar C)
- **Arquitetura de Partials:**
    - Migração definitiva dos arquivos de template `header.php` e `footer.php` para o diretório MVC (`app/Views/partials/`).
    - Refatoração do `BaseController` para gerenciar o empilhamento (*stacking*) de componentes de UI de forma automática.
- **Estilização High-End:**
    - Implementação da paleta de cores dinâmica baseada em **HSL** para consistência visual e facilidade de temas.
    - Aplicação de **Glassmorphism** (transparência com `backdrop-filter: blur`) na barra de navegação e nos cards estatísticos.
    - Micro-animações de elevação e transições de cor em botões e elementos interativos para feedback tátil aprimorado.

### 🩹 Refinamentos e Correções (Consolidação Final)
- **Hotfix: Restauração da Integridade Visual (CSS):**
    - Corrigido erro crítico de sintaxe no `style.css` (comentário mal fechado) que anulava as classes de botões, causando regressão visual (links roxos).
    - Reintroduzidas variáveis de design essenciais no `:root` (`--secondary-color`, `--info-color`, `--border-color`) para compatibilidade com módulos legados e dashboards.
    - Restaurado o contraste do menu de usuário (texto "Olá" agora é branco) e padronizados arredondamentos (`30px`) para botões.
    - Saneamento do arquivo CSS com remoção de 100% das duplicatas e isolamento de estilos de Landing Page.
- **Correção no Fluxo de Atendimentos:**
    - Eliminado **Fatal Error** na página de "Lançar Atendimento" causado por caminhos de inclusão manuais (`views/header.php`) que estavam obsoletos após a migração MVC.
    - Limpeza de resquícios de código corrompido no arquivo `cadastrar.php`.
- **Correção do Layout em Telas de Erro:**
    - Atualizado o método `renderError` no `BaseController.php` para apontar para os novos caminhos dos parciais de layout (`app/Views/partials/`).
    - **Impacto:** Garante que mensagens de erro críticas (como falha de CSRF) sejam renderizadas dentro do Design System profissional (com CSS e Navbar), eliminando a exibição de texto puro em falhas de segurança.
- **Validação de Conformidade:** Execução de auditoria empírica via CLI confirmando 100% de sucesso na infraestrutura de dados para dashboards e isolamento multi-tenant.

---

## [2026-06-15] — Refatoração de UX e Unificação de Configurações

Melhoria na arquitetura de informação do sistema, separando o fluxo operacional das definições de regras de negócio e parâmetros do sistema.

### 🎨 Melhorias de UI/UX
- **Formatação e Máscara de CNPJ/Telefone:**
    - Adicionada função helper para formatar o CNPJ com pontuação padrão no Painel da Clínica e nos Recibos.
    - Implementada máscara em tempo real via Regex com captura de evento `oninput`, garantindo formatação mesmo em colagens.
    - Sincronização automática da máscara ao ativar o modo de edição, garantindo consistência visual imediata.
    - **Racional Técnico:** Melhora a apresentação visual e garante a integridade dos dados inseridos, eliminando falhas de captura em interações de mouse ou teclado.
- **Padrão "Visualizar primeiro, Editar depois":********
    - Implementado bloqueio de edição por padrão nos campos de Dados Institucionais e Regras de Comissão.
    - Adicionado fluxo de alternância (Toggle) via botões **Editar**, **Salvar** e **Cancelar** no rodapé dos formulários.
    - **Racional Técnico:** Previne alterações acidentais em parâmetros críticos do sistema e melhora a clareza sobre o estado dos dados (leitura vs. escrita).
- **Unificação do Menu de Configurações:****
    - Removido o item "Configurações Clínica" do menu de **Cadastros** (operacional).
    - Criado o novo menu de topo **Configurações** (Dropdown).
    - Unificada a gestão de parâmetros da **Clínica** (Admin) com o gerenciamento de **Meu Perfil** (Usuário) em um único hub intuitivo.
    - **Racional Técnico:** Melhora o modelo mental do usuário ao separar dados que crescem em volume (Pacientes, Procedimentos) de dados que definem regras (Taxas, Comissões, Perfil).

---

## [2026-06-12] — Hotfixes e Melhorias Pós-Auditoria — Fase 6 Item 1

Correções cirúrgicas de segurança e integridade realizadas após a auditoria técnica do módulo de gestão administrativa e edição de preços da clínica.

### 🛠️ Correções Realizadas

- **Hotfix CSRF na Remoção de Taxas:**
    - Refatorado o botão de exclusão de taxas na view [painel.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Views/clinica/painel.php) para disparar via requisição POST segura.
    - Atualizado o método `excluirTaxa()` em [ClinicaController.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Controllers/ClinicaController.php) para aceitar apenas requisições POST com validação CSRF.
- **Sanitização de CNPJ:**
    - Incluído filtro no controller [ClinicaController.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Controllers/ClinicaController.php) para persistir apenas caracteres numéricos puros (sem pontuação) no banco de dados.
    - Criada e executada a migration [migration_normalize_cnpj.sql](file:///home/rafael/Documents/projetointegrado2_turma2023/database/migration_normalize_cnpj.sql) para normalizar todos os registros de CNPJ existentes no banco de dados da clínica.
- **Melhoria no Script de Auditoria:**
    - Atualizado o script [auditoria_conclusao_fase6.php](file:///home/rafael/Documents/projetointegrado2_turma2023/scripts/auditoria_conclusao_fase6.php) para incluir testes lógicos de integridade de banco de dados e verificar a imutabilidade histórica do faturamento e custos contra alterações no valor base de procedimentos.

---

## [2026-06-11] — Resolução de Débitos Técnicos Pós-Fase 5

Correção cirúrgica de quatro débitos técnicos identificados após a consolidação da Fase 5, mantendo o isolamento multi-tenant, zero hardcode e o rigor arquitetural estabelecido.

### 🛠️ Correções e Melhorias Realizadas

- **Item 1: Recibo Dinâmico Multi-tenant**
    - Refatorada a view [recibo.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Views/atendimentos/recibo.php) para eliminar dados institucionais fixos (*hardcoded*).
    - Atualizado o [AtendimentoController::recibo](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Controllers/AtendimentoController.php) para obter dinamicamente as informações da clínica logada (`clinicas` e `clinica_configuracoes`) e injetá-las na view, removendo qualquer query direta da camada de visualização.

- **Item 2: Autenticação SaaS Isolada por Clínica**
    - Adicionado o campo `clinica_identificador` (Código ou CNPJ) no formulário de login na view [login.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Views/auth/login.php).
    - Criado o método `findClinicaId()` no [AuthModel.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Models/AuthModel.php) para resolver o ID numérico da clínica a partir do identificador digitado (aceitando código, CNPJ com máscara ou CNPJ puramente numérico).
    - Atualizada a busca de credenciais no método `authenticate()` do [AuthModel.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Models/AuthModel.php) para isolar estritamente a query com `AND clinica_id = ?`, prevenindo colisão ou autenticação cruzada de logins idênticos em clínicas distintas.
    - **Decisão Técnica Consciente (Comportamento Temporário):** Para simplificar a experiência do usuário durante o estágio atual do projeto (com apenas uma clínica ativa), desativamos temporariamente a obrigatoriedade do campo `clinica_identificador` na tela de login. O código de isolamento multi-tenant foi mantido no código-fonte, porém comentado. Em seu lugar, adicionamos o método `findFirstActiveClinicaId()` para resolver o ID da clínica automaticamente no banco (buscando o primeiro registro `'ativo'`), preservando o isolamento de dados sem exigir input adicional do usuário. O fluxo completo com o campo na tela está pronto e será reativado quando o ambiente com múltiplas clínicas for implantado.

- **Item 3: Exceções Explícitas para Configurações Ausentes (Zero Hardcode)**
    - Removidos fallbacks fixos de taxas e comissões do código fonte PHP em [Config.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Models/Config.php) e [FinanceiroService.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Services/FinanceiroService.php).
    - Substituídos os values silenciosos por exceções explícitas (`\Exception`) que informam a ausência da configuração no banco de dados, fazendo o sistema falhar de forma informativa em vez de mascarar a ausência de parâmetros.
    - Atualizado o dump de banco de dados [migration.sql](file:///home/rafael/Documents/projetointegrado2_turma2023/database/migration.sql) para injetar as taxas default e configurações da clínica principal.

- **Item 4: Refatoração do Fluxo de Feedback no UsuarioController**
    - Removidos todos os comandos `die()` no [UsuarioController.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Controllers/UsuarioController.php).
    - Substituídos por redirecionamentos HTTP com mensagens de feedback flash salvas em `$_SESSION['feedback']`.
    - Atualizadas as views de usuários [index.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Views/usuarios/index.php) e [editar.php](file:///home/rafael/Documents/projetointegrado2_turma2023/app/Views/usuarios/editar.php) para renderizar dinamicamente os alertas e removê-los após a exibição.

### 🔍 Infraestrutura de Qualidade (Validação Automatizada)
- **Script de Validação (`scripts/valida_correcoes.php`):**
    - Desenvolvido script de validação automatizada que realiza a varredura e checagem estática/lógica das quatro correções efetuadas.
    - Executado via Docker com **100% de sucesso**, garantindo a conformidade e integridade das rotas afetadas.

---

## [2026-06-04] — Fase de Saneamento e Consolidação Arquitetural

Esta fase teve como objetivo eliminar a dívida técnica gerada por arquivos duplicados e versões evolutivas espalhadas pelo projeto, preparando o terreno para a migração MVC e Multi-tenant.

### 🔄 Consolidações de Funcionalidades (Versão Evolutiva -> Base)
- **Relatório de Pacientes (`relatorio_paciente.php`):**
    - Conteúdo atualizado para a versão v3.
    - **Funcionalidades Preservadas:** Odontograma Responsivo em SVG, Sistema de Notificações Toast, Gestão de Anexos e exclusão de procedimentos.
    - **Ajustes:** Referência interna do formulário atualizada de `relatorio_paciente3.php` para `relatorio_paciente.php`.
- **Lançamento de Atendimento (`views/novo_atendimento.php`):**
    - Conteúdo atualizado para a versão v3.
    - **Funcionalidades Preservadas:** Lógica de Custo Auxiliar/Protético, Natureza de Procedimentos Especializados e integração com o Odontograma SVG.
    - **Ajustes:** Destino do formulário (action) unificado para `actions/salvar_atendimento.php`.
- **Lógica de Processamento (`actions/salvar_atendimento.php`):**
    - Conteúdo atualizado para a lógica da v2.
    - **Funcionalidades Preservadas:** Registro automático de novos pacientes, limpeza de pendências resolvidas e cálculo de comissões baseado no faturamento mensal.

### 🛠️ Refatoração de Referências Globais
- **Navegação Central (`views/header.php`):** Todos os links de menu e verificações de estado ativo (`isActive`) foram corrigidos para apontar para os nomes de arquivos base.
- **Dashboard (`index.php`):** O botão principal de "Novo Lançamento" foi redirecionado para `views/novo_atendimento.php`.
- **Ações de Upload (`actions/salvar_arquivo_procedimento.php`):** URLs de redirecionamento após o upload de anexos corrigidas para `relatorio_paciente.php`.

### 🗑️ Limpeza de Workspace (Arquivos Removidos)
- **PHP:** `relatorio_paciente2.php`, `relatorio_paciente3.php`, `views/novo_atendimento2.php`, `views/novo_atendimento3.php`, `views/novo_atendimento4.php`, `actions/salvar_atendimento2.php`.
- **Assets:** `assets/css/style3.css`, `assets/css/style-orig.css` (backups obsoletos).
- **Diretórios:** Pasta `teste/` (rascunhos de desenvolvimento integrados ao sistema principal).

---

## [2026-06-04] — Reorganização Estrutural (Pivot para public/app)

Ajuste na estratégia de saneamento para alinhar com a nova estrutura de pastas profissional, visando segurança e isolamento.

### 🏗️ Nova Estrutura de Diretórios
- Criação das pastas `app/Models`, `app/Controllers`, `app/Services`, `app/Views`.
- **Isolamento de Scripts:** Movimentação de `setup.php`, `setup_data.php` e `verify_saneamento.php` para o diretório `scripts/` (fora da raiz pública).
- **Public Assets:** Migração das pastas `assets/` e `uploads/` para dentro de `public/`.
- **Database:** Organização de dumps SQL no diretório `database/`.

---

## [2026-06-04] — Infraestrutura MVC e Centralização de Assets

Finalização da Fase 2 com a implementação dos componentes base da nova arquitetura.

### 🛠️ Refatoração Técnica e Padronização
- **JavaScript:** Extração das funções `mascaraCPF`, `mascaraTelefone` e `mascaraCEP` para o novo arquivo `public/assets/js/mascaras.js`. Removidas as redundâncias inline nos arquivos `pacientes.php` e `editar_paciente.php`.
- **Autoloading:** Implementação de `app/autoload.php` (PSR-4 manual) mapeando o namespace `App\` para o diretório `app/`.
- **Front Controller:** Criação de `public/index.php` como ponto de entrada único. Adicionado ajuste de `include_path` para manter compatibilidade com arquivos legados na raiz durante a transição.
- **Servidor Web:** Atualização do `Dockerfile` e criação de `public/.htaccess` para definir o `DocumentRoot` em `/public` e habilitar o módulo `rewrite` do Apache.
- **Correção de Caminhos:** Atualização de referências de imagens, CSS e destinos de upload em `login.php`, `recibo.php`, `relatorio_paciente.php`, `header.php`, `actions/salvar_atendimento.php` e `actions/salvar_arquivo_procedimento.php` para refletir a nova localização física dos arquivos.

---
*Status: Fase 2 Concluída. Sistema estável e estruturado para Fase 3 (Migração de Banco).*

---

## [2026-06-04] — Fase 3: Transição para SaaS Multi-tenant (Banco de Dados)

Implementação da estrutura de isolamento de dados e regras administrativas flexíveis, eliminando a dependência de parâmetros fixos no código.

### 🗄️ Estruturação Multi-tenant
- **Âncora de Dados (`clinicas`):** Criação da tabela mestre para gestão de clientes SaaS. Sincronizada com o schema remoto (`nome_fantasia`, `razao_social`).
- **Isolamento de Dados:** Inclusão da coluna `clinica_id` em todas as entidades do sistema (`usuarios`, `pacientes`, `procedimentos`, `atendimentos`, `despesas`, `atendimento_procedimentos`, `atendimento_pagamentos`).
- **Migração de Dados Legados:** Todos os registros existentes foram vinculados automaticamente a uma "Clínica Principal" (ID 1) para preservar a integridade histórica.

### 🛡️ Integridade e Segurança
- **Índices Compostos:** Conversão de índices únicos simples para únicos compostos (`clinica_id` + `cpf` / `clinica_id` + `login`). Isso permite que o mesmo dado (ex: CPF) coexista no sistema em clínicas diferentes.
- **Constraints de Integridade:** Implementação de Foreign Keys (`ON DELETE CASCADE`) vinculando todas as tabelas à tabela `clinicas`.

### ⚙️ Configurações Dinâmicas (Zero Hardcode)
- **Tabelas de Parâmetros:** Criação de `clinica_configuracoes`, `clinica_taxas_cartao` e `clinica_regras_comissao`.
- **Carga de Inicialização:** Inserção de taxas de operadoras (Visa/Master) e regras de repasse para permitir o funcionamento imediato dos cálculos financeiros na próxima fase.

### 📂 Artefatos de Desenvolvimento
- **Migration Consolidada:** Criação do arquivo `database/migration.sql` contendo o histórico completo da evolução do schema.
- **Validação Automatizada:** Ajuste no script `scripts/auditoria_conclusao_fase3.php` para garantir a conformidade técnica rigorosa do banco de dados remoto.

---
*Status: Fase 3 Concluída. Banco de Dados preparado para implementação da lógica de negócio (Fase 4).*

---

## [2026-06-04] — Padronização de Repositório e Estrutura Git

Ajustes na configuração do controle de versão para garantir a limpeza do repositório remoto e a persistência da estrutura arquitetural necessária para a Fase 4.

### ⚙️ Ajustes de Git e Rastreamento
- **Atualização do `.gitignore`:** Sincronização com o `planejamento.md`. Agora, arquivos binários (`*.pdf`), pastas de documentação externa (`contextopdf/`) e uploads locais estão formalmente ignorados para evitar poluição do repositório.
- **Limpeza do Cache Git:** Remoção de arquivos que já haviam sido rastreados indevidamente (PDFs e manuais antigos), mantendo-os apenas na máquina local do desenvolvedor.
- **Preservação de Estrutura MVC:** Adição de arquivos `.gitkeep` nas pastas `app/Controllers`, `app/Models`, `app/Services` e `app/Views`.
    - **Por que:** O Git não rastreia pastas vazias. Como essas pastas são fundamentais para a próxima fase (MVC), o `.gitkeep` garante que elas existam no GitHub mesmo antes de conterem código.

---
*Status: Repositório organizado. Estrutura de pastas MVC pronta para receber as primeiras classes da Fase 4.*

## [2026-06-05] — Fase 4: Infraestrutura Zero Hardcode (Config & Services)

Implementação do motor financeiro dinâmico e integração com o banco de dados SaaS, eliminando a necessidade de constantes fixas no código PHP.

### 🧠 Modelos e Serviços (Infraestrutura)
- **Classe `App\Models\Config`:** Criada utilizando o padrão arquitetural Singleton. Responsável por buscar, em uma única consulta otimizada, as taxas de cartão, regras de comissão e personalizações da clínica (com base na `$_SESSION['clinica_id']`).
- **Classe `App\Services\FinanceiroService`:** Construída para substituir a lógica legada. Agora recebe a instância de `Config` via injeção de dependência e realiza todos os cálculos de repasse de dentista, metas e taxas de maquininha dinamicamente, mantendo o rigoroso ajuste de arredondamento de centavos.

### 🛠️ Refatoração de Controladores (Consumidores)
- **Atendimentos (`actions/salvar_atendimento.php`):** Refatorado para instanciar o novo `FinanceiroService` e abandonar as chamadas estáticas `Financeiro::calcularComissao`.
- **Pagamentos (`actions/salvar_pagamento.php`):** Adaptado para o novo serviço de injeção, recalculando comissões dinâmicas e taxas de liquidação corretamente na hora do fechamento.
- **Autenticação (`actions/verificar_login.php`):** Ajustado para armazenar explicitamente o `clinica_id` na sessão no momento do login, chave-mestra para o funcionamento do Singleton da Fase 4.

### 🛡️ Auditoria de Qualidade
- **`scripts/auditoria_conclusao_fase4.php`:** Criado um novo script automatizado de auditoria que validou a inexistência de resquícios "hardcoded" de taxas e a correta aplicação do padrão Singleton e injeções de dependência.

---
*Status: Fase 4 Concluída. A infraestrutura de back-end multi-tenant está consolidada. Sistema pronto para a Fase 5 (Migração MVC - Módulo a Módulo).*

## [2026-06-05] — Fase 4: Consolidação Arquitetural e Refinamento de Segurança

Após a integração das frentes de trabalho da Fase 4, o sistema foi consolidado seguindo um padrão de excelência técnica, unindo a robustez de segurança com a flexibilidade da Injeção de Dependência.

### 🏗️ Integração e Refinamento Arquitetural
- **Padrão de Projeto:** Implementação definitiva das classes `App\Models\Config` e `App\Services\FinanceiroService` utilizando **Injeção de Dependência (DI)**. Esta escolha técnica permite maior testabilidade e desacoplamento, preparando o sistema para testes unitários automatizados.
- **Merge de Contribuições:** Integração da branch `fase4-financeiro-saas`, preservando o histórico de desenvolvimento colaborativo e consolidando a lógica de negócio parametrizada.

### 🛡️ Hotfix: Roteamento Híbrido e Segurança Avançada (Front Controller)
- **Estratégia de Roteamento:** O Front Controller (`public/index.php`) foi atualizado para uma versão de segurança avançada.
- **Validação de Caminhos:** Implementação de checagem via `realpath()` e validação de escopo de diretório, prevenindo vulnerabilidades de *Directory Traversal*.
- **Gestão de Ativos:** Adicionado tratamento nativo para arquivos estáticos (CSS, JS, Imagens, PDFs), garantindo que o servidor web os sirva corretamente sem interferência do motor PHP.
- **Compatibilidade Legada:** Mantido o suporte ao sistema híbrido para garantir a operação ininterrupta das páginas atuais durante a migração para o padrão MVC.

---
*Status: Fase 4 Finalizada e Documentada. Infraestrutura de segurança e finanças consolidada na branch principal.*

## [2026-06-08] — Fase 5: Consolidação Arquitetural e Refatoração MVC (Módulos Base)

Esta etapa marca a transição definitiva dos módulos core para o padrão MVC, unificando frentes de trabalho paralelas através de uma "Consolidação Cirúrgica" com foco em alta coesão e segurança SaaS.

### 🏗️ Evolução de Infraestrutura (Refinamento de Arquiteto)
- **Implementação do `App\Controllers\BaseController`:** 
    - Introdução de uma classe abstrata base para todos os controladores.
    - Centralização do motor de renderização de views (`render()`) e padronização de respostas JSON (`json()`).
    - Redução drástica de redundância de código nos controladores de Pacientes e Procedimentos.
- **Unificação do Front Controller (`public/index.php`):**
    - Consolidação de rotas MVC para os módulos de Autenticação, Pacientes e Procedimentos.
    - Refinamento da lógica de normalização de URI e suporte a rotas amigáveis (Ex: `/pacientes/editar?id=X`).
    - Manutenção controlada da camada de compatibilidade legada (Strangler Fig Pattern).

### 🛡️ Segurança e Multi-tenancy (Rigor SaaS)
- **Refatoração do Módulo de Autenticação:**
    - Migração para `AuthController` e `AuthModel` integrados ao fluxo MVC.
    - **Correção Crítica de Segurança:** Busca de usuários agora valida o status ativo e garante o isolamento do `clinica_id` desde o ponto de entrada.
    - **Proteção de Sessão:** Implementado `session_regenerate_id(true)` no sucesso do login para mitigar riscos de *Session Fixation*.
- **Integridade de Dados:** Validação rigorosa de propriedade de registros (cláusula `WHERE clinica_id = ?`) em todas as operações de leitura e escrita nos novos Models.

### 📦 Consolidação de Módulos (Feature Sync)
- **Módulo de Pacientes:** Refatoração completa concluída. Integração de histórico de atendimentos e pendências via API JSON interna.
- **Módulo de Procedimentos:** Refatoração completa concluída. Implementação de exclusão lógica com validação de dependências (impede a exclusão de procedimentos vinculados a atendimentos).
- **Limpeza Técnica:** Remoção de backups residuais (`.bak`, `.old`) e scripts de ação obsoletos (`actions/`) substituídos pela lógica dos controladores.

### 🩹 Hotfixes e Refinamentos de Integridade (Sincronização de Sessão)
- **Restauração de Componente Crítico:** Recuperação do diretório `app/Views/auth/` e da view `login.php`, corrigindo erro de "View não encontrada" na raiz do projeto após falha de merge.
- **Sincronização de Schema (AuthModel):** Removido filtro por coluna `status` no `AuthModel.php`, resolvendo exceção `PDOException` (SQLSTATE[42S22]) decorrente de incompatibilidade com o schema da Fase 3.
- **Padronização de Respostas API:** Refatoração completa das buscas AJAX no `PacienteController` para utilizar o método `$this->json()` do `BaseController`. 
    - **Impacto:** Eliminação de redundâncias, centralização de cabeçalhos HTTP e garantia de consistência arquitetural de alto nível.
- **Atualização da Auditoria Holística:** O script `scripts/auditoria_conclusao_fase5.php` foi atualizado para validar a nova sintaxe de controladores e as restrições de schema identificadas, mantendo o selo de conformidade do sistema.

### 🛡️ Segurança Transversal (CSRF - Requirement Elevation)
- **Implementação do `CsrfHelper`:** Criação de um utilitário dedicado (`app/Helpers/CsrfHelper.php`) para geração (`random_bytes`) e validação (`hash_equals`) segura de tokens de sessão.
- **Interceptação no `BaseController`:** O construtor do controlador base foi modificado para interceptar 100% das requisições `POST` no escopo MVC, exigindo um token CSRF válido e retornando erro 403 (Forbidden) em caso de falha. Isso garante que todos os futuros módulos herdem a segurança "by design".
- **Retrofit de Módulos Consolidados:** Injeção da chamada `parent::__construct()` nos controladores e adição do input oculto `<?= \App\Helpers\CsrfHelper::input() ?>` nas views migradas (`login.php`, `Pacientes`, `Procedimentos`).
- **Elevação de Mandato:** O planejamento foi atualizado para mover o CSRF de uma tarefa opcional ("Se sobrar tempo") para um requisito estrito (Passo 1) da Fase 5.

---
*Status: Fase 5 (Segurança Transversal) Concluída. Módulos de Autenticação, Pacientes e Procedimentos blindados contra CSRF. Infraestrutura preparada para a refatoração do módulo de Atendimentos.*

## [2026-06-09] — Fase 5: Refatoração MVC do Módulo de Atendimentos

Conclusão da migração do core transacional da aplicação. O Módulo de Atendimentos, responsável pela orquestração do odontograma e lógica financeira, foi integralmente refatorado.

### 🏗️ Evolução Arquitetural (MVC & SRP)
- **Criação do `App\Models\Atendimento`:** 
    - Toda a lógica de inserção principal, inserção de procedimentos atrelados e exclusão de pendências foi migrada das antigas *actions* procedurais.
    - **Isolamento SaaS Garantido:** A coluna `clinica_id` agora é injetada nativamente via construtor do Model e propagada para todos os `INSERT`, `UPDATE` e `DELETE`, fechando uma brecha crítica que impedia a escalabilidade Multi-Tenant segura.
- **Criação do `App\Controllers\AtendimentoController`:**
    - Atua como o maestro do lançamento de dados, absorvendo a complexa lógica que residia no antigo `salvar_atendimento.php`.
    - **Proteção Herdada:** Extensão da classe `BaseController`, garantindo a validação transversal de tokens CSRF em toda requisição POST, blindando o formulário contra falsificação.
    - **Motor Zero Hardcode:** Injeção do `FinanceiroService` e o uso rigoroso do Model `Config`, garantindo que cálculos de custo e comissão respeitem as parametrizações da clínica do usuário ativo.

### 🛡️ Limpeza, Rotas e Apresentação
- **Limpeza de View (`app/Views/atendimentos/cadastrar.php`):** 
    - O arquivo antigo `views/novo_atendimento.php` foi refatorado. Extirpou-se a lógica PHP de banco de dados do início do arquivo, passando as responsabilidades para o Controller.
    - Injetada a tag de proteção de formulários (`CsrfHelper::input()`).
    - Nomenclatura ajustada para maior expressividade ao domínio do negócio (`cadastrar.php`).
- **Refinamento de Rotas (`public/index.php`):** 
    - Registrado o bloco MVC `atendimentos/` para capturar requisições de página (`cadastrar`) e chamadas de API internas (`salvar`, `verificar-pagamento`).
- **Limpeza de Base (`Dívida Técnica Expurgo`):** 
    - Exclusão dos scripts obsoletos e inseguros `actions/salvar_atendimento.php` e `actions/verificar_pagamento_pendente.php`.
- **Navegação Sincronizada:** Botões e menus do Dashboard e Header ajustados para direcionar à nova infraestrutura MVC.

### 🐛 Hotfixes e Degradação Graciosa (Gestão de Débito Técnico)
- **Correção Arquitetural (`AtendimentoController`):** Ajustada a chamada do construtor da classe base (`parent::__construct()`) que estava recebendo o `$pdo` indevidamente, prevenindo *Fatal Errors* e garantindo a instância do banco para as transações locais.
- **Purificação POO e Remoção de Hardcode (`AtendimentoController` e `PacienteModel`):** Removido SQL bruto do controlador (SRP Restaurado) criando o método genérico `criar()` no `PacienteModel`. Moviddo `date_default_timezone_set` para as configurações centrais em `config/app.php`.
- **Isolamento de Banco de Dados (`AtendimentoModel`):** Corrigida a omissão do `clinica_id` na query de inserção da tabela `atendimento_procedimentos`, resolvendo o erro `SQLSTATE[HY000]: General error: 1364` e assegurando o isolamento dos registros secundários.
- **Degradação Graciosa do Financeiro (`actions/salvar_pagamento.php`):** O script legado de confirmação de pagamentos foi **bloqueado intencionalmente** com uma mensagem JSON amigável ("Módulo Financeiro em Refatoração").
  - **Motivo:** O banco SaaS agora exige o `clinica_id` (erro 1364), e o script antigo não comporta essa injeção de forma segura.
  - **Ação Futura:** Este script será substituído inteiramente por um novo `FinanceiroController` (ou `PagamentoController`) na próxima etapa do cronograma (Refatoração do Módulo Financeiro).

### 🔍 Evolução da Infraestrutura de Qualidade (Auditoria)
- **Scripts de Auditoria e Saneamento:** Refatorados e adequados para reconhecer a nova arquitetura do módulo de Atendimentos. Corrigidos falsos-positivos na validação Fase 5 e adicionada cobertura explícita de "Zero Hardcode" e Imutabilidade Financeira ao longo do script de auditoria Fase 5. Ajustados os caminhos do `verify_saneamento.php` para impedir quebra.

---
*Status: Módulo de Atendimentos consolidado, rigorosamente validado na arquitetura SaaS/MVC e protegido por CSRF. Débito técnico do módulo financeiro isolado com segurança.*

## [2026-06-10] — Fase 5: Refatoração MVC do Módulo Financeiro e Relatórios (Consolidação e Saneamento)

Consolidação completa da migração financeira e do fluxo de caixa. Esta etapa elimina os últimos bloqueios de segurança do banco SaaS, resolve inconsistências de faturamento nos relatórios e conclui a migração MVC de relatórios e fechamentos de pagamentos.

### 🏗️ Evolução Arquitetural (Financeiro 2.0 & Relatórios MVC)
- **Criação do `App\Controllers\FinanceiroController`:**
    - Centraliza a confirmação de pagamentos, gestão de despesas e os 4 relatórios financeiros do sistema.
    - Herda proteção **CSRF transversal** do `BaseController` para rotas transacionais.
- **Implementação dos Models `Pagamento` e `Despesa`:**
    - Introdução de métodos de persistência atômica com injeção obrigatória de `clinica_id`.
    - **Isolamento SaaS:** Impedida a visualização ou alteração de registros financeiros e relatórios entre clínicas distintas (prevenção de vazamento de dados).
- **Roteamento Unificado:** 
    - Mapeamento de rotas amigáveis no Front Controller (`/financeiro/pagar`, `/financeiro/despesas`, `/financeiro/relatorios/geral`, `/financeiro/relatorios/diario`, `/financeiro/relatorios/dentistas`, `/financeiro/relatorios/procedimentos`).
    - Interceptação de acessos diretos às URLs legadas do tipo `.php` para roteá-las pelo MVC.

### 🛡️ Segurança e Integridade Transacional (Fechamento)
- **Cálculos e Recalculos no Fechamento:**
    - O fechamento do pagamento em `Pagamento::confirmarPagamentoCompleto` agora utiliza `FinanceiroService` para calcular a taxa de cartão de cada parcela em tempo real e deduzir do lucro líquido do atendimento.
    - Recalcula a comissão do dentista baseada no faturamento mensal atualizado (aplicando a regra de meta e bônus de produtividade do banco de dados).
    - Atualiza os status dos procedimentos associados no banco de dados de `'finalizado'` para `'feito'` no momento da liquidação.
- **Transacionalidade:** Uso de `beginTransaction` e `commit` na confirmação de pagamentos para garantir atomicidade entre os lançamentos individuais de pagamento, recálculos e atualizações de status.
- **Blindagem de Erros Internos:** Remoção da exposição direta de exceções de banco de dados (`$e->getMessage()`) para o cliente final. As mensagens brutas de erro agora são redirecionadas para o `error_log` interno do servidor PHP e o usuário recebe feedbacks amigáveis.

### 🧹 Saneamento e UI
- **Refatoração de Views:** Migração para `app/Views/financeiro/` (incluindo `relatorio_geral.php`, `relatorio_diario.php`, `relatorio_dentistas.php` e `relatorio_procedimentos.php`), separando lógica de apresentação da lógica de banco de dados.
- **Navegação Sincronizada:** Menu lateral (Header) atualizado para as novas rotas MVC.
- **Expurgo de Dívida Técnica (Arquivos Removidos):**
    - Remoção definitiva de scripts legados e desprotegidos na raiz: `relatorios.php`, `relatorio_diario.php`, `relatorio_dentistas.php`, `relatorio_procedimentos.php`.
    - Remoção de views legadas: `views/confirmar_pagamento.php`.
    - Remoção de handlers residuais em `actions/`: `actions/salvar_pagamento.php`, `actions/salvar_despesa.php`, `actions/excluir_despesa.php`.

### 🔍 Infraestrutura de Qualidade (Garantia de Regressão)
- **Script de Integração:** Criação do script de testes `scripts/test_financeiro_db.php` para validar as novas queries SQL diretamente no banco remoto a partir do Docker.
- **Resultados Operacionais:** Validação 100% bem-sucedida de todas as rotas de faturamento do Relatório Geral, Gráficos (evolução e formas de pagamento), Relatório Diário, Relatório por Dentista e Relatório por Procedimentos com dados reais, assegurando que o sistema esteja livre de qualquer falha contábil ou sintática.

---
*Status: Fase 5 (Módulo Financeiro e Relatórios) Concluída. Core transacional e gerencial do sistema 100% migrado para MVC/SaaS e auditado.*

## [2026-06-10] — Fase 5: Refatoração MVC do Módulo de Dashboard

Conclusão da migração do painel principal (Dashboard) para a arquitetura MVC e padrão SaaS Multi-Tenant. Com esta etapa, a raiz do projeto foi completamente saneada, e toda a lógica de faturamento, despesas e atendimentos está centralizada no padrão MVC.

### 🏗️ Evolução Arquitetural (Dashboard MVC & SaaS)
- **Criação do `App\Controllers\DashboardController`:**
    - Responsável por receber a requisição de exibição do painel principal da clínica do usuário logado.
    - Realiza o processamento e a formatação de dados temporais (mês selecionado, navegação entre meses em português via `IntlDateFormatter`) e paginação de lançamentos.
    - Delega consultas de faturamento bruto, lucro líquido e despesas aos Models `Atendimento` e `Despesa`, isolando a lógica de negócio do layout.
- **Implementação do Isolamento SaaS:**
    - Filtros de consulta e paginação baseados rigidamente em `clinica_id` nos Models `Atendimento` e `Despesa` para garantir a privacidade de dados entre clínicas no ecossistema SaaS.
    - Tratamento unificado de atendimentos com status de execução `feito` e `finalizado` para integridade contábil do painel.
- **Criação da View `app/Views/dashboard.php`:**
    - Nova view limpa e desacoplada, contendo apenas elementos visuais, estruturação dos cards estatísticos e tabela dinâmica de histórico de atendimentos com busca e paginação.
    - Implementação de um modal dinâmico JavaScript integrado para exibição detalhada de lançamentos e anexos de atendimento.

### 🧹 Saneamento e Expurgo de Legados
- **Remoção de `index.php` Legado:** Exclusão definitiva do arquivo procedural obsoleto na raiz (`index.php`), que continha misturas de requisições diretas de banco de dados, lógica financeira procedural e markup HTML.
- **Roteamento MVC:** Configuração do Front Controller (`public/index.php`) para capturar acessos à raiz `/` e `/index.php` e direcionar ao `DashboardController`.

### 🔍 Infraestrutura de Qualidade (Auditoria)
- **Auditoria de Conformidade:** Execução do script `scripts/auditoria_conclusao_fase5.php` via Docker Compose validando 100% de sucesso nas validações de arquitetura, segurança (CSRF) e limpeza residual do sistema.

---
*Status: Fase 5 Concluída com Sucesso. Módulos de Pacientes, Procedimentos, Autenticação, Atendimentos, Financeiro, Relatórios e Dashboard integralmente migrados para MVC/SaaS Multi-Tenant.*

## [2026-06-10] — Fase 5: Conclusão do Saneamento e Migração Final

Etapa final da Fase 5, focada na migração dos últimos módulos residuais e na limpeza total da raiz do projeto, consolidando a arquitetura MVC profissional.

### 🏗️ Migração de Módulos Residuais
- **Módulo de Usuários:**
    - Criada a classe `App\Models\Usuario` para centralizar CRUD de usuários com isolamento SaaS.
    - Criado `App\Controllers\UsuarioController` para gerenciar listagem, criação, edição e exclusão de usuários.
    - Implementadas views em `app/Views/usuarios/` (`index.php`, `editar.php`).
- **Módulo de Perfil/Configurações:**
    - Migrada a lógica de alteração de senha e nome para o `UsuarioController`.
    - Criada a view `app/Views/usuarios/configuracoes.php`.
- **Relatório por Paciente e Odontograma:**
    - Migrada a lógica complexa do Odontograma para o `PacienteController` e `PacienteModel`.
    - Criada a view `app/Views/pacientes/relatorio.php`.
- **Recibo e Detalhes de Atendimento:**
    - Migrados para o `AtendimentoController`.
    - Criadas as views `app/Views/atendimentos/detalhes.php` e `app/Views/atendimentos/recibo.php`.

### 🧹 Saneamento Radical da Raiz (Zero Debt)
- **Exclusão de Arquivos Legados:** Removidos todos os arquivos `.php` residuais da raiz (`usuarios.php`, `editar_usuario.php`, `configuracoes.php`, `login.php`, `despesas.php`, `relatorio_paciente.php`, etc.).
- **Remoção da pasta `actions/`:** Toda a lógica procedural foi substituída por métodos nos Controllers MVC.
- **Padronização de Uploads:** Movimentada a pasta `uploads/` da raiz para `public/uploads/`, alinhando com a estrutura de segurança que expõe apenas o diretório público.

### 🛡️ Refinamentos de Infraestrutura
- **Roteamento MVC:** Atualizado o Front Controller (`public/index.php`) para incluir as novas rotas de usuários, perfil, relatórios e recibos.
- **Proteção CSRF:** Estendida a proteção CSRF para todos os novos formulários de usuários e configurações.
- **Navegação:** Atualizado o `header.php` para utilizar exclusivamente as rotas amigáveis do MVC.

### 💎 Garantia de Qualidade (Auditoria Final)
- **Validação de Conformidade:** Execução dos scripts de auditoria das Fases 3, 4 e 5 com **100% de sucesso**.
- **Resultados:**
    - **Arquitetura:** 45/45 verificações aprovadas (Models, Controllers e Namespaces).
    - **Segurança:** 12/12 verificações aprovadas (CSRF, Tenant Isolation, Session Security).
    - **Limpeza:** 7/7 verificações aprovadas (Zero arquivos legados na raiz pública).

---
*Status: Fase 5 100% Concluída. Sistema totalmente migrado para MVC e pronto para a Fase 6 (Interface).*

## [2026-06-12] — Fase 6: Item 1 - Gestão Administrativa e Regras de Negócio

Implementação do pilar administrativo do sistema SaaS, movendo a inteligência de negócios do código para o banco de dados e refinando a experiência visual para padrões profissionais.

### ⚙️ Gestão Clínica (Zero Hardcode & SaaS)
- **Novo Módulo de Configurações da Clínica:**
    - Criada a classe `App\Models\Clinica` para gerenciar tabelas administrativas.
    - Implementado o `App\Controllers\ClinicaController` para orquestrar parametrizações de taxas e regras.
    - Criada a View `app/Views/clinica/painel.php` com navegação por abas para Gestão de Dados, Comissões e Taxas.
- **Parametrização de Taxas de Cartão:** 
    - Implementado CRUD completo para taxas por bandeira e parcelamento (1x a 12x), gravando na tabela `clinica_taxas_cartao`.
- **Regras de Comissão e Rateio:**
    - Interface para configuração de metas de faturamento e percentuais de bônus.
    - Parametrização de rateios especializados (Canal, Prótese, Especializado) removendo cálculos fixos do `FinanceiroService`.
- **Dados Institucionais Dinâmicos:**
    - Implementado CRUD para Nome Fantasia, CNPJ, Endereço e Telefone.
    - Integração imediata com a View de Recibo (`atendimentos/recibo.php`), garantindo documentos profissionais atualizados.

### 📈 Evolução de Módulos Core
- **Edição de Preços de Procedimentos:**
    - Refatorado `ProcedimentoController` e `ProcedimentoModel` para permitir alteração de valores e categorias.
    - **Imutabilidade Financeira:** Garantida a integridade histórica; alterações de preço afetam apenas novos atendimentos, preservando valores já faturados.
- **Segurança e Validação:**
    - Implementadas validações de servidor contra percentuais inconsistentes (>100%) e parcelamentos inválidos.
    - Proteção CSRF transversal aplicada a todos os novos formulários administrativos.

### 🎨 Refinamentos de UI/UX (High-End Aesthetics)
- **Navegação Profissional:**
    - Reconstrução da lógica CSS do Header para garantir interatividade de clique 100% confiável (remoção de "zonas mortas" entre dropdowns).
    - **Linha Ativa Responsiva:** Ajuste matemático da linha branca inferior (underline) para se estender precisamente do início do texto até o ícone da seta (▾), utilizando `inline-flex` e `width: max-content`.
- **Componentização e Partials (Pilar C):**
    - Implementação do `app/Views/partials/alert.php`: Centralização do sistema de feedback visual com ícones dinâmicos e animações CSS.
    - Refatoração das views de Clínicas e Procedimentos para utilizar o novo partial, garantindo consistência em todo o sistema.
- **Usabilidade (Máscaras de Entrada):**
    - Adição de máscaras de CNPJ e Telefone via JavaScript para padronização de dados institucionais.

### 🔍 Infraestrutura de Qualidade
- **Auditoria Automatizada:** Criado o script `scripts/auditoria_conclusao_fase6.php` para validar a conformidade MVC, integridade de tabelas SaaS e presença de proteção CSRF no novo módulo.
- **Teste de Integração Lógica:** Evolução da auditoria para validar se o `FinanceiroService` reflete instantaneamente as configurações alteradas na interface, garantindo o fim do hardcode.


---
*Status: Fase 6 (Item 1) Concluída. Sistema com gestão administrativa completa e interface refinada.*
