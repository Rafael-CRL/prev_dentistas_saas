# Changelog - Sistema de Gestão Odontológica

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

### 🏗️ Evolução da Infraestrutura (Refinamento de Arquiteto)
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

---
*Status: Fase 5 (Parte 1) Concluída. Módulos de Autenticação, Pacientes e Procedimentos consolidados e auditados. Infraestrutura preparada para a refatoração do módulo de Atendimentos.*

