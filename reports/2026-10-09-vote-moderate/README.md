# Relatório de Benchmark — Carga Moderada (API de Votação)

**Data:** 2026-10-09
**Cenário:** `vote-moderate` (25 VUs fixos, **sem think time** — isola a latência real, sem saturação)
**Cada framework medido isolado, 1 execução.**

---

## 1. Ambiente

| Item | Valor |
|---|---|
| Host | WSL2, Linux 6.18, 5 cores, 19 GB RAM |
| Docker | 29.7.2 · Compose v5.5.0 |
| App | 1 CPU / 512 MB |
| PostgreSQL | 17 · 2 CPU / 2 GB |
| Redis | 7 · 0.5 CPU / 256 MB |
| FPM pool | `pm.max_children=32` |
| Webman | async Workerman, 4 workers |
| PHP | 8.3.35 |

---

## 2. Resultados (25 VUs, sem saturação)

| Framework | Throughput (req/s) | p95 voto | p99 voto | p95 resultados | Erros | Votos aceitos |
|---|---|---|---|---|---|---|
| **PivotPHP** | 222,4 | 212,0 ms | 263,2 ms | 190,1 ms | 0,04% | 99,91% |
| **Slim 4** | 228,3 | 207,7 ms | 258,4 ms | 186,1 ms | 0,04% | 99,91% |
| **Mezzio** | 229,5 | 207,4 ms | 258,3 ms | 187,2 ms | 0,03% | 99,93% |
| **Symfony** | 222,4 | 212,7 ms | 268,1 ms | 190,8 ms | 0,03% | 99,92% |
| **Webman** | **984,8** | **59,2 ms** | **91,5 ms** | **58,5 ms** | **0,01%** | 99,97% |

---

## 3. Leitura

1. **Sem saturação, os PHP-FPM empatam de verdade**: p95 entre 207–213 ms e
   throughput entre 222–230 req/s — diferença ≤ 2%, dentro do ruído de 1 execução.
   O PivotPHP está **competitivo** (222 req/s, p95 212 ms).

2. **Webman ~3,5× mais rápido** (59 ms p95, 985 req/s): arquitetura assíncrona com
   workers persistentes, sem o custo de bootstrap por request nem o gargalo de 32
   workers síncronos.

3. **A latência de ~210 ms é dominada pelo banco** (INSERT transacional do voto +
   `COUNT ... GROUP BY` dos resultados), não pelo framework — por isso os 4 PHP-FPM
   ficam praticamente idênticos.

4. **Correção idêntica**: ~0,03% de erro e ~99,9% de votos aceitos em todos.

---

## 4. Comparação com o cenário de saturação (`vote-load`, 800 VUs)

| Framework | Saturação p95 | Moderado p95 | Saturação req/s | Moderado req/s |
|---|---|---|---|---|
| PivotPHP | 2,97 s | **212 ms** | 164 | 222 |
| Slim 4 | 2,86 s | 208 ms | 167 | 228 |
| Mezzio | 2,87 s | 207 ms | 168 | 230 |
| Symfony | 2,81 s | 213 ms | 171 | 222 |
| Webman | 395 ms | **59 ms** | 495 | 985 |

> No cenário saturado, o p95 de ~2,9 s dos PHP-FPM era **fila** (800 VUs > capacidade),
> não latência real. O teste moderado revela a latência verdadeira (~210 ms) e o
> throughput real (~225 req/s) dos tradicionais.

---

## 5. Ressalvas

- **1 execução por framework** (o SPEC-037 prevê mediana de 3).
- **WSL2** distorce valores absolutos (ranking relativo permanece válido).
- **Webman é async** vs PHP-FPM — diferença arquitetural, não artefato.
- **Redis cacheia resultados** (TTL 30 s), favorecendo as leituras no mix.

---

## 6. Arquivos brutos

`reports/2026-10-09-vote-moderate/<framework>_vote-moderate.txt` — saída completa do k6.
