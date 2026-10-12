# Consolidação — `vote-load` (5 repetições)

Repetições: `rep1`, `rep2`, `rep3`, `rep4`, `rep5`

## Throughput (req/s) por repetição

| Framework | Rep. 1 | Rep. 2 | Rep. 3 | Rep. 4 | Rep. 5 | Média ± IC95 | CV |
|---|---:|---:|---:|---:|---:|---:|---:|
| pivotphp | 185,30 | 185,32 | 185,44 | 183,91 | 185,47 | 185,09 ± 0,82 | 0,4% |
| slim | 184,18 | 188,60 | 185,96 | 187,16 | 185,16 | 186,21 ± 2,14 | 0,9% |
| mezzio | 186,89 | 187,21 | 187,98 | 186,20 | 185,98 | 186,85 ± 1,00 | 0,4% |
| symfony | 185,77 | 186,52 | 187,13 | 186,06 | 185,31 | 186,16 ± 0,87 | 0,4% |
| webman | 519,15 | 521,81 | 524,58 | 525,31 | 525,45 | 523,26 ± 3,39 | 0,5% |

## Médias

| Framework | req/s | p50 | p95 | p99 | Erros | CPU app | RSS máx. |
|---|---:|---:|---:|---:|---:|---:|---:|
| pivotphp | 185,09 | 657 ms | 2.380 ms | 2.572 ms | 0,014% | 79,8% | 77,3 MiB |
| slim | 186,21 | 646 ms | 2.362 ms | 2.568 ms | 0,012% | 79,7% | 76,2 MiB |
| mezzio | 186,85 | 640 ms | 2.340 ms | 2.528 ms | 0,014% | 79,6% | 77,0 MiB |
| symfony | 186,16 | 651 ms | 2.346 ms | 2.536 ms | 0,012% | 79,1% | 76,8 MiB |
| webman | 523,26 | 8 ms | 284 ms | 424 ms | 0,000% | 17,7% | 29,7 MiB |

## Ranking PHP-FPM (req/s, IC95)

1. **mezzio** — 186,85 [185,86 – 187,85]
2. **slim** — 186,21 [184,07 – 188,35]
3. **symfony** — 186,16 [185,29 – 187,03]
4. **pivotphp** — 185,09 [184,27 – 185,91]

## Comparação pareada do PivotPHP (mesma repetição)

t crítico (95%, gl=4): ±2.776

| Contra | Diferença por repetição | Média | t | Significativo? |
|---|---|---:|---:|---|
| slim | +0.6% -1.7% -0.3% -1.7% +0.2% | -0.6% | -1.22 | não (empate) |
| mezzio | -0.8% -1.0% -1.4% -1.2% -0.3% | -0.9% | -5.01 | sim |
| symfony | -0.2% -0.6% -0.9% -1.2% +0.1% | -0.6% | -2.57 | não (empate) |
