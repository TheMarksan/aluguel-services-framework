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

### Divisão de Escopo (Dupla)

| Aluno | Responsabilidade |
|-------|------------------|
| **Marcos Melo dos Santos** | Gateway, núcleo de Reservas (`RentEngine` / Template Method) e Front-end integrado (baseado em Figma via MCP) |
| **Leonardo Barbosa** | Persistência e lógica interna dos serviços de **Auth** e **Catálogo** (banco de dados, autorização e criptografia) |

> O ecossistema roda de ponta a ponta. Os pontos sob responsabilidade do Leonardo que
> ainda não foram finalizados estão listados na seção **[Pendências de Implementação](#pendências-de-implementação-leonardo)**.

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

A interface web (servida pelo Gateway) inclui **hero com busca avançada**, filtros por
tipo/local/preço/quartos, cards de imóveis e fluxo de reservas. Consome todo o
ecossistema via `/api/*`. Login do seed: `admin@aluguel.dev` / `admin123`.

### Front-end (Design via Figma MCP)

O layout foi derivado da fusão de dois kits da Figma Community, extraídos via MCP
(`get_figma_data`), com **paleta de cores própria** (identidade UFAL):

| Referência Figma | Elementos reutilizados |
|------------------|------------------------|
| [M-Rent — Property Management](https://www.figma.com/design/C7kvO7HltB2PsnpfAyBndG/) | Cards de imóvel (12px), listagem, filtros por tipo |
| [Estatery — Real Estate SaaS UI Kit](https://www.figma.com/design/M2Aw6pJ8HQN6xssgFc4hgj/) | Hero, Search Bar, tabs, barra de resultados |

Arquivos: `gateway/views/home.php` + `gateway/public/assets/css/app.css`

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

- **`VacationRent`** (`modality: vacation`) — aluguel por temporada. Reuso por
  **herança**: reaproveita o esqueleto e sobrescreve apenas os hotspots mínimos
  (preço com adicional de alta temporada + taxa de limpeza).
- **`LongTermRent`** (`modality: long_term`) — aluguel de longa duração. Combina
  **herança** (preço mensal com desconto para contratos longos) e **composição**:
  injeta o `CreditCheckComponent` no hook `applyBusinessRules`, que define se a
  reserva fica `confirmed` ou `pending` conforme a análise de crédito.

Exemplo de criação de reserva de longa duração (via Gateway):

```bash
curl -X POST http://localhost:8003/api/reservas \
  -H "Content-Type: application/json" \
  -d '{
    "modality": "long_term",
    "property_id": 1,
    "check_in": "2026-07-01",
    "check_out": "2027-07-01",
    "monthly_rate": 4000,
    "extras": { "monthly_income": 13000 }
  }'
```

---

## Endpoints (via Gateway)

Todas as rotas abaixo são acessadas pelo Gateway (`http://localhost:8003`),
que as encaminha ao microsserviço correto via cURL.

### Autenticação — `ms-auth`

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/api/auth/register` | Cria um usuário |
| POST | `/api/auth/login` | Autentica e retorna um token JWT |
| GET | `/api/auth/me` | Dados do usuário do token (`Authorization: Bearer`) |
| POST | `/api/auth/validate` | Valida um token (uso interno) |

### Catálogo — `ms-catalogo`

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/imoveis` | Lista imóveis (filtros: `city`, `type`, `available`) |
| GET | `/api/imoveis/{id}` | Detalha um imóvel |
| POST | `/api/imoveis` | Cadastra um imóvel |
| PUT | `/api/imoveis/{id}` | Atualiza um imóvel |
| DELETE | `/api/imoveis/{id}` | Remove um imóvel |

### Reservas — `ms-reservas`

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/reservas` | Lista reservas + modalidades suportadas |
| GET | `/api/reservas/{id}` | Detalha uma reserva |
| POST | `/api/reservas` | Cria uma reserva (campo `modality` seleciona a engine) |

> A criação de reserva passa pelo Template Method da `RentEngine`. As modalidades
> concretas (`vacation`, `long_term`) são registradas na Etapa 6.

---

## Roadmap de Implementação (Backlog)

- [x] **Etapa 1** — Inicialização (`.gitignore`, `README.md`, Relatório LaTeX)
- [x] **Etapa 2** — API Gateway (roteamento stateless + cURL)
- [x] **Etapa 3** — `ms-auth` (JWT) + `db_auth`
- [x] **Etapa 4** — `ms-catalogo` + `db_catalogo`
- [x] **Etapa 5** — `ms-reservas` + `RentEngine` (Template Method) + `db_reservas`
- [x] **Etapa 6** — Classes concretas (`LongTermRent`, `VacationRent`)
- [x] **Etapa 7** — Front-end integrado no Gateway (Blade/HTML)

---

## Pendências de Implementação (Leonardo)

Esta seção lista **apenas o que realmente falta** nas partes sob responsabilidade do
Leonardo (lógica interna e persistência de **Auth** e **Catálogo**). O restante do
ecossistema (Gateway, Reservas/`RentEngine` e Front-end) está funcional.

> O código de Auth e Catálogo **já existe** (rotas, PDO, bcrypt e JWT HS256), porém os
> itens abaixo precisam ser concluídos/validados para uma operação real e segura.

### Configuração e Provisionamento (Auth + Catálogo)
- [x] Criar os arquivos `.env` reais a partir dos `.env.example` (hoje só existem os exemplos).
- [x] Definir um `JWT_SECRET` forte em `ms-auth/.env` (atualmente é um placeholder).
- [x] Provisionar e validar os bancos em um MySQL real (`db_auth`, `db_catalogo`) executando os scripts de `*/sql/`.

### Autenticação (`ms-auth`)
- [x] **Aplicar autorização por papéis (`role`)**: o campo existe no schema, mas não é verificado em nenhuma rota.
- [x] Substituir o hash fixo do admin no `db_auth.sql` por um seed/migração que gere o hash em tempo de execução.
- [x] Fluxo de sessão completo: refresh token e logout/revogação (hoje há apenas validação de `exp`).
- [x] Mapear erros de banco (`PDOException`) para respostas HTTP amigáveis.

### Catálogo (`ms-catalogo`)
- [ ] **Proteger os endpoints de escrita** (`POST`/`PUT`/`DELETE /api/imoveis`): atualmente estão abertos, sem checagem de token/role.
- [ ] Vincular `owner_id` ao usuário autenticado (hoje é aceito direto do payload).
- [ ] Paginação e ordenação na listagem (hoje retorna todos os registros).
- [ ] Validações de negócio adicionais (preços não-negativos, tipos, etc.).

### Integração de Segurança (limite entre as duas partes)
- [ ] Acionar `POST /api/auth/validate` como middleware nas rotas protegidas: o Gateway hoje
      apenas **repassa** o header `Authorization`, sem validar o token antes de encaminhar.

---

## Licença

Distribuído sob a licença incluída no arquivo [LICENSE](LICENSE).
