# Tutorial de Instalação e Execução: Sistema Odontológico SaaS Multi-Tenant

Este guia orienta a configuração do ambiente com Docker, criação e migração do banco de dados MariaDB, povoamento com dados iniciais (seed) e validação por scripts de auditoria.

---

## Pré-requisitos

- Docker (https://www.docker.com/)
- Docker Compose (https://docs.docker.com/compose/)

---

## 1. Visão Geral da Arquitetura do Banco

- Engine: MariaDB (imagem oficial via Docker).
- Modelo de negócio: multi-tenant (SaaS). O isolamento lógico de dados é garantido pela chave estrangeira `clinica_id`, presente nas tabelas.
- Arquivos de banco de dados, localizados na pasta `database` na raiz do projeto:
  - `clinica_prev_dentistas.sql`: estrutura base das tabelas e dados iniciais.
  - `migration.sql`: alterações estruturais (constraints, custos auxiliares, parcelamento em até 24x e status de Fiado).
  - `migration_normalize_cnpj.sql`: normalização dos registros de CNPJ na tabela de clínicas.

---

## 2. Passo a Passo de Execução

Execute os comandos a seguir no terminal, a partir da raiz do projeto.

### 2.1 Configurar variáveis de ambiente

Verifique se existe o arquivo `.env` na raiz. Caso não exista, crie-o a partir do `.env.example`:

```
cp .env.example .env
```

Configure as variáveis de banco de dados no `.env` para o ambiente Docker:

```
DB_HOST=db
DB_NAME=clinica_prev_dentistas
DB_USER=root
DB_PASS=root
DB_PORT=3306
```

### 2.2 Inicializar os containers Docker

Sobe os containers da aplicação (Apache + PHP 8.1) e do banco de dados (MariaDB) em segundo plano:

```
docker compose up -d --build
```

Portas expostas:
- Aplicação web: http://localhost:8080
- Banco de dados (MariaDB): localhost:3306

### 2.3 Criar a estrutura do banco de dados

Executa o script de setup, que cria o banco e importa os arquivos SQL na ordem correta (`clinica_prev_dentistas.sql`, `migration.sql`, `migration_normalize_cnpj.sql`):

```
docker compose exec -T app php scripts/setup.php
```

### 2.4 Popular com dados iniciais (seed)

Executa o script que cria a clínica padrão, as credenciais administrativas e os procedimentos base:

```
docker compose exec -T app php scripts/setup_data.php
```

Acessos padrão criados:

| Perfil | Login | Senha |
|---|---|---|
| Administrador / Proprietário | admin | admin123 ou 123 |
| Dentista | roberto | 123 |
| Dentista | ana | 123 |

---

## 3. Verificação e Auditoria

Para validar a integridade do banco e as regras de negócio, execute:

```
# Verifica integridade física dos arquivos e tabelas do banco
docker compose exec -T app php scripts/verify_saneamento.php

# Testa o ciclo de vida financeiro e o fluxo de pagamento do Fiado (2 parcelas)
docker compose exec -T app php scripts/test_fiado.php

# Executa auditoria geral de conformidade (comissões, parcelamento 24x, CSRF)
docker compose exec -T app php scripts/auditoria_conclusao_fase6.php
```

---

## 4. Acessando a Aplicação

Se todos os comandos acima finalizarem com sucesso, acesse o sistema em:

http://localhost:8080/setup.php

http://localhost:8080/setup_data.php

http://localhost:8080/
