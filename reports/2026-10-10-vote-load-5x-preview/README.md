# Prévia — `vote-load` × 5 repetições (core 5.0, host local)

> **Prévia, não é a rodada de referência.** Serve para orientar a SPEC-110 (execução remota). Não use estes
> números como ranking oficial.

- **Data**: 2026-10-10, 20:54–23:07 (horário de Brasília); commit da suíte `ed0636c`.
- **Versões**: `pivotphp/core` 5.0.0, `pivotphp/core-routing` 3.0.0, `pivotphp/http` 1.0.1.
- **Host**: Intel i7-13700H, WSL2 (5 núcleos visíveis), Docker; limites por stack: app 1 vCPU / 512 MB,
  PostgreSQL 2 vCPU / 2 GB, Redis 0,5 vCPU / 256 MB (validados com `scripts/validate-environment.sh`).
- **Execução**: `./scripts/run-benchmark.sh "" vote-load` repetido 5 vezes; em cada repetição os 5 frameworks
  rodam em sequência, sempre na mesma ordem (PivotPHP primeiro).
- **Consolidação**: [`consolidated.md`](consolidated.md), gerado por
  `python3 scripts/consolidate-runs.py --scenario vote-load rep1 rep2 rep3 rep4 rep5`.
- **Dados brutos**: `rep1/`…`rep5/` (logs do k6 e do smoke, `metadata.json`, `resource-usage.csv`).

## Leitura

- **Grupo PHP-FPM: empate técnico.** Os ICs95 se sobrepõem e a comparação pareada não é significativa
  (|t| ≤ 1,68 < 2,776). Os quatro saturam o limite de 1 vCPU (~80% de CPU), com ~220 req/s por CPU e ~78 MiB.
- **PivotPHP**: média −2% com variação maior (CV 3,1%) — empata em 3 repetições e cai ~5% em 2 (rep. 2 e 5).
  Não há base para afirmar vantagem nem desvantagem.
- **Webman** (worker persistente): ~2,9× o throughput com 23% de CPU — ranking separado.
- O cenário satura todos os PHP-FPM (p95 > 2,5 s, acima dos thresholds do script): mede capacidade sob
  fila, não latência dentro do SLO.

## Limitações conhecidas

- **Host em uso** durante a rodada (outros programas abertos); o monitor registrava só os containers, então a
  interferência não pode ser medida nem descartada.
- **k6 no mesmo host** que as aplicações, disputando CPU.
- **Ordem fixa**: o PivotPHP sempre roda primeiro na repetição (após validação, build e a troca de stack) —
  efeito de ordem não controlado.
- O resumo JSON do k6 não foi gravado (permissão no volume `/out`); as métricas vêm dos logs de texto.

Essas limitações são tratadas na SPEC-110 (APIs numa VM dedicada, k6 em outra máquina, rodízio de ordem,
aquecimento descartado e monitoramento da CPU do host).
