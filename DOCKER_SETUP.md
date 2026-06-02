# Documentação de Configuração Docker - Sistema de Clínica Odontológica

Este documento detalha o processo de migração do sistema de um ambiente local tradicional (XAMPP) para uma infraestrutura baseada em containers **Docker**. O objetivo é garantir um ambiente padronizado, escalável e de fácil inicialização para qualquer desenvolvedor.

---

## 1. Infraestrutura como Código

A base da nossa infraestrutura é composta por dois arquivos principais que definem o ambiente de execução.

### 1.1. Dockerfile
O `Dockerfile` é a "receita" para construir a imagem do container da aplicação (o servidor web).

```dockerfile
FROM php:8.1-apache
RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli intl
```

*   **`FROM php:8.1-apache`**: Utilizamos uma imagem oficial que já traz o PHP 8.1 pré-configurado com o servidor Apache. Isso elimina a necessidade de configurar o Apache manualmente.
*   **`apt-get install -y libicu-dev`**: Instala bibliotecas do sistema necessárias para a extensão `intl`.
*   **`docker-php-ext-install`**: Comando específico do Docker-PHP para habilitar extensões:
    *   `pdo` e `pdo_mysql`: Drivers modernos para conexão com banco de dados.
    *   `mysqli`: Driver legacy necessário para o script de `setup.php`.
    *   `intl`: Extensão de internacionalização, crucial para a classe `IntlDateFormatter` usada no Dashboard para formatar datas e moedas no padrão brasileiro.

### 1.2. docker-compose.yml
O `docker-compose.yml` atua como o maestro, orquestrando múltiplos containers para trabalharem juntos.

```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: mariadb:latest
    environment:
      MARIADB_DATABASE: clinica_prev_dentistas
      MARIADB_ROOT_PASSWORD: root
    ports:
      - "3306:3306"
```

*   **Serviço `app`**:
    *   `build: .`: Constrói a imagem usando o Dockerfile local.
    *   `ports: "8080:80"`: Mapeia a porta 8080 da sua máquina para a porta 80 do container.
    *   `volumes`: Sincroniza o código-fonte da sua máquina com o container em tempo real.
    *   `depends_on`: Garante que o banco de dados inicie antes da aplicação.
*   **Serviço `db`**:
    *   Utiliza a imagem oficial do **MariaDB**.
    *   **Variáveis de Ambiente**: Define o nome do banco (`clinica_prev_dentistas`) e a senha do root (`root`). Estes valores são a "fonte da verdade" para os arquivos de configuração do PHP.

---

## 2. Ajustes de Configuração

Ao mover para o Docker, a aplicação deixa de rodar em `localhost` (da perspectiva do container) e passa a rodar em uma rede interna.

### 2.1. config/database.php
Este arquivo é o ponto central de conexão. Ele precisou ser ajustado para "conversar" com o container vizinho.

*   **O Problema**: Antes, o `$host` era `localhost` ou `127.0.0.1`. No Docker, cada container é uma máquina isolada, então `localhost` apontaria para o próprio container da aplicação, onde não há banco de dados.
*   **A Solução**: Alteramos o `$host` para `'db'`. O Docker possui um DNS interno que resolve o nome do serviço definido no Compose para o IP correto do container de banco de dados.
*   **Paridade**: Garantimos que `$db_name` e `$password` correspondam exatamente ao que foi definido no `docker-compose.yml`.

### 2.2. config/app.php
*   **O Problema**: No XAMPP, o projeto geralmente ficava em uma subpasta (ex: `/trabalhoihc/`). No Docker, o Apache serve o conteúdo direto na raiz (`/var/www/html`).
*   **A Solução**: Alteramos `define('BASE_URL', '/')`. Isso garante que todos os links, redirecionamentos e caminhos de CSS/JS gerados pela aplicação funcionem corretamente a partir da raiz do domínio.

---

## 3. Scripts de Inicialização

Diferente de um banco manual, o Docker inicia o banco vazio. Criamos scripts para automatizar a criação da estrutura.

### 3.1. setup.php
Este script deve ser executado primeiro (`localhost:8080/setup.php`).
*   **Função**: Conecta ao MariaDB, cria o banco de dados (se não existir) e executa o arquivo `.sql` localizado em `database/`.
*   **Correção Realizada**: Ajustamos as credenciais internas do script para usar o host `'db'` e a senha `'root'`, em conformidade com o ambiente Docker.

### 3.2. setup_data.php
Executado após o setup da estrutura (`localhost:8080/setup_data.php`).
*   **Função**: "Povoa" o banco com dados iniciais, como o usuário administrador (`admin / 123`) e procedimentos básicos.
*   **Destaque**: Ele utiliza o `config/database.php` para se conectar, herdando automaticamente as configurações corretas do container.

---

## 4. Resumo do Fluxo de Trabalho

Para rodar o projeto do zero, a ordem lógica é:

1.  **`docker compose up -d`**: Sobe a infraestrutura (Containers).
2.  **`setup.php`**: Constrói as tabelas (Arquiteto).
3.  **`setup_data.php`**: Insere os dados iniciais (Povoador).
4.  **Uso Diário**: A aplicação utiliza o `database.php` para manter a persistência.

Esta arquitetura garante que, se precisarmos mudar a versão do PHP ou trocar o MariaDB pelo MySQL, basta alterar os arquivos de configuração do Docker, sem "poluir" a máquina física do desenvolvedor.
