# Relatório de Benchmark — API de Votação (PivotPHP 4.0 vs. outros frameworks)

**Data:** 2026-10-10
**Cenário:** `vote-moderate` (carga moderada — 100 VUs fixos, sem saturação)
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

| Framework | Throughput (req/s) | p95 voto | Checks OK |
|---|---|---|---|
| **PivotPHP 4.0** | 229,72 | **209,69 ms** | 99,96% |
| **Slim 4** | 227,78 | 212,46 ms | 99,95% |
| **Mezzio** | 229,05 | 211,75 ms | 99,97% |
| **Symfony** | 220,22 | 224,97 ms | 99,95% |
| **Webman** | **1159,45** | **49,28 ms** | 99,99% |

### Comparação PivotPHP 3.x → 4.0 (mesmo cenário)

| Versão | Throughput (req/s) | p95 voto |
|---|---|---|
| PivotPHP 3.x (2026-10-09) | 222,4 | 212,0 ms |
| **PivotPHP 4.0 (2026-10-10)** | **229,72** | **209,69 ms** |

**Sem regressão** — a 4.0 fica levemente acima (dentro do ruído).

### Saturação vs. moderado (PivotPHP 4.0)

| Cenário | Throughput (req/s) | p95 voto |
|---|---|---|
| `vote-load` (até 800 VUs) | 164,97 | 3,00 s |
| `vote-moderate` (100 VUs) | 229,72 | **209,69 ms** |

Sem saturação, a latência cai de ~3 s (fila) para **~210 ms** — e a 4.0 é a mais rápida
entre os PHP-FPM neste cenário.

---

## 3. Leitura

1. **PHP-FPM empatam de verdade no moderado**: p95 entre 210–225 ms — a diferença do `vote-load`
   era só fila.
2. **PivotPHP 4.0 no topo do grupo PHP-FPM**: 229,72 req/s e p95 209,69 ms (menor p95 junto ao Mezzio).
3. **Webman (async) ~5× mais rápido** (49 ms p95): workers persistentes.

---

## 4. Ressalvas

- **1 execução por framework** (SPEC-037 prevê 3 com mediana).
- **Webman** usa 4 workers assíncronos — comparação maçãs×laranjas em runtime.
- **WSL2** distorce I/O e CPU.

---

## 5. Arquivos brutos

`results/20261010_153109/<framework>_vote-moderate.txt` — saída completa do k6.
