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

> **Estado atual:** os grupos **A** e **B** estão implementados **apenas na variante PivotPHP** — ainda não
> há comparação entre frameworks para eles (pendente: SPEC-109). O grupo **C** (votação) é o único
> comparável entre os 5 frameworks.

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

**Prévia — core 5.0, `vote-load` × 5 repetições** (2026-10-10, host local WSL2 ·
[relatório](reports/2026-10-10-vote-load-5x-preview/README.md)):

| Framework | req/s (média ± IC95) | p50 | p95 | Erros | CPU app |
|---|---:|---:|---:|---:|---:|
| Slim 4 | 179,14 ± 3,70 | 675 ms | 2,59 s | 0,012% | 82% |
| Symfony | 178,95 ± 3,57 | 675 ms | 2,61 s | 0,016% | 80% |
| Mezzio | 178,80 ± 3,95 | 672 ms | 2,60 s | 0,016% | 80% |
| PivotPHP 5.0 | 175,21 ± 6,84 | 698 ms | 2,67 s | 0,018% | 80% |
| Webman *(worker persistente — ranking separado)* | 511,85 ± 16,18 | 7 ms | 0,35 s | 0,002% | 23% |

> **Empate técnico no grupo PHP-FPM**: os intervalos de confiança se sobrepõem e a diferença pareada do
> PivotPHP (−2%) não é estatisticamente significativa. É uma **prévia**: o host estava em uso durante a
> rodada, o k6 rodou na mesma máquina e a ordem dos frameworks era fixa. A rodada de referência será feita
> com as APIs numa VM dedicada e a carga gerada em outra máquina (SPEC-110).
>
> O `vote-load` **satura** os frameworks PHP-FPM (p95 > 2,5 s, acima dos thresholds): os números indicam
> capacidade sob fila, não latência dentro do SLO.

Consolidar repetições: `python3 scripts/consolidate-runs.py --scenario vote-load results/<run1> results/<run2> ...`

## Limites de recursos (justiça entre frameworks)

| Serviço | CPU | Memória |
|---|---|---|
| App PHP | 1.0 | 512M |
| PostgreSQL | 2.0 | 2G |
| Redis | 0.5 | 256M |

> `deploy.resources` vale em Swarm mode ou `docker compose --compatibility`. O FPM pool
> (`pm.max_children`) é idêntico entre frameworks.
