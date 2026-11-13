#!/usr/bin/env python3
"""
scripts/report-microbench-core.py
Relatório do microbench do core (run-microbench-core.sh): CPU por requisição de cada variante
(média ± IC95 entre rodadas), custo acima do piso e diferença pareada contra a versão instalada.

    python3 scripts/report-microbench-core.py results/<run_id> > report.md
"""

import csv
import json
import math
import statistics as st
import sys
from collections import defaultdict
from pathlib import Path

# t de Student bicaudal, 95%, por graus de liberdade.
T95 = {1: 12.706, 2: 4.303, 3: 3.182, 4: 2.776, 5: 2.571, 6: 2.447, 7: 2.365, 8: 2.306, 9: 2.262}
LABELS = {
    "released": "core instalado (released)",
    "candidate": "core local (candidate)",
    "candidate-lazy": "core local + lazyHandlers()",
    "floor": "PHP puro (piso)",
    "slim": "Slim 4",
    "symfony": "Symfony",
}


def ci95(values: list[float]) -> float:
    n = len(values)
    if n < 2:
        return 0.0
    return T95.get(n - 1, 1.96) * st.stdev(values) / math.sqrt(n)


def fmt(x: float, d: int = 1) -> str:
    return f"{x:,.{d}f}".replace(",", "X").replace(".", ",").replace("X", ".")


def main() -> None:
    if len(sys.argv) != 2:
        raise SystemExit("uso: report-microbench-core.py <diretório da rodada>")
    run = Path(sys.argv[1])
    meta = json.loads((run / "metadata.json").read_text(encoding="utf-8"))

    # data[variant][round] = µs de CPU por requisição
    data: dict[str, dict[int, float]] = defaultdict(dict)
    with open(run / "cpu.csv", newline="") as fh:
        for row in csv.DictReader(fh):
            data[row["variant"]][int(row["round"])] = int(row["cpu_usec"]) / int(row["requests"])

    variants = [v for v in LABELS if v in data]
    floor = st.mean(data["floor"].values()) if "floor" in data else None

    out = ["# Microbench do core — CPU por requisição", ""]
    out.append(f"Rodada `{meta['run_id']}`. PHP {meta['php']} (Nginx + PHP-FPM, OPcache), 1 vCPU, "
               f"rota `{meta['route']}`, {meta['rounds']} rodadas × {meta['requests']} requisições "
               f"({meta['vus']} VUs).")
    out.append("")
    out.append(f"- **released**: `pivotphp/core` {meta['core_released']}")
    if meta.get("core_candidate_commit"):
        out.append(f"- **candidate**: checkout local do core em `{meta['core_candidate_commit']}`")
    out += ["", "CPU do container (Nginx + PHP-FPM) dividida pelas requisições, em µs "
            "(média ± IC95 entre rodadas).", "",
            "| Variante | µs/req | Acima do piso | vs. released (pareado) |", "|---|---:|---:|---:|"]

    for v in variants:
        xs = [data[v][r] for r in sorted(data[v])]
        above = fmt(st.mean(xs) - floor) if floor is not None and v != "floor" else "—"
        if not v.startswith("candidate") or "released" not in data:
            versus = "—"
        else:
            rounds = sorted(set(data[v]) & set(data["released"]))
            diffs = [100 * (data[v][r] - data["released"][r]) / data["released"][r] for r in rounds]
            versus = f"{st.mean(diffs):+.1f}% ± {fmt(ci95(diffs))}".replace(".", ",")
        out.append(f"| {LABELS[v]} | {fmt(st.mean(xs))} ± {fmt(ci95(xs))} | {above} | {versus} |")

    print("\n".join(out))


if __name__ == "__main__":
    main()
