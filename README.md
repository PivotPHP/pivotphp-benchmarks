# PivotPHP Benchmarks — API de Votação

Benchmark realista comparando frameworks PHP executando a **mesma API de votação**, em
**Docker com recursos idênticos e limitados**. Mede latência (p95/p99), throughput
sustentado, taxa de erro e eficiência de recursos.

> **Status**: todos os 5 frameworks implementados e validados (smoke test 0% falhas).
> A variante **PivotPHP** usa o core **5.2** (`pivotphp/core ^5.2`, com pipeline pré-compilado,
> roteador tipado por instância e logging desacoplado); os demais estão inalterados.

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
├── microbench/routing/    # microbench do pivotphp/core-routing (uma versão por versions/<x.y.z>)
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

## Microbench de roteamento

Mede só o `pivotphp/core-routing`, sem HTTP nem banco, para comparar versões do roteador. Cada versão
fica em `microbench/routing/versions/<versão>/` com `composer.json` e `composer.lock` próprios.
O `bench.php` chama o router como o core daquela versão chama: `resolve()` na 3.1+ (core 5.2) e
`identify()` seguido de `allowedMethods()` na 3.0 (core 5.0).

```bash
./scripts/run-microbench-routing.sh                       # 5 rodadas; 18, 100 e 500 rotas
./scripts/run-microbench-routing.sh --rounds 10 --routes "500"
```

- Cenários: rota estática, dinâmica, `HEAD` (fallback para `GET`), `OPTIONS` automático, `405`, `404` e o
  registro completo das N rotas. O alvo é o último recurso registrado (pior caso de busca linear).
- Cada medição roda num container novo (PHP 8.3 CLI, OPcache ligado, JIT desligado, 1 vCPU), com as versões
  alternadas a cada rodada. O relatório (`results/<run>/report.md`, gerado por
  `scripts/compare-microbench-routing.py`) traz µs por operação ± IC95 e o ganho pareado por rodada.
- Antes de medir, o script confere que cada cenário tem o mesmo desfecho (casou, 405/OPTIONS ou 404) nas duas
  versões; se não tiver, a rodada falha.

Resultado de referência: [core-routing 3.0.0 × 3.1.0](reports/2026-10-29-microbench-routing/README.md).

## Resultados

Os relatórios consolidados ficam em [`reports/`](reports/) e os dados brutos (k6 + smoke) em
`results/<timestamp>/` (ignorados no git).

**Referência — core 5.0, `vote-load` × 5 repetições** (2026-10-11, APIs numa VM dedicada e carga gerada em
outro computador, ordem rotacionada · [relatório](reports/2026-10-11-vote-load-5x-remote/README.md)):

| Framework | req/s (média ± IC95) | p50 | p95 | Erros | CPU app |
|---|---:|---:|---:|---:|---:|
| Mezzio | 186,85 ± 1,00 | 640 ms | 2,34 s | 0,014% | 80% |
| Slim 4 | 186,21 ± 2,14 | 646 ms | 2,36 s | 0,012% | 80% |
| Symfony | 186,16 ± 0,87 | 651 ms | 2,35 s | 0,012% | 79% |
| PivotPHP 5.0 | 185,09 ± 0,82 | 657 ms | 2,38 s | 0,014% | 80% |
| Webman *(worker persistente — ranking separado)* | 523,26 ± 3,39 | 8 ms | 0,28 s | 0,000% | 18% |

> **Grupo PHP-FPM praticamente equivalente** (185–187 req/s). O PivotPHP fica 0,6–0,9% abaixo dos demais;
> a diferença é estatisticamente significativa só contra o Mezzio (−0,9%) e indica um custo de CPU por
> requisição ligeiramente maior. O `vote-load` **satura** os frameworks PHP-FPM (p95 > 2,3 s, acima dos
> thresholds): os números indicam capacidade sob fila, não latência dentro do SLO. Limitações da rodada no
> relatório.

Rodadas anteriores: [prévia local de 2026-10-10](reports/2026-10-10-vote-load-5x-preview/README.md) (host em
uso, k6 no mesmo host e ordem fixa — superada por esta).

Execução remota (APIs numa VM, k6 em outro computador): [`reports/remote-setup.md`](reports/remote-setup.md) e
`./scripts/run-benchmark-remote.sh --host usuario@ip-da-vm --repeats 5 --warmup 1`.

Consolidar repetições: `python3 scripts/consolidate-runs.py --scenario vote-load results/<run1> results/<run2> ...`

## Limites de recursos (justiça entre frameworks)

| Serviço | CPU | Memória |
|---|---|---|
| App PHP | 1.0 | 512M |
| PostgreSQL | 2.0 | 2G |
| Redis | 0.5 | 256M |

> `deploy.resources` vale em Swarm mode ou `docker compose --compatibility`. O FPM pool
> (`pm.max_children`) é idêntico entre frameworks.
