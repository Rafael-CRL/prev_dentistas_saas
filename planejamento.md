# Planejamento de Execução - Prev-Dentistas (MVC + SaaS)

Roteiro técnico para refatoração e implementação do modelo multi-tenant.

## 1. Configuração de Infraestrutura e Git

### Fluxo Git
*   **main:** Estável, reflete produção.
*   **dev:** Integração de funcionalidades testadas.
*   **feature/xxx:** Desenvolvimento individual.
*   **Requisito:** Pull Request (PR) com 1 review obrigatório para merge em `dev`.

### Banco de Dados e Ambiente
*   **Remoto:** Banco MariaDB centralizado no Railway (acesso comum para os 5 membros).
*   **Local:** Docker apenas para servidor Apache/PHP.
*   **Config:** Variáveis de conexão via `.env` (nunca subir para o Git).

## 2. Fases do Projeto

### Fase 1: Novo DER (Design de Dados)
*   **Decisão Técnica:** Um dentista (usuário) pertence a apenas **uma clínica** (Relação 1:N).
*   **Estrutura:** Adicionar `clinica_id` como FK direta na tabela `usuarios`.
*   Desenhar esquema com tabela `clinicas` e `clinica_id` em: `usuarios`, `pacientes`, `procedimentos`, `atendimentos`, `despesas`.
*   Criar tabelas `clinica_taxas_cartao` e `clinica_regras_comissao`.

### Fase 2: Setup do Repositório
*   Configurar `.gitignore` (excluir `.env`, `vendor/`, `.gemini/`).
*   Configurar `config/database.php` para usar `$_ENV`.
*   Validar conexão de todos os membros ao banco Railway.

### Fase 3: Saneamento e Estrutura
*   Eliminar arquivos redundantes (`_2`, `_3`, etc).
*   Criar estrutura: `app/Models`, `app/Controllers`, `app/Views`, `public/`.
*   Implementar Autoloader PSR-4.

### Fase 4: Migração (Migration)
*   Aplicar script SQL para atualizar o banco para o novo DER.
*   Inserir dados iniciais da clínica padrão.

### Fase 5: Infraestrutura de Configuração
*   Criar classe `Config` para leitura dinâmica de taxas e comissões.
*   Substituir cálculos fixos no PHP por chamadas ao banco.

### Fase 6: Refatoração MVC (Módulos)
1.  **Pacientes:** Model -> Controller -> View.
2.  **Procedimentos:** Model -> Controller -> View.
3.  **Atendimentos:** Integração com os anteriores.
4.  **Financeiro:** Cálculos baseados no novo DER.

### Fase 7: Interface e Dashboard
*   Padronizar componentes visuais.
*   Refatorar Dashboard com dados reais dos Models.

## 3. Matriz de Responsabilidades (Sugestão)
*   **Membro A:** DB Admin & Infra (Railway/Docker).
*   **Membro B:** Core MVC (Front Controller/Router/Autoloader).
*   **Membro C:** Módulo Pacientes/Procedimentos.
*   **Membro D:** Módulo Atendimentos/Financeiro.
*   **Membro E:** UI/UX e Frontend das Views.

---
*Este plano substitui todas as versões anteriores e é o guia oficial para o desenvolvimento.*
