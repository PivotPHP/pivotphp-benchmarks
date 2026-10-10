# Relatório de Benchmark — API de Votação (PivotPHP 4.0 vs. outros frameworks)

**Data:** 2026-10-10
**Cenário:** `vote-load` (carga mista 50% voto / 30% resultados / 20% candidatos, rampa 10→800 VUs)
**Cada framework medido isolado, 1 execução.**

> Atualização do relatório de 2026-10-09 com o **PivotPHP v4.0.0** (core-routing 2.2.1,
> http 1.0.0, security 1.0.0).

---

## 1. Ambiente

| Item | Valor |
|---|---|
| Host | WSL2, Linux 6.18, 5 cores, 19 GB RAM |
| Docker | 29.7.2 · Compose v5.5.0 |
| App | 1 CPU / 512 MB (limitado via `deploy.resources`) |
| PostgreSQL | 17 · 2 CPU / 2 GB |
| Redis | 7 · 0.5 CPU / 256 MB |
| FPM pool | `pm.max_children=32` (PHP-FPM frameworks) |
| Webman | async Workerman, 4 workers |
| PHP | 8.3 (JIT off) |

---

## 2. Resultados (PivotPHP 4.0)

| Framework | Throughput (req/s) | p95 voto | p99 voto | Checks OK |
|---|---|---|---|---|
| **PivotPHP 4.0** | 164,97 | 3,00 s | 3,27 s | 99,98% |
| **Slim 4** | 166,00 | 2,92 s | 3,34 s | 99,98% |
| **Mezzio** | 162,56 | 3,07 s | 3,94 s | 99,98% |
| **Symfony** | 167,64 | 2,79 s | 3,12 s | 99,98% |
| **Webman** | **493,62** | **441,52 ms** | **657,42 ms** | 99,99% |

### Comparação PivotPHP 3.x → 4.0 (mesmo cenário)

| Versão | Throughput (req/s) | p95 voto | p99 voto |
|---|---|---|---|
| PivotPHP 3.x (2026-10-09) | 164,3 | 2,97 s | 3,29 s |
| **PivotPHP 4.0 (2026-10-10)** | **164,97** | **3,00 s** | **3,27 s** |

**Sem regressão**: a 4.0 (Router simplificado + `BodyParser` na pipeline) mantém os mesmos
números — as diferenças estão dentro do ruído de 1 execução em WSL2.

---

## 3. Leitura

1. **Correção idêntica**: todos retornam ~0% de erro e ~99,98% de checks OK — mesmo contrato,
   SQL, validação de CPF e cache Redis.
2. **Webman (async) domina**: ~3× o throughput (~494 vs ~165 req/s) e ~7× menor p95
   (442 ms vs ~3 s) — arquitetura assíncrona com workers persistentes, sem a fila do PHP-FPM.
3. **PHP-FPM empatam no pico**: pivotphp/slim/mezzio/symfony ~163–168 req/s. O p95 de ~3 s
   reflete **fila** (800 VUs > capacidade de 1 núcleo + 32 workers), não diferença real.
4. **PivotPHP 4.0 competitivo**: dentro de ~2% do Slim/Mezzio/Symfony (ruído).

---

## 4. Ressalvas

- **1 execução por framework** — sem mediana de múltiplas rodadas (o SPEC-037 prevê 3).
- **Saturação proposital** (rampa até 800 VUs) — p95 alto por fila no PHP-FPM.
- **Webman** usa 4 workers assíncronos — comparação maçãs×laranjas em runtime.
- **WSL2** distorce I/O e CPU.
- **Redis** cacheia candidatos/resultados.

---

## 5. Arquivos brutos

`results/20261010_150341/<framework>_vote-load.txt` — saída completa do k6.
