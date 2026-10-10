# Relatório de Benchmark — VOTE-LOAD (SPEC-106)

> **Origem dos dados:** relatório regerado pelo `scripts/generate-report.py` a partir dos logs brutos da
> rodada da SPEC-037 (2026-10-10), quando a variante PivotPHP usava **`pivotphp/core` ^4.0**. Não é uma
> medição do core 5.0 e a rodada não tem `metadata.json` — por isso data e host aparecem como desconhecidos.

- **Data da Execução**: `desconhecido`
- **Cenário**: `vote-load`
- **Host**: Desconhecido (— núcleos), — MB RAM, Linux
- **Limites de Recursos (App)**: 1.0 vCPU / 512 MB RAM (estritamente limitados)
- **Banco / Cache**: PostgreSQL 17 (2.0 vCPU / 2GB RAM) + Redis 7 (0.5 vCPU / 256MB RAM)

---

## 1. Ranking do Grupo PHP-FPM (Nginx + PHP-FPM)

> Frameworks de arquitetura idêntica em contêineres e pools equivalentes.

| Posição | Framework | Throughput (RPS) | Latência p50 | Latência p95 | Latência p99 | Taxa de Erro | Status |
|:---:|:---|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | Symfony | 167.640674 | 724.91ms | 2.77s | — | 0.02% | Concluído |
| 2 | Slim | 166.002566 | 690.57ms | 2.91s | — | 0.02% | Concluído |
| 3 | **Pivotphp** | 164.969602 | 666.54ms | 2.97s | — | 0.01% | Concluído |
| 4 | Mezzio | 162.563498 | 694.96ms | 3.05s | — | 0.01% | Concluído |

## 2. Comparativo Geral de Arquiteturas de Execução

> Inclui runtimes assíncronos/event-driven (Webman/Workerman) para referência arquitetural.

| Posição | Framework | Runtime | Throughput (RPS) | Latência p50 | Latência p95 | Taxa de Erro | Status |
|:---:|:---|:---|:---:|:---:|:---:|:---:|:---:|
| 1 | Webman | CLI Async / Workerman | 493.618849 | 10.37ms | 441.64ms | 0.00% | Concluído |
| 2 | Symfony | Nginx + PHP-FPM 8.3 | 167.640674 | 724.91ms | 2.77s | 0.02% | Concluído |
| 3 | Slim | Nginx + PHP-FPM 8.3 | 166.002566 | 690.57ms | 2.91s | 0.02% | Concluído |
| 4 | **Pivotphp** | Nginx + PHP-FPM 8.3 | 164.969602 | 666.54ms | 2.97s | 0.01% | Concluído |
| 5 | Mezzio | Nginx + PHP-FPM 8.3 | 162.563498 | 694.96ms | 3.05s | 0.01% | Concluído |

---

## 3. Metadados e Reprodutibilidade

- Arquivos brutos gravados em:
  - Metadados: `metadata.json`
  - Logs brutos de execução: `<framework>_vote-load.txt`
  - Uso de recursos durante o teste: `resource-usage.csv`

Relatório gerado automaticamente conforme a metodologia definida na SPEC-106.
