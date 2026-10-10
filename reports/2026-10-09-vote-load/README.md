# Relatório de Benchmark — API de Votação (PivotPHP vs. outros frameworks)

**Data:** 2026-10-09
**Cenário:** `vote-load` (carga mista 50% voto / 30% resultados / 20% candidatos, rampa 10→800 VUs)
**Cada framework medido isolado, 1 execução.**

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
| PHP | 8.3.35 (JIT off) |

---

## 2. Resultados

| Framework | Throughput (req/s) | p95 voto | p99 voto | p95 resultados | Erros | Votos aceitos |
|---|---|---|---|---|---|---|
| **PivotPHP** | 164,3 | 2,97 s | 3,29 s | 2,95 s | 0,01% | 99,96% |
| **Slim 4** | 167,2 | 2,86 s | 3,20 s | 2,86 s | 0,02% | 99,94% |
| **Mezzio** | 167,5 | 2,87 s | 3,19 s | 2,86 s | 0,01% | 99,96% |
| **Symfony** | 171,3 | 2,81 s | 3,10 s | 2,78 s | 0,01% | 99,96% |
| **Webman** | **495,1** | **394,8 ms** | **592,5 ms** | **400,7 ms** | **0,00%** | 99,98% |

---

## 3. Leitura

1. **Correção idêntica**: todos os frameworks retornam ~0% de erro e ~99,9% de votos
   aceitos — o contrato, o SQL, a validação de CPF e o cache Redis são os mesmos.

2. **Webman (async) domina**: ~3× o throughput (495 vs ~165 req/s) e ~7× menor p95
   (395 ms vs ~2,9 s). Por ser assíncrono e com workers persistentes, não sofre o
   gargalo de fila do PHP-FPM sob 800 VUs em 1 CPU.

3. **PHP-FPM saturam no pico**: pivotphp/slim/mezzio/symfony ficam praticamente
   empatados (~165 req/s). O p95 de ~2,9 s reflete **fila** (800 VUs > capacidade de
   1 núcleo + 32 workers), não uma diferença real entre eles.

4. **PivotPHP competitivo**: em termos de throughput, o PivotPHP (164 req/s) está a
   ~2–4% do Slim/Mezzio/Symfony — dentro da margem de ruído de 1 execução.

---

## 4. Ressalvas (importante para interpretação)

- **1 execução por framework** — sem média/mediana de múltiplas rodadas (o SPEC-037
  prevê 3 execuções com mediana). Os empates entre os PHP-FPM estão dentro do ruído.
- **Saturação proposital**: a rampa chega a 800 VUs para medir a *capacidade máxima*.
  Isso pune os frameworks tradicionais com fila (p95 alto). Um teste de *latência a
  carga moderada* (ex.: 100 VUs fixos) mostraria latências muito menores e mais
  comparáveis.
- **Webman usa 4 workers assíncronos** — arquitetura fundamentalmente diferente dos
  PHP-FPM (32 workers síncronos). A comparação é maçãs×laranjas em runtime, como já
  havíamos sinalizado; está aqui por pedido explícito.
- **WSL2** distorce I/O e CPU — o guia recomenda host dedicado para a bateria oficial.
- **Redis cacheia candidatos/resultados** — o endpoint de resultados pode ser servido do
  cache (o que favorece leituras), conforme o contrato.

---

## 5. Arquivos brutos

`results/20261009_183647/<framework>_vote-load.txt` — saída completa do k6 (percentis,
métricas customizadas, checks) de cada framework.
