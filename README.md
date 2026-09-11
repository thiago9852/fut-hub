# FutHub — Plataforma de Gestão, API e Ingestão de Dados (Excel/VBA & Symfony)

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Symfony Version](https://img.shields.io/badge/Symfony-8.1-000000?style=flat-square&logo=symfony)](https://symfony.com/)
[![Doctrine ORM](https://img.shields.io/badge/Doctrine_ORM-3.6-FF6B6B?style=flat-square)](https://www.doctrine-project.org/)
[![VBA Macro](https://img.shields.io/badge/Excel-VBA_Macros-217346?style=flat-square&logo=microsoft-excel)](https://www.microsoft.com/)
[![Database](https://img.shields.io/badge/Database-MySQL_8.0-4479A1?style=flat-square&logo=mysql)](https://www.mysql.com/)
[![Docker Containerized](https://img.shields.io/badge/Infrastructure-Docker_%26_Nginx-2496ED?style=flat-square&logo=docker)](https://www.docker.com/)
[![Test Suite](https://img.shields.io/badge/Tests-PHPUnit_13-00B4D8?style=flat-square)](https://phpunit.de/)

> **Resumo:**  
> **Problema:** A gestão de campeonatos amadores sofre com a coleta manual e offline de súmulas em campo, além da latência e erros no envio de dados para o sistema central.  
> **Solução:** Desenvolvimento do **FutHub**, uma solução híbrida composta por **planilhas Excel automatizadas via VBA** para validação e envio HTTP/JSON offline-first, integrada a uma API em **PHP 8.4** e **Symfony 8.1** com **Doctrine ORM**, reduzindo o tempo de sincronização e cálculo de classificações de horas para milissegundos.

---

## 🎯 Principais Destaques Técnicos & Arquitetura

- **Módulo de Automação & Sincronização Excel VBA:** Macros em **VBA** (`modSincronizacao`, `modHttp`, `modJson`) integrados às planilhas offline para validação de dados em tempo de digitação e transmissão assíncrona HTTP/JSON direta para os endpoints da API.
- **Pipeline de Ingestão de Dados em Massa (Batch Processing):** Leitura, validação sintática/semântica e persistência transacional de arquivos e payloads JSON (Times, Jogadores, Partidas, Súmulas) via `ImportService`.
- **Engine de Classificação e Estatísticas Dinâmicas:** Recompilação em tempo real de tabelas de classificação (pontos, saldos, critérios de desempate) e estatísticas de atletas (artilharia, cartões).
- **Modelagem Orientada a Domínio (DDD) & ORM:** Uso de **Doctrine ORM 3.6** com mapeamentos PSR-4, relacionamentos complexos (`Championship`, `Round`, `GameMatch`, `MatchEvent`, `MatchLineup`, `TeamPlayer`) e migrations versionadas.
- **Segurança & Controle de Acesso:** Implementação de autenticação via `SecurityBundle` do Symfony com proteção contra **CSRF** e gerenciamento de permissões (RBAC).
- **Ambiente Containerizado:** Arquitetura pronta para desenvolvimento e produção via **Docker Compose** (PHP 8.4 FPM, Nginx, MySQL 8, PHPMyAdmin).
- **Suíte de Testes Automatizados:** Testes unitários e de integração com **PHPUnit 13** cobrindo serviços críticos de estatísticas, classificação e manipulação de partidas.

---

## 🛠️ Tech Stack & Palavras-Chave (ATS Aligned)

- **Linguagem & Framework Backend:** `PHP 8.4`, `Symfony 8.1` (`FrameworkBundle`, `SecurityBundle`, `Form`, `Validator`, `Messenger`, `Serializer`)
- **Automação & Integração Client-Side:** `VBA (Visual Basic for Applications)`, `Excel Macros`, `HTTP/JSON Sync`
- **Persistência & Banco de Dados:** `Doctrine ORM 3.6`, `Doctrine Migrations`, `MySQL 8.0`
- **Frontend / Template Engine:** `Twig`, `Asset Component`
- **Testes & Qualidade:** `PHPUnit 13`, `PHPStan`, `MakerBundle`
- **Infraestrutura & DevOps:** `Docker`, `Docker Compose`, `Nginx`, `Caddy / FrankenPHP`

---

## 🚀 Como Executar o Projeto

### Pré-requisitos
- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)

### Passos para Inicialização

1. **Clonar o Repositório:**
   ```bash
   git clone https://github.com/seu-usuario/futhub-championship-management-symfony.git
   cd futhub-championship-management-symfony
   ```

2. **Subir os Conteineres Docker:**
   ```bash
   docker compose up -d
   ```
   *A aplicação estará disponível em `http://localhost:8080` e o PHPMyAdmin em `http://localhost:8081`.*

3. **Instalar Dependências (Composer):**
   ```bash
   docker compose exec app composer install
   ```

4. **Executar as Migrações do Banco de Dados:**
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **(Opcional) Carregar Dados Fictícios (Fixtures):**
   ```bash
   docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
   ```

---

## 🧪 Suíte de Testes

Para executar os testes unitários e de integração com PHPUnit:

```bash
docker compose exec app bin/phpunit
```

---

## 📊 Estrutura do Domínio

```text
src/
├── Controller/         # Handlers HTTP & Rotas da Aplicação
├── Entity/             # Entidades do Domínio
├── Service/
│   ├── Import/         # Ingestão e parsing automatizado de planilhas/dados
│   ├── Standings/      # Algoritmos de cálculo de tabela de classificação
│   └── Statistics/     # Cálculo de artilharia, cartões e estatísticas
├── Repository/         # Consultas otimizadas com Doctrine DQL/QueryBuilder
└── DataFixtures/       # Cargas de teste e dados inicializados
```

---

## ✉️ Contato & Conexão

Desenvolvido por **Thiago**  
- **LinkedIn:** [linkedin.com/in/thiago](https://www.linkedin.com/in/thiago-ferreira-54491a278)
- **Portfólio:** [portfolio-thiago](https://portfolio-thiago-df.vercel.app/)