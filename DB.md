# Planejamento do Banco de Dados - Sistema Odontológico SaaS

Este documento descreve a nova arquitetura de dados do sistema, projetada para suportar o modelo **SaaS (Multi-tenant)**, garantir o **isolamento de dados** e eliminar regras de negócio fixas no código (**Zero Hardcode**).

## 1. Visão Geral da Mudança
O sistema deixa de ser uma aplicação para uma única clínica e passa a ser um **Produto Multi-Tenant**. 
- **Antes:** Dados misturados, taxas e comissões fixas no código PHP.
- **Depois:** Isolamento total por `clinica_id` e regras financeiras parametrizáveis via banco de dados.

## 2. Novas Tabelas (O "Cérebro" Administrativo)

| Tabela | Função | Por que existe? |
| :--- | :--- | :--- |
| **`clinicas`** | Cadastro mestre dos clientes. | É a "âncora" do sistema. Cada registro é um cliente (clínica) pagante. |
| **`clinica_configuracoes`** | Personalização flexível (Chave-Valor). | Armazena logos, cores e textos de recibos sem precisar criar novas colunas no banco. |
| **`clinica_taxas_cartao`** | Parâmetros de taxas de maquininha. | Permite que cada clínica defina suas próprias taxas de débito/crédito e parcelamento. |
| **`clinica_regras_comissao`** | Regras de repasse para dentistas. | Define percentuais, valores fixos e bônus por meta (Ex: Meta de R$ 10k). |

## 3. O Atributo `clinica_id` (Isolamento SaaS)
Para garantir que os dados da "Clínica A" nunca vazem para a "Clínica B", a coluna `clinica_id` (INT, FK) foi adicionada obrigatoriamente às seguintes tabelas:

- `usuarios` (vínculo de funcionários)
- `pacientes` (privacidade de prontuários)
- `procedimentos` (tabela de preços própria)
- `atendimentos` (histórico financeiro isolado)
- `despesas` (fluxo de caixa por unidade)
- `atendimento_procedimentos` e `atendimento_pagamentos` (integridade total)

## 4. Detalhamento de Atributos (Novas Tabelas)

### `clinica_taxas_cartao`
- `bandeira`: Visa, Master, etc.
- `modalidade`: 'debito' ou 'credito'.
- `parcelas`: Quantidade de parcelas suportada.
- `taxa_percentual`: O custo da operadora.

### `clinica_regras_comissao`
- `tipo`: 'fixo' ou 'percentual'.
- `valor_regra`: O valor base da comissão.
- `valor_meta`: Gatilho para o bônus de produtividade.
- `percentual_bonus`: Adicional pago após atingir a meta.

## 5. Impacto na Arquitetura (MVC/OOP)
1. **Models:** Cada tabela terá seu respectivo Model. O isolamento (`WHERE clinica_id = ?`) será tratado na camada de Model ou via Session Global.
2. **Services:** A lógica de cálculo financeiro será movida para o `FinanceiroService.php`, que consultará as tabelas de taxas e regras em tempo real.
3. **Imutabilidade:** Os valores calculados (líquido, comissão) continuam sendo gravados fisicamente na tabela `atendimentos` no ato da venda para preservar o histórico financeiro.

---
*Documento consolidado para o Projeto Integrado II - UFPA.*
