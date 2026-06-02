# Mandatos Estratégicos e Objetivos - Sistema Odontológico SaaS

Este documento define a direção técnica do projeto, focando em escalabilidade, segurança e transição para o modelo SaaS (Software as a Service).

## 1. Visão Geral: O Produto Generalista
O objetivo central é transformar o sistema atual em um **Produto de Prateleira Multi-Tenant**. O software deve permitir o isolamento total de dados entre diferentes clínicas usando a mesma base de dados.

## 2. Pilares Arquiteturais (Obrigatórios)

### I. Arquitetura MVC com Front Controller
*   **Ação:** Migrar toda a lógica para o padrão Model-View-Controller.
*   **Mandato:** O único diretório exposto ao servidor web deve ser o `/public`. Nenhum arquivo de lógica (`app/`) ou configuração (`.env`) deve ser acessível via URL direta.

### II. Multi-Tenancy (Isolamento de Dados)
*   **Ação:** Implementar a coluna `clinica_id` como chave estrangeira (FK) em todas as tabelas transacionais e cadastrais.
*   **Mandato:** Toda consulta (SELECT, UPDATE, DELETE) deve ser filtrada obrigatoriamente pelo ID da clínica ativa na sessão.

### III. Parametrização Total (Zero Hardcode)
*   **Ação:** Criar tabelas de configuração por clínica para taxas de cartão e regras de comissão.
*   **Mandato:** Proibido o uso de percentuais ou valores fixos (hardcoded) em arquivos PHP. Todo cálculo deve ler os parâmetros do banco de dados.

### IV. Ambiente Colaborativo Profissional
*   **Ação:** Utilizar banco de dados remoto centralizado (Railway) e fluxo de trabalho Git robusto.
*   **Mandato:** A branch `main` é sagrada e estável. O desenvolvimento ocorre em `feature/` branches com merge condicionado a code review na branch `dev`.

## 3. Diretrizes de Desenvolvimento
1.  **Segurança:** Uso rigoroso de Prepared Statements (PDO), proteção CSRF e variáveis de ambiente (`.env`).
2.  **Organização:** Autoloader PSR-4 e separação clara de responsabilidades (Model trata dado, View trata HTML, Controller orquestra).
3.  **Integridade:** Imutabilidade histórica em cálculos financeiros. Alterar uma taxa hoje não retroage em atendimentos passados.

---
**Status:** Plano Consolidado (UFPA - Projeto Integrado II).
