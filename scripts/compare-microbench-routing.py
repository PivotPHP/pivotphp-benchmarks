#!/usr/bin/env python3
"""
scripts/compare-microbench-routing.py
Compara as versões do pivotphp/core-routing medidas por run-microbench-routing.sh.

Para cada quantidade de rotas e cenário: média das medianas por rodada (µs/op), IC95 entre
rodadas e o fator de ganho pareado (versão antiga ÷ nova na mesma rodada). Uso:

    python3 scripts/compare-microbench-routing.py results/<run_id> > report.md
"""

import json
import math
import re
import statistics as st
import sys
from collections import defaultdict
from pathlib import Path

# t de Student bicaudal, 95%, por graus de liberdade.
T95 = {1: 12.706, 2: 4.303, 3: 3.182, 4: 2.776, 5: 2.571, 6: 2.447, 7: 2.365, 8: 2.306, 9: 2.262}
SCENARIOS = ["static_hit", "dynamic_hit", "head_fallback", "options_auto", "method_not_allowed", "not_found",
             "register"]
FILE_RE = re.compile(r"routes-(\d+)_v([\d.]+)_r(\d+)\.json$")


def ci95(values: list[float]) -> float:
    n = len(values)
    if n < 2:
        return 0.0
    return T95.get(n - 1, 1.96) * st.stdev(values) / math.sqrt(n)


def fmt(x: float, d: int = 2) -> str:
    return f"{x:,.{d}f}".replace(",", "X").replace(".", ",").replace("X", ".")


def main() -> None:
    if len(sys.argv) != 2:
        raise SystemExit("uso: compare-microbench-routing.py <diretório da rodada>")
    run = Path(sys.argv[1])

    # data[routes][version][round] = resultado do bench.php
    data: dict[int, dict[str, dict[int, dict]]] = defaultdict(lambda: defaultdict(dict))
    meta = {}
    for path in sorted(run.glob("routes-*.json")):
        m = FILE_RE.search(path.name)
        if not m:
            continue
        result = json.loads(path.read_text(encoding="utf-8"))
        data[int(m.group(1))][m.group(2)][int(m.group(3))] = result
        meta[m.group(2)] = result

    versions = sorted(meta, key=lambda v: tuple(int(p) for p in v.split(".")))
    if len(versions) != 2:
        raise SystemExit(f"esperadas 2 versões, encontradas: {versions}")
    old, new = versions

    out = [f"# Microbench de roteamento — core-routing {old} × {new}", ""]
    out.append(f"Rodada `{run.name}`. PHP {meta[new]['php']}, OPcache "
               f"{'ligado' if meta[new]['opcache'] else 'desligado'}, JIT `{meta[new]['jit'] or 'off'}`, 1 vCPU.")
    out.append("")
    for v in versions:
        out.append(f"- **{v}**: API `{meta[v]['api']}`")
    out += ["", "Valores em µs por operação (média das medianas por rodada ± IC95). "
            "**Ganho** = tempo da versão antiga ÷ tempo da nova, pareado por rodada.", ""]

    for routes in sorted(data):
        rounds = sorted(set(data[routes][old]) & set(data[routes][new]))
        out += [f"## {routes} rotas ({len(rounds)} rodadas)", "",
                f"| Cenário | {old} | {new} | Ganho |", "|---|---:|---:|---:|"]
        for sc in SCENARIOS:
            a = [data[routes][old][r]["results"][sc]["median_ns"] / 1000 for r in rounds]
            b = [data[routes][new][r]["results"][sc]["median_ns"] / 1000 for r in rounds]
            ratios = [x / y for x, y in zip(a, b)]
            out.append(
                f"| {sc} | {fmt(st.mean(a))} ± {fmt(ci95(a))} | {fmt(st.mean(b))} ± {fmt(ci95(b))} "
                f"| {fmt(st.mean(ratios), 1)}× ± {fmt(ci95(ratios), 1)} |"
            )
        out.append("")

    print("\n".join(out))


if __name__ == "__main__":
    main()
