# Microbench de roteamento — core-routing 3.0.0 × 3.1.0

> Mede o roteador isolado (sem HTTP, sem banco) para verificar o ganho da SPEC-114 (core-routing 3.1.0,
> usado pelo core 5.2) em relação à 3.0.0 (core 5.0).

- **Data**: 2026-10-29; run `20261029_131728_microbench-routing`; commit da suíte `1fb1be0`.
- **Ambiente**: Docker 29.7.2 no WSL2 de um i7-13700H; container PHP 8.3.36 CLI, OPcache ligado, JIT desligado,
  1 vCPU, 512 MB. Um container novo por medição.
- **Execução**: `./scripts/run-microbench-routing.sh --rounds 5` — 5 rodadas com as versões alternadas, 30 amostras
  de 2.000 operações por cenário (registro: 20 operações por amostra).
- **Dados brutos**: os `routes-<N>_v<versão>_r<rodada>.json` e o `metadata.json` desta pasta.

Valores em µs por operação (média das medianas por rodada ± IC95). **Ganho** = tempo da versão antiga ÷ tempo da nova, pareado por rodada.

## 18 rotas (5 rodadas)

| Cenário | 3.0.0 | 3.1.0 | Ganho |
|---|---:|---:|---:|
| static_hit | 0,49 ± 0,01 | 0,23 ± 0,01 | 2,1× ± 0,1 |
| dynamic_hit | 1,29 ± 0,02 | 0,93 ± 0,02 | 1,4× ± 0,0 |
| head_fallback | 0,86 ± 0,02 | 0,48 ± 0,02 | 1,8× ± 0,0 |
| options_auto | 4,06 ± 0,11 | 1,25 ± 0,06 | 3,2× ± 0,1 |
| method_not_allowed | 4,08 ± 0,07 | 1,25 ± 0,06 | 3,3× ± 0,1 |
| not_found | 5,44 ± 0,07 | 1,02 ± 0,07 | 5,3× ± 0,4 |
| register | 18,43 ± 0,38 | 15,33 ± 0,76 | 1,2× ± 0,1 |

## 100 rotas (5 rodadas)

| Cenário | 3.0.0 | 3.1.0 | Ganho |
|---|---:|---:|---:|
| static_hit | 2,92 ± 0,08 | 0,23 ± 0,00 | 12,7× ± 0,3 |
| dynamic_hit | 6,72 ± 0,18 | 0,92 ± 0,02 | 7,3× ± 0,3 |
| head_fallback | 4,98 ± 0,09 | 0,47 ± 0,01 | 10,6× ± 0,3 |
| options_auto | 23,46 ± 0,38 | 1,24 ± 0,05 | 18,9× ± 0,5 |
| method_not_allowed | 23,42 ± 0,48 | 1,23 ± 0,04 | 19,0× ± 0,4 |
| not_found | 32,61 ± 0,98 | 1,01 ± 0,05 | 32,3× ± 1,1 |
| register | 124,70 ± 4,08 | 100,80 ± 4,37 | 1,2× ± 0,0 |

## 500 rotas (5 rodadas)

| Cenário | 3.0.0 | 3.1.0 | Ganho |
|---|---:|---:|---:|
| static_hit | 14,48 ± 0,41 | 0,23 ± 0,01 | 63,5× ± 2,8 |
| dynamic_hit | 32,09 ± 1,03 | 0,91 ± 0,02 | 35,4× ± 0,8 |
| head_fallback | 25,73 ± 0,31 | 0,47 ± 0,01 | 55,2× ± 1,6 |
| options_auto | 123,77 ± 2,10 | 1,25 ± 0,02 | 99,3× ± 1,8 |
| method_not_allowed | 122,84 ± 3,08 | 1,24 ± 0,03 | 99,1× ± 3,4 |
| not_found | 166,07 ± 1,78 | 1,01 ± 0,02 | 164,2× ± 2,6 |
| register | 608,30 ± 16,77 | 510,59 ± 6,12 | 1,2× ± 0,0 |


## Leitura

- **Resolução: ganho real e crescente com o número de rotas.** A 3.0.0 percorria a lista de rotas (custo
  linear); a 3.1.0 usa um índice e fica constante em ~0,2–1,3 µs de 18 a 500 rotas. Com 500 rotas o ganho vai
  de 35× (rota dinâmica) a 164× (404); com 18 rotas, de 1,4× a 5,3×.
- **Registro: ganho pequeno (~1,2×) e custo ainda linear.** Registrar 500 rotas leva ~511 µs na 3.1.0 (608 µs
  na 3.0.0). No PHP-FPM o registro acontece a cada requisição, então em aplicações grandes ele passa a ser o
  custo dominante do roteamento — maior que a própria resolução. A meta "< 50 µs para 500 rotas" registrada
  na SPEC-114 não é confirmada por esta medição.
- **Por que o `vote-load` não mostra o ganho.** A variante PivotPHP tem ~18 rotas: roteamento completo
  (registro + resolução) passa de ~19 µs para ~16 µs por requisição, contra ~5.500 µs de CPU por requisição no
  benchmark HTTP — uma diferença de ~0,05%, muito abaixo do IC95 daquela suíte (±0,4–0,8%).

## Limitações

- Mede o roteador, não o core: middlewares, PSR-7 e o despacho do handler ficam de fora.
- Host em uso (WSL2/Docker Desktop); a estabilidade vem da comparação pareada e alternada, não de um host
  dedicado. Os IC95 ficaram em até ~7% do valor (pior caso: 404 com 18 rotas, 1,02 ± 0,07 µs).
