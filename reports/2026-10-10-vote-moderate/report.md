# Relatório de Benchmark — VOTE-MODERATE (SPEC-106)

- **Data da Execução**: `desconhecido`
- **Cenário**: `vote-moderate`
- **Host**: Desconhecido (— núcleos), — MB RAM, Linux
- **Limites de Recursos (App)**: 1.0 vCPU / 512 MB RAM (estritamente limitados)
- **Banco / Cache**: PostgreSQL 17 (2.0 vCPU / 2GB RAM) + Redis 7 (0.5 vCPU / 256MB RAM)

---

## 1. Ranking do Grupo PHP-FPM (Nginx + PHP-FPM)

> Frameworks de arquitetura idêntica em contêineres e pools equivalentes.

| Posição | Framework | Throughput (RPS) | Latência p50 | Latência p95 | Latência p99 | Taxa de Erro | Status |
|:---:|:---|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | **Pivotphp** | 229.72098 | 99.36ms | 186.8ms | — | 0.03% | Concluído |
| 2 | Mezzio | 229.05024 | 99.61ms | 188ms | — | 0.02% | Concluído |
| 3 | Slim | 227.778412 | 99.54ms | 193.7ms | — | 0.04% | Concluído |
| 4 | Symfony | 220.221026 | 101.08ms | 202.54ms | — | 0.04% | Concluído |

## 2. Comparativo Geral de Arquiteturas de Execução

> Inclui runtimes assíncronos/event-driven (Webman/Workerman) para referência arquitetural.

| Posição | Framework | Runtime | Throughput (RPS) | Latência p50 | Latência p95 | Taxa de Erro | Status |
|:---:|:---|:---|:---:|:---:|:---:|:---:|:---:|
| 1 | Webman | CLI Async / Workerman | 1159.451951 | 20.11ms | 48.08ms | 0.00% | Concluído |
| 2 | **Pivotphp** | Nginx + PHP-FPM 8.3 | 229.72098 | 99.36ms | 186.8ms | 0.03% | Concluído |
| 3 | Mezzio | Nginx + PHP-FPM 8.3 | 229.05024 | 99.61ms | 188ms | 0.02% | Concluído |
| 4 | Slim | Nginx + PHP-FPM 8.3 | 227.778412 | 99.54ms | 193.7ms | 0.04% | Concluído |
| 5 | Symfony | Nginx + PHP-FPM 8.3 | 220.221026 | 101.08ms | 202.54ms | 0.04% | Concluído |

---

## 3. Metadados e Reprodutibilidade

- Arquivos brutos gravados em:
  - Metadados: `metadata.json`
  - Logs brutos de execução: `<framework>_vote-moderate.txt`
  - Uso de recursos durante o teste: `resource-usage.csv`

Relatório gerado automaticamente conforme a metodologia definida na SPEC-106.
