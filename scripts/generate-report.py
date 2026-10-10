#!/usr/bin/env python3
"""
scripts/generate-report.py
Gera report.md consolidado a partir dos resultados brutos do benchmark (SPEC-106).
Separa rankings em:
1. PHP-FPM x PHP-FPM (PivotPHP, Slim, Mezzio, Symfony)
2. Comparativo Geral de Arquiteturas (incluindo Webman - async)
"""

import sys
import os
import re
import json
from pathlib import Path

FPM_FRAMEWORKS = ["pivotphp", "slim", "mezzio", "symfony"]
ASYNC_FRAMEWORKS = ["webman"]

def parse_txt_output(filepath):
    """Extrai métricas do log de texto padrão do k6."""
    metrics = {
        "reqs_per_sec": "—",
        "total_reqs": "—",
        "p50": "—",
        "p95": "—",
        "p99": "—",
        "error_rate": "0.00%",
        "status": "Incompleto"
    }

    if not os.path.exists(filepath):
        return metrics

    content = Path(filepath).read_text(encoding="utf-8", errors="ignore")
    metrics["status"] = "Concluído"

    # http_reqs
    reqs_match = re.search(r'http_reqs\.*:\s+(\d+)\s+([\d.]+)/s', content)
    if reqs_match:
        metrics["total_reqs"] = reqs_match.group(1)
        metrics["reqs_per_sec"] = reqs_match.group(2)

    # http_req_failed
    failed_match = re.search(r'http_req_failed\.*:\s+([\d.]+)%', content)
    if failed_match:
        metrics["error_rate"] = f"{float(failed_match.group(1)):.2f}%"

    # vote_duration_ms or http_req_duration
    # Ex: p(95)=209.69ms
    dur_match = re.search(r'(?:vote_duration_ms|http_req_duration)\.*:\s+avg=[\d.]+ms\s+min=[\d.]+ms\s+med=([\d.]+[a-zµ]+).*?p\(95\)=([\d.]+[a-zµ]+)\s+p\(99\)=([\d.]+[a-zµ]+)', content)
    if dur_match:
        metrics["p50"] = dur_match.group(1)
        metrics["p95"] = dur_match.group(2)
        metrics["p99"] = dur_match.group(3)
    else:
        # Fallback individual regexes
        p95_match = re.search(r'p\(95\)=([\d.]+[a-zµ]*)', content)
        if p95_match:
            metrics["p95"] = p95_match.group(1)
        p90_match = re.search(r'p\(90\)=([\d.]+[a-zµ]*)', content)
        p50_match = re.search(r'med=([\d.]+[a-zµ]*)', content)
        if p50_match:
            metrics["p50"] = p50_match.group(1)

    return metrics

def parse_json_summary(filepath):
    """Extrai métricas do k6-summary.json se disponível."""
    if not os.path.exists(filepath):
        return None
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)
        metrics = data.get("metrics", {})
        http_reqs = metrics.get("http_reqs", {}).get("values", {})
        http_failed = metrics.get("http_req_failed", {}).get("values", {})
        duration = metrics.get("vote_duration_ms", {}).get("values", {}) or metrics.get("http_req_duration", {}).get("values", {})

        return {
            "reqs_per_sec": f"{http_reqs.get('rate', 0):.2f}" if "rate" in http_reqs else "—",
            "total_reqs": str(int(http_reqs.get("count", 0))),
            "p50": f"{duration.get('med', 0):.2f}ms" if "med" in duration else "—",
            "p95": f"{duration.get('p(95)', 0):.2f}ms" if "p(95)" in duration else "—",
            "p99": f"{duration.get('p(99)', 0):.2f}ms" if "p(99)" in duration else "—",
            "error_rate": f"{http_failed.get('rate', 0) * 100:.2f}%",
            "status": "Concluído"
        }
    except Exception:
        return None

def generate_report(results_dir):
    results_path = Path(results_dir)
    metadata_path = results_path / "metadata.json"
    metadata = {}
    if metadata_path.exists():
        try:
            metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
        except Exception:
            pass

    scenario = metadata.get("scenario")
    if not scenario:
        # Detectar a partir dos arquivos presentes
        for f in results_path.glob("*_*.txt"):
            if "_smoke.txt" in f.name:
                continue
            parts = f.name.replace(".txt", "").split("_", 1)
            if len(parts) == 2:
                scenario = parts[1]
                break
        if not scenario:
            scenario = "vote-load"
    timestamp = metadata.get("timestamp", "desconhecido")
    cpu_model = metadata.get("host", {}).get("cpu_model", "Desconhecido")
    cpu_cores = metadata.get("host", {}).get("cpu_cores", "—")
    ram_mb = metadata.get("host", {}).get("ram_total_mb", "—")
    os_name = metadata.get("host", {}).get("os", "Linux")

    all_frameworks = FPM_FRAMEWORKS + ASYNC_FRAMEWORKS
    results = {}

    for fw in all_frameworks:
        json_file = results_path / f"{fw}_{scenario}_summary.json"
        txt_file = results_path / f"{fw}_{scenario}.txt"

        data = parse_json_summary(json_file)
        if not data:
            data = parse_txt_output(txt_file)
        results[fw] = data

    # Ordenar ranking FPM por RPS decrescente
    def sort_key(fw):
        val = results[fw]["reqs_per_sec"]
        try:
            return float(val)
        except ValueError:
            return -1.0

    fpm_sorted = sorted(FPM_FRAMEWORKS, key=sort_key, reverse=True)
    all_sorted = sorted(all_frameworks, key=sort_key, reverse=True)

    lines = [
        f"# Relatório de Benchmark — {scenario.upper()} (SPEC-106)",
        "",
        f"- **Data da Execução**: `{timestamp}`",
        f"- **Cenário**: `{scenario}`",
        f"- **Host**: {cpu_model} ({cpu_cores} núcleos), {ram_mb} MB RAM, {os_name}",
        f"- **Limites de Recursos (App)**: 1.0 vCPU / 512 MB RAM (estritamente limitados)",
        f"- **Banco / Cache**: PostgreSQL 17 (2.0 vCPU / 2GB RAM) + Redis 7 (0.5 vCPU / 256MB RAM)",
        "",
        "---",
        "",
        "## 1. Ranking do Grupo PHP-FPM (Nginx + PHP-FPM)",
        "",
        "> Frameworks de arquitetura idêntica em contêineres e pools equivalentes.",
        "",
        "| Posição | Framework | Throughput (RPS) | Latência p50 | Latência p95 | Latência p99 | Taxa de Erro | Status |",
        "|:---:|:---|:---:|:---:|:---:|:---:|:---:|:---:|"
    ]

    for idx, fw in enumerate(fpm_sorted, 1):
        m = results[fw]
        name = f"**{fw.capitalize()}**" if fw == "pivotphp" else fw.capitalize()
        lines.append(
            f"| {idx} | {name} | {m['reqs_per_sec']} | {m['p50']} | {m['p95']} | {m['p99']} | {m['error_rate']} | {m['status']} |"
        )

    lines.extend([
        "",
        "## 2. Comparativo Geral de Arquiteturas de Execução",
        "",
        "> Inclui runtimes assíncronos/event-driven (Webman/Workerman) para referência arquitetural.",
        "",
        "| Posição | Framework | Runtime | Throughput (RPS) | Latência p50 | Latência p95 | Taxa de Erro | Status |",
        "|:---:|:---|:---|:---:|:---:|:---:|:---:|:---:|"
    ])

    for idx, fw in enumerate(all_sorted, 1):
        m = results[fw]
        runtime = "Nginx + PHP-FPM 8.3" if fw in FPM_FRAMEWORKS else "CLI Async / Workerman"
        name = f"**{fw.capitalize()}**" if fw == "pivotphp" else fw.capitalize()
        lines.append(
            f"| {idx} | {name} | {runtime} | {m['reqs_per_sec']} | {m['p50']} | {m['p95']} | {m['error_rate']} | {m['status']} |"
        )

    lines.extend([
        "",
        "---",
        "",
        "## 3. Metadados e Reprodutibilidade",
        "",
        "- Arquivos brutos gravados em:",
        f"  - Metadados: `metadata.json`",
        f"  - Logs brutos de execução: `<framework>_{scenario}.txt`",
        f"  - Uso de recursos durante o teste: `resource-usage.csv`",
        "",
        "Relatório gerado automaticamente conforme a metodologia definida na SPEC-106.",
        ""
    ])

    report_content = "\n".join(lines)
    (results_path / "report.md").write_text(report_content, encoding="utf-8")
    print(f"Relatório gerado em: {results_path / 'report.md'}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Uso: python3 scripts/generate-report.py <diretorio_resultados>")
        sys.exit(1)
    generate_report(sys.argv[1])
