# PivotPHP Benchmarks — API de Votação

Benchmark realista comparando frameworks PHP executando a **mesma API de votação**, em
**Docker com recursos idênticos e limitados**. Mede latência (p95/p99), throughput
sustentado, taxa de erro e eficiência de recursos.

> **Status**: todos os 5 frameworks implementados e validados (smoke test 0% falhas).
> A variante **PivotPHP** usa o core **5.0** (`pivotphp/core ^5.0`, core-routing 3.0 — roteador por
> instância); os demais estão inalterados.

## Frameworks

| Framework | Diretório | Porta | Status |
|---|---|---|---|
| PivotPHP | `pivotphp/` | 8080 | ✅ |
| Slim 4 | `slim/` | 8081 | ✅ |
| Mezzio | `mezzio/` | 8082 | ✅ |
| Symfony (microkernel) | `symfony/` | 8083 | ✅ |
| Webman (async) | `webman/` | 8084 | ✅ |

Cada framework é **autocontido**: `Dockerfile`, `docker/` (nginx/php configs), `app/` ou `src/`
(código da API), `database/` (schema + seed) e `docker-compose.yml` próprios. O ambiente de
cada framework é **isolado** (banco e cache próprios), e um framework é medido por vez.

Limites estritos de recursos são garantidos via cgroups e Docker Compose:
- **Aplicação**: 1.0 vCPU / 512 MB RAM
- **PostgreSQL**: 2.0 vCPU / 2048 MB RAM
- **Redis**: 0.5 vCPU / 256 MB RAM

## Grupos de Avaliação Arquitetural

A suíte divide os testes em três grupos de medição (ver [Metodologia detalhada](reports/methodology.md)):

### Grupo A — Overhead Mínimo do Framework (sem banco)
- `GET /api/health` — JSON simples (`{"status":"ok"}`)
- `GET /api/ping` — Resposta em texto puro (`pong`)

### Grupo B — CRUD com Persistência Relacional e Cache
- `GET /api/products` — Listagem paginada (`page`, `limit`) e ordenada por id
- `GET /api/products/{id}` — Detalhe de produto por chave primária
- `POST /api/products` — Cadastro com validação de payload e inserção relacional
- `PATCH /api/products/{id}` — Atualização parcial com invalidação de cache
- `DELETE /api/products/{id}` — Exclusão física com invalidação de cache
- `GET /api/dashboard` — Agregação analítica (`COUNT`, `AVG`, `SUM`, agrupamento por categoria) com cache no Redis

### Grupo C — Regras de Negócio e Concorrência (Votação)
- `GET /api/candidates` — Lista candidatos (cache Redis → PostgreSQL)
- `GET /api/candidates/{id}` — Detalhe de candidato
- `POST /api/votes` — Voto concorrente (validação de CPF, transação ACID com detecção de unicidade `409`)
- `GET /api/votes/results` — Resultados consolidados por candidato
- `DELETE /api/votes` — Limpeza determinística para testes

## Estrutura

```
pivotphp-benchmarks/
├── pivotphp/              # framework autocontido (implementado)
│   ├── Dockerfile         # Nginx + PHP-FPM (idêntico entre frameworks)
│   ├── docker/            # nginx.conf, php.ini, www.conf (pm.max_children fixo)
│   ├── app/               # public/index.php + src/ (Controllers, Support)
│   ├── database/          # schema.sql + seed.sql
│   ├── composer.json
│   └── docker-compose.yml # postgres + redis + app (isolado)
├── slim/                  # Slim 4 (idêntica estrutura)
├── mezzio/                # Mezzio (idêntica estrutura)
├── symfony/               # Symfony microkernel (idêntica estrutura)
├── webman/                # Webman async (own server, sem Nginx/PHP-FPM)
├── k6/                    # smoke.js, vote-load.js, vote-spike.js, vote-soak.js
├── scripts/run-benchmark.sh  # orquestrador
└── results/               # saídas (ignoradas no git)
```

## Como rodar (orquestrador)

```bash
# Todos os frameworks, cenário vote-load
./scripts/run-benchmark.sh

# Só um framework
./scripts/run-benchmark.sh pivotphp

# Cenário específico
./scripts/run-benchmark.sh slim spike
```

Os resultados brutos ficam em `results/<timestamp>/`. Cada framework sobe/desce a própria
stack (isolada) e o k6 roda `smoke.js` (validação) + o cenário escolhido.

## Como rodar (PivotPHP)

```bash
cd pivotphp
docker compose up --build -d postgres redis app

# Smoke manual
curl http://localhost:8080/api/health
curl http://localhost:8080/api/candidates
curl -X POST http://localhost:8080/api/votes -H 'Content-Type: application/json' \
  -d '{"candidateId":1,"cpf":"11144477735"}'
curl http://localhost:8080/api/votes/results

# Smoke via k6
docker compose --profile loadtest run --rm k6 run /scripts/smoke.js
```

## Resultados

Os relatórios consolidados ficam em [`reports/`](reports/) e os dados brutos (k6 + smoke) em
`results/<timestamp>/` (ignorados no git).

**Linha de base — PivotPHP 5.0** (`vote-load`, 2026-10-10, host WSL2 · Docker 29.7.2):

| Métrica | valor |
|---|---|
| Smoke | ✅ aprovado — `http_req_failed 0.00%` (funcionalmente correto) |
| Throughput | 160,93 req/s (`http_reqs 43226`) |
| Latência (voto) | avg 1,01 s · med 784 ms · p95 **3,05 s** · p99 3,29 s |
| Erros | 0,02% (`9/43226`; nenhum 500) |

> O `vote-load` **satura** o ambiente (p95 ~3 s ≫ thresholds) — os números indicam o ponto de
> degradação sob essa carga, **não** a capacidade no SLO. Sem regressão vs. o core 4.0 (~165 req/s no
> mesmo cenário). Parte de `reports/2026-10-10-vote-load/`.

## Limites de recursos (justiça entre frameworks)

| Serviço | CPU | Memória |
|---|---|---|
| App PHP | 1.0 | 512M |
| PostgreSQL | 2.0 | 2G |
| Redis | 0.5 | 256M |

> `deploy.resources` vale em Swarm mode ou `docker compose --compatibility`. O FPM pool
> (`pm.max_children`) é idêntico entre frameworks.
