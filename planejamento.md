# Plano de Execução — Prev-Dentistas (Versão Atualizada)
> Refatoração para MVC + SaaS Multi-Tenant — Grupo de 5  
> Projeto Integrado II — UFPA

---

## Contexto e Mandatos

O sistema será transformado em um **Produto de Prateleira (SaaS)**. O objetivo é a refatoração completa para MVC com Orientação a Objetos, garantindo isolamento total entre clínicas e eliminando regras de negócio fixas no código (**Zero Hardcode**).

**Decisões Estratégicas:**
- **Isolamento:** `clinica_id` obrigatório em todas as tabelas transacionais e cadastrais.
- **Banco Remoto:** Railway (MySQL/MariaDB) para ambiente colaborativo.
- **Arquitetura:** MVC manual (sem frameworks) com **Camada de Serviço** para lógica complexa.
- **Versionamento:** Git Flow (`main` → `dev` → `feature/xxx`).

---

## Infraestrutura e Git

**Ambiente:**
- Banco de dados centralizado no Railway.
- Docker local apenas para servidor Apache/PHP.
- Variáveis sensíveis via `.env` (excluído do Git).

**Fluxo de Trabalho:**
- `main`: Estável/Produção.
- `dev`: Integração e testes.
- `feature/xxx`: Desenvolvimento de funcionalidades específicas.
- *Requisito:* PR com revisão antes do merge em `dev`.

---

## Estrutura de Pastas (Nova Arquitetura)

```
/
├── app/
│   ├── Controllers/       ← Orquestração da lógica
│   ├── Models/            ← Interação com banco e abstração de dados
│   ├── Services/          ← Lógica de negócio complexa (Ex: FinanceiroService)
│   └── Views/             ← Templates (mínimo de PHP possível)
├── config/
│   ├── app.php
│   └── database.php       ← Conexão PDO lendo do $_ENV
├── public/                ← Único diretório exposto ao servidor web
│   ├── index.php          ← Front Controller (Ponto de entrada único)
│   └── assets/            ← CSS, JS, Imagens
├── .env.example           ← Modelo de variáveis de ambiente
├── .gitignore
├── DB.md                  ← Planejamento detalhado do banco de dados
└── README.md
```

---

## Divisão de Módulos e Responsabilidades

| Responsável | Módulo | Dependências |
| :--- | :--- | :--- |
| **A** | Gestão de Pacientes | — |
| **B** | Gestão de Procedimentos | — |
| **C** | Autenticação, Sessão e Multi-tenancy | — |
| **D** | Fluxo de Atendimentos | A + B |
| **E** | Financeiro, Comissões e Dashboard | D + Camada Service |

---

## Fases de Execução

### Fase 1 — Diagrama de Dados (DER)
Implementar as novas tabelas e relacionamentos conforme definido no `DB.md`:

| Nova Tabela | Objetivo |
| :--- | :--- |
| **`clinicas`** | Cadastro mestre de clientes SaaS. |
| **`clinica_configuracoes`** | Chave-Valor para personalização (Logos, Cores, Recibos). |
| **`clinica_taxas_cartao`** | Parâmetros de taxas de maquininha (Fim do Hardcode). |
| **`clinica_regras_comissao`** | Regras de repasse e metas (Bônus). |

- **Isolamento:** Adicionar `clinica_id` em: `usuarios`, `pacientes`, `procedimentos`, `atendimentos`, `despesas`, `atendimento_procedimentos` e `atendimento_pagamentos`.

### Fase 2 — Saneamento e Estrutura
- **Limpeza:** Deletar arquivos duplicados identificados:
    - `relatorio_paciente2.php`, `relatorio_paciente3.php`
    - `views/novo_atendimento2.php`, `novo_atendimento3.php`, `novo_atendimento4.php`
    - `actions/salvar_atendimento2.php`
- **Organização:** Criar estrutura de pastas `app/` e `public/`.
- **Autoload:** Implementar Autoloader PSR-4 para carregamento automático de classes.
- **Padrão de Resposta (Híbrido):**
    - **Redirecionamento (`header("Location")`):** Obrigatório para fluxos de formulários (Salvar, Editar, Excluir).
    - **JSON (`json_encode`):** Obrigatório para consultas dinâmicas, Dashboard e integrações via AJAX.

### Fase 3 — Migração e Setup
- Aplicar script SQL de migração no Railway.
- Vincular dados legados a uma clínica padrão.
- Validar conectividade de todos os membros.

### Fase 4 — Camada de Serviço e Configuração
- Criar a classe **`FinanceiroService.php`** (ou similar) para centralizar os cálculos de taxas e comissões.
- Implementar classe de configuração que lê as tabelas `clinica_configuracoes` em tempo real.

### Fase 5 — Refatoração MVC (Módulos)
Refatoração iterativa seguindo a ordem de dependências.
- **Regra:** Todo Model deve filtrar automaticamente pelo `clinica_id` da sessão ativa.

### Fase 6 — UI/UX e Validação
- Padronização estética.
- Refatoração do Dashboard usando dados consolidados dos Models/Services.

---

## Metas e Critérios de Sucesso
1. **Multi-tenancy Ativo:** Nenhuma clínica consegue ver dados de outra.
2. **Zero Hardcode:** Nenhuma taxa ou percentual fixo nos arquivos PHP.
3. **Padrão MVC:** Separação clara de responsabilidades confirmada por revisão de código.

---

## Itens Opcionais (Se houver margem)
- **Segurança:** Proteção CSRF e regeneração de ID de sessão no login.
- **Logística:** Exclusão lógica (`deleted_at`) em vez de `DELETE` físico.
- **Precisão:** Uso de centavos inteiros (integers) para cálculos financeiros críticos.
- **Bônus de Meta:** Implementar a lógica de meta de faturamento (Ex: R$ 10k) como parâmetro dinâmico no banco.

---
*UFPA - Projeto Integrado II*
