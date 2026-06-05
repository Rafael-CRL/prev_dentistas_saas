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

## [2026-06-05] — Hotfix: Roteamento Híbrido no Front Controller

Correção de um bug crítico no Front Controller (`public/index.php`) que impedia o acesso às páginas legadas (como o login) e exibia prematuramente a mensagem "Página não encontrada (MVC em construção)".

### 🐛 O Problema (Bug de Roteamento)
- **Lógica Falha:** O sistema estava utilizando a função `str_replace(BASE_URL, '', $uri)` para extrair o caminho relativo da requisição. Como nossa `BASE_URL` é configurada como `/`, o PHP removia **todas** as barras da URL.
- **Efeito:** Uma requisição para `/actions/verificar_login.php` era transformada incorretamente em `actionsverificar_login.php`. Como esse arquivo não existe fisicamente, o Front Controller acionava o fallback de erro 404 (MVC em construção), bloqueando o acesso ao sistema antes mesmo da Fase 5.

### 🛠️ A Solução Implementada
- **Correção em `public/index.php`:** A extração da URL base foi reescrita para garantir que apenas o início da string seja modificado, preservando a integridade das pastas internas.
- **Conceito de Sistema Híbrido:** Foi validado e garantido que o sistema pode operar de forma mista. O Front Controller primeiro tenta carregar a página legada diretamente (garantindo que a clínica não pare) e, somente se ela não existir, exibe a tela de construção MVC.

---
*Status: Bug resolvido. O login e navegação das páginas legadas estão restaurados através do ponto único de entrada.*
