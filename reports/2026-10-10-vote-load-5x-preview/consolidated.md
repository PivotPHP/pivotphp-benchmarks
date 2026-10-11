# Consolidação — `vote-load` (5 repetições)

Repetições: `rep1`, `rep2`, `rep3`, `rep4`, `rep5`

## Throughput (req/s) por repetição

| Framework | Rep. 1 | Rep. 2 | Rep. 3 | Rep. 4 | Rep. 5 | Média ± IC95 | CV |
|---|---:|---:|---:|---:|---:|---:|---:|
| pivotphp | 176,45 | 172,40 | 180,89 | 179,11 | 167,18 | 175,21 ± 6,84 | 3,1% |
| slim | 176,25 | 182,72 | 179,79 | 181,04 | 175,92 | 179,14 ± 3,70 | 1,7% |
| mezzio | 176,84 | 182,19 | 182,35 | 176,08 | 176,54 | 178,80 ± 3,95 | 1,8% |
| symfony | 176,07 | 181,30 | 182,30 | 176,13 | 178,93 | 178,95 ± 3,57 | 1,6% |
| webman | 488,75 | 518,98 | 516,54 | 519,61 | 515,35 | 511,85 ± 16,18 | 2,5% |

## Médias

| Framework | req/s | p50 | p95 | p99 | Erros | CPU app | RSS máx. |
|---|---:|---:|---:|---:|---:|---:|---:|
| pivotphp | 175,21 | 698 ms | 2.670 ms | 2.934 ms | 0,018% | 80,4% | 78,7 MiB |
| slim | 179,14 | 675 ms | 2.594 ms | 2.848 ms | 0,012% | 81,5% | 77,4 MiB |
| mezzio | 178,80 | 672 ms | 2.600 ms | 2.930 ms | 0,016% | 80,3% | 78,4 MiB |
| symfony | 178,95 | 675 ms | 2.614 ms | 2.968 ms | 0,016% | 80,4% | 78,1 MiB |
| webman | 511,85 | 7 ms | 346 ms | 548 ms | 0,002% | 22,7% | 31,8 MiB |

## Ranking PHP-FPM (req/s, IC95)

1. **slim** — 179,14 [175,45 – 182,84]
2. **symfony** — 178,95 [175,38 – 182,51]
3. **mezzio** — 178,80 [174,85 – 182,75]
4. **pivotphp** — 175,21 [168,37 – 182,04]

## Comparação pareada do PivotPHP (mesma repetição)

t crítico (95%, gl=4): ±2.776

| Contra | Diferença por repetição | Média | t | Significativo? |
|---|---|---:|---:|---|
| slim | +0.1% -5.6% +0.6% -1.1% -5.0% | -2.2% | -1.68 | não (empate) |
| mezzio | -0.2% -5.4% -0.8% +1.7% -5.3% | -2.0% | -1.40 | não (empate) |
| symfony | +0.2% -4.9% -0.8% +1.7% -6.6% | -2.1% | -1.32 | não (empate) |
