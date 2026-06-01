# Framework Whitelabel para Sistemas Online de Aluguel de Casas e Apartamentos

Framework reutilizável, baseado em arquitetura de microsserviços, para a construção
rápida de plataformas de aluguel de imóveis (white-label). O núcleo do reuso é a
**engine de reservas** (`RentEngine`), implementada com o padrão de projeto
**Template Method**, permitindo que diferentes modalidades de locação
(temporada, longa duração, etc.) sejam construídas reaproveitando o fluxo comum.

---

## Contexto Acadêmico

| Item | Descrição |
|------|-----------|
| **Instituição** | UFAL — Universidade Federal de Alagoas |
| **Período** | 2026.1 |
| **Disciplina** | Reuso de Software e Metodologias Ágeis |
| **Alunos** | Marcos Melo dos Santos e Leonardo Barbosa |

---

## Arquitetura (Monorepo)

O projeto é organizado como um **monorepo** unificado em **PHP 8.2** e **MySQL**,
composto por um API Gateway e três microsserviços independentes:

```
aluguel-services-framework/
├── gateway/        # API Gateway — roteamento central stateless (cURL)
├── ms-auth/        # Autenticação JWT
├── ms-catalogo/    # Catálogo de imóveis
└── ms-reservas/    # Engine do framework (Template Method)
```

### Mapa de Portas e Bancos de Dados

| Serviço         | Porta | Banco de Dados | Responsabilidade                                            |
|-----------------|:-----:|----------------|-------------------------------------------------------------|
| **Gateway**     | 8003  | —              | Ponto de entrada único, roteamento e composição via cURL    |
| **ms-auth**     | 8000  | `db_auth`      | Autenticação e emissão/validação de tokens JWT              |
| **ms-catalogo** | 8001  | `db_catalogo`  | Cadastro e consulta de imóveis (casas e apartamentos)       |
| **ms-reservas** | 8002  | `db_reservas`  | Engine de reservas com Template Method e hotspots           |

> O **cliente** (front-end) conversa **somente** com o Gateway (porta 8003).
> O Gateway redireciona as requisições para os microsserviços internos via cURL.

---

## Pré-requisitos

- PHP **8.2+** (com extensões `pdo_mysql`, `curl`, `json`, `mbstring`)
- MySQL **8.0+**
- Composer **2.x**

---

## Guia Rápido (Quick Start)

Cada serviço sobe com o servidor embutido do PHP. Em terminais separados:

```bash
# 1) Autenticação (db_auth)
cd ms-auth      && composer install && php -S localhost:8000 -t public

# 2) Catálogo (db_catalogo)
cd ms-catalogo  && composer install && php -S localhost:8001 -t public

# 3) Reservas (db_reservas)
cd ms-reservas  && composer install && php -S localhost:8002 -t public

# 4) Gateway (ponto de entrada do usuário)
cd gateway      && composer install && php -S localhost:8003 -t public
```

Acesse a aplicação em: **http://localhost:8003**

---

## Banco de Dados

Cada microsserviço possui um banco isolado (princípio *Database per Service*).
Os scripts de criação ficam em `*/sql/` de cada serviço:

```bash
mysql -u root -p < ms-auth/sql/db_auth.sql
mysql -u root -p < ms-catalogo/sql/db_catalogo.sql
mysql -u root -p < ms-reservas/sql/db_reservas.sql
```

---

## Estratégia de Reuso

O coração do framework é a classe abstrata **`RentEngine`** (em `ms-reservas`),
que define o esqueleto invariável do processo de reserva (Template Method) e expõe
**hotspots** (pontos de variação) para as aplicações concretas:

- **`VacationRent`** — aluguel por temporada.
- **`LongTermRent`** — aluguel de longa duração, estendido com `CreditCheckComponent`
  (análise de crédito) — demonstrando reuso por composição.

---

## Roadmap de Implementação (Backlog)

- [x] **Etapa 1** — Inicialização (`.gitignore`, `README.md`, Relatório LaTeX)
- [ ] **Etapa 2** — API Gateway (roteamento stateless + cURL)
- [ ] **Etapa 3** — `ms-auth` (JWT) + `db_auth`
- [ ] **Etapa 4** — `ms-catalogo` + `db_catalogo`
- [ ] **Etapa 5** — `ms-reservas` + `RentEngine` (Template Method) + `db_reservas`
- [ ] **Etapa 6** — Classes concretas (`LongTermRent`, `VacationRent`)
- [ ] **Etapa 7** — Front-end integrado no Gateway (Blade/HTML)

---

## Licença

Distribuído sob a licença incluída no arquivo [LICENSE](LICENSE).
