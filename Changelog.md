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
