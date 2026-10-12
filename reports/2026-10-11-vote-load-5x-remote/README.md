# Rodada de referência — `vote-load` × 5 repetições (core 5.0, execução remota)

> Rodada de referência da SPEC-106, executada na topologia remota da SPEC-110: as APIs numa VM dedicada e a
> carga gerada por outro computador. Substitui a [prévia local](../2026-10-10-vote-load-5x-preview/README.md)
> como base de comparação.

- **Data**: 2026-10-11, 13:23 (horário de Brasília); run `20261011_132303_remote`; commit da suíte `89518af`.
- **Versões**: `pivotphp/core` 5.0.0, `pivotphp/core-routing` 3.0.0, `pivotphp/http` 1.0.1.
- **APIs (SUT)**: VM Hyper-V com Ubuntu Server 22.04 (kernel 5.15), 6 vCPU e 12 GB de memória fixos,
  disco fixo, Docker 29.9.0, num host Intel i7-13700H. Limites por stack: app 1 vCPU / 512 MB,
  PostgreSQL 2 vCPU / 2 GB, Redis 0,5 vCPU / 256 MB. Uma stack ligada por vez.
- **Gerador**: notebook Intel i5-9300HF, Windows 11 Home, k6 v2.3.0 nativo.
- **Rede**: os dois no mesmo switch, por cabo (gigabit). Latência base: média 0 ms, máximo 1 ms, sem perda
  ([`ping-raw.txt`](ping-raw.txt), saída do `ping.exe` na codificação do console, CP850).
- **Execução**: `./scripts/run-benchmark-remote.sh --host bench@<vm> --repeats 5 --warmup 1` — 1 repetição de
  aquecimento descartada e 5 medidas, com a **ordem dos frameworks rotacionada** (cada framework passa uma vez
  por cada posição).
- **Consolidação**: [`consolidated.md`](consolidated.md), gerado por
  `python3 scripts/consolidate-runs.py --scenario vote-load rep1 rep2 rep3 rep4 rep5`.
- **Dados brutos**: `rep1/`…`rep5/` (logs e resumos JSON do k6, smoke, `resource-usage.csv`),
  `metadata.json` e `host-cpu-usage.csv`.

## Resultados

| Framework | req/s (média ± IC95) | CV | p50 | p95 | p99 | Erros | CPU app | Memória máx. |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Mezzio | 186,85 ± 1,00 | 0,4% | 640 ms | 2,34 s | 2,53 s | 0,014% | 79,6% | 77,0 MiB |
| Slim 4 | 186,21 ± 2,14 | 0,9% | 646 ms | 2,36 s | 2,57 s | 0,012% | 79,7% | 76,2 MiB |
| Symfony | 186,16 ± 0,87 | 0,4% | 651 ms | 2,35 s | 2,54 s | 0,012% | 79,1% | 76,8 MiB |
| **PivotPHP 5.0** | **185,09 ± 0,82** | 0,4% | 657 ms | 2,38 s | 2,57 s | 0,014% | 79,8% | 77,3 MiB |
| Webman *(worker persistente — ranking separado)* | 523,26 ± 3,39 | 0,5% | 8 ms | 0,28 s | 0,42 s | 0,000% | 17,7% | 29,7 MiB |

Comparação pareada do PivotPHP (diferença de req/s dentro de cada repetição; t crítico 95%, gl=4: ±2,776):

| Contra | Por repetição | Média | t | Significativo? |
|---|---|---:|---:|---|
| Slim 4 | +0,6% −1,7% −0,3% −1,7% +0,2% | −0,6% | −1,22 | não |
| Mezzio | −0,8% −1,0% −1,4% −1,2% −0,3% | −0,9% | −5,01 | **sim** |
| Symfony | −0,2% −0,6% −0,9% −1,2% +0,1% | −0,6% | −2,57 | não (no limite) |

## Leitura

- **O ambiente remoto eliminou o ruído da prévia.** A variação entre repetições caiu de 1,6–3,1% (prévia
  local) para 0,4–0,9%, e os intervalos de confiança ficaram até 8 vezes mais estreitos.
- **Grupo PHP-FPM: os quatro entregam praticamente o mesmo throughput** (185–187 req/s), saturando o limite de
  1 vCPU com ~80% de CPU e ~77 MiB.
- **PivotPHP: 0,6–0,9% abaixo dos demais**, com p50 1–3% maior. Contra o Mezzio a diferença é
  estatisticamente significativa (−0,9%, consistente nas 5 repetições); contra Slim e Symfony, não. Como o app
  está saturado no limite de CPU, isso indica um **custo de CPU por requisição ligeiramente maior** no PivotPHP —
  real, mas pequeno. A diferença de ~2% vista na prévia local era, em sua maior parte, ruído do ambiente.
- **Webman** (worker persistente): ~2,8× o throughput com 18% de CPU — outra arquitetura, ranking separado.
- O cenário satura os frameworks PHP-FPM (p95 > 2,3 s, acima dos thresholds do script): mede capacidade sob fila,
  não latência dentro do SLO.

## Limitações conhecidas

- **Verificação de CPU externa sem efeito nesta rodada.** A leitura do `/proc/stat` da VM falhava (aspas
  perdidas no comando enviado por SSH) e a conta misturava unidades, então `vm_cpu_total_perc` ficou vazio e a
  CPU externa saiu sempre 0 em `host-cpu-usage.csv`. Corrigido depois da rodada (commit `9fe3db7`). A ausência de
  interferência relevante é sustentada indiretamente pela variação muito baixa entre repetições (CV ≤ 0,9%).
- **Smoke de produtos não é comparável entre frameworks.** `smoke.js` chama `/api/products*` e `/api/dashboard`,
  implementados só na variante PivotPHP (SPEC-109). Slim, Mezzio e Symfony respondem **200 com "Fatal error" do
  PHP** a qualquer rota inexistente (sem tratamento de exceção e com `display_errors` ativo), então esses checks
  passam por engano; o Webman responde 404 e o smoke dele reprova (57% dos checks). **Não afeta o `vote-load`**:
  ele só usa rotas de votação, o check do voto exige `201`, e a taxa de checks aprovados é a mesma nos 5
  frameworks (99,98–99,99%).
- A rotação é cíclica: cada framework tem sempre o mesmo antecessor. Cada stack é derrubada com
  `docker compose down -v` antes da próxima subir.
