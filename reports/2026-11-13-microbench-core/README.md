# Microbench do core — 5.2.0 × otimizações da SPEC-116

> Mede a CPU por requisição da variante PivotPHP no PHP-FPM real, para verificar o ganho das otimizações de
> bootstrap da SPEC-116 antes da release do core.

- **Data**: 2026-11-13; run `20261113_185430_microbench-core`; commit da suíte `9534d0f`.
- **Ambiente**: Docker 29.7.2 no WSL2 de um i7-13700H; container Nginx + PHP-FPM 8.3.36 com OPcache, 1 vCPU,
  512 MB; carga do k6 2.3.0 (8 VUs) em outro container da mesma rede.
- **Execução**: `./scripts/run-microbench-core.sh --core-src ../pivotphp-core --rounds 5 --requests 20000
  --with-frameworks` — 5 rodadas com a ordem das variantes rotacionada; aquecimento de 4.000 requisições antes
  de cada medição.
- **Medida**: contador `usage_usec` do cgroup do container antes e depois de 20.000 requisições a `/api/bare`,
  dividido pelas requisições. Inclui Nginx e PHP-FPM; não depende do gerador de carga.
- **Dados brutos**: [`cpu.csv`](cpu.csv) e [`metadata.json`](metadata.json).

- **released**: `pivotphp/core` v5.2.0
- **candidate**: checkout local do core em `d37d097+alterações locais`

CPU do container (Nginx + PHP-FPM) dividida pelas requisições, em µs (média ± IC95 entre rodadas).

| Variante | µs/req | Acima do piso | vs. released (pareado) |
|---|---:|---:|---:|
| core instalado (released) | 563,1 ± 19,5 | 303,9 | — |
| core local (candidate) | 527,4 ± 9,8 | 268,3 | -6,3% ± 1,5 |
| core local + lazyHandlers() | 516,6 ± 12,0 | 257,4 | -8,2% ± 1,4 |
| PHP puro (piso) | 259,1 ± 11,3 | — | — |
| Slim 4 | 512,4 ± 5,6 | 253,2 | — |
| Symfony | 487,9 ± 14,5 | 228,7 | — |

## Leitura

- **Core local sem mudar a aplicação: −6,3% de CPU por requisição** (563 → 527 µs), consistente nas 5 rodadas.
  Descontado o piso do Nginx + PHP-FPM, o custo do framework cai de ~304 µs para ~268 µs (−12%).
- **Com `Application::lazyHandlers()`: −8,2%** (517 µs; custo do framework ~257 µs, −15%). A aplicação do
  benchmark tem 3 controllers; o ganho dessa opção cresce com o número de controllers.
- **Em relação aos outros frameworks**: o core 5.2.0 ficava ~10% acima do Slim 4 (512 µs). O core local fica
  ~3% acima, e com `lazyHandlers()` ~1% acima — dentro da margem de erro. O Symfony (488 µs) segue à frente:
  ~8% e ~6%, respectivamente.
- **De onde vem o ganho**: menos arquivos carregados por requisição — 71 no core 5.2.0, 51 com o core local e
  48 com `lazyHandlers()` (contagem de `get_included_files()` em `/api/bare`).

## Limitações

- Host em uso (WSL2/Docker Desktop) e gerador no mesmo host: a primeira rodada saiu 3–6% acima das demais em
  quase todas as variantes. A comparação pareada por rodada absorve essa deriva; os valores absolutos, não.
- Mede só `/api/bare` (sem banco, sem middlewares de rota). Não substitui a rodada HTTP comparativa.
- O core local é um checkout com alterações não publicadas (`d37d097` + diff da SPEC-116), não uma versão
  de release.
