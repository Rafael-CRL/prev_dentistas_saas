# Prev-Dentistas

SaaS multi-tenant para clínicas odontológicas. Projeto Integrado II, UFPA. Projeto realizado em equipe.

Pacientes, atendimentos, procedimentos, pagamentos, despesas e financeiro. Taxas de cartão e regras de comissão parametrizadas por clínica, sem valores fixos no código.

## Stack

PHP 8.1, MySQL (PDO), Apache, Docker. MVC com front controller: só `public/` fica exposto.

Prepared statements, proteção CSRF em todo POST, senhas com `password_hash`, configuração em `.env`.

## Executar

```bash
cp .env.example .env   # preencher DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
docker compose up --build
```

A aplicação sobe em `http://localhost:8080`. O compose sobe só a aplicação: o MySQL é externo. `scripts/setup.php` cria o banco e `database/` guarda as migrations.

## Documentação

`OBJETIVOS_DO_PROJETO.md`, `DB.md`, `DOCKER_SETUP.md` e `Changelog.md`.
