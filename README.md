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

Cada framework é **autocontido**: `Dockerfile`, `docker/` (nginx/php configs), `app/`
(código da API), `database/` (schema + seed) e `docker-compose.yml` próprios. O ambiente de
cada framework é **isolado** (banco e cache próprios), e um framework é medido por vez.

## Contrato da API

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/health` | Health check (sem dependências) |
| `GET` | `/api/candidates` | Lista candidatos (cache Redis → banco) |
| `GET` | `/api/candidates/{id}` | Detalhe de um candidato |
| `POST` | `/api/votes` | Registra voto (valida CPF, voto duplicado, transação, invalida cache) |
| `GET` | `/api/votes/results` | Resultados agregados |
| `GET` | `/api/votes/results/{candidateId}` | Resultado de um candidato |
| `DELETE` | `/api/votes` | Limpa votos (teste) |

Regras:

- `POST /api/votes` recebe `{"candidateId": int, "cpf": string}`.
- CPF validado com dígitos verificadores.
- Voto duplicado (mesmo CPF) → `409`; candidato inexistente → `404`; entrada inválida → `422`.
- Resultados agregados via `COUNT` + `GROUP BY`, com cache Redis invalidado a cada voto.

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
