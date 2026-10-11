#!/usr/bin/env python3
"""
scripts/consolidate-runs.py
Consolida várias repetições de um cenário (SPEC-106): média, desvio, IC95 (t de Student),
comparação pareada por repetição e os dois rankings (PHP-FPM e arquiteturas).

Lê o resumo do k6 a partir do log de texto (`<fw>_<cenario>.txt`) e o uso de recursos de
`resource-usage.csv`. Uso:

    python3 scripts/consolidate-runs.py --scenario vote-load DIR1 DIR2 ... > report.md
"""

import argparse
import csv
import math
import re
import statistics as st
from pathlib import Path

FRAMEWORKS = ["pivotphp", "slim", "mezzio", "symfony", "webman"]
PHP_FPM = ["pivotphp", "slim", "mezzio", "symfony"]
# t de Student bicaudal, 95%, por graus de liberdade.
T95 = {1: 12.706, 2: 4.303, 3: 3.182, 4: 2.776, 5: 2.571, 6: 2.447, 7: 2.365, 8: 2.306, 9: 2.262}


def to_ms(value: str) -> float:
    m = re.match(r"([\d.]+)(µs|ms|s)$", value)
    if not m:
        raise ValueError(f"duração inesperada: {value}")
    number, unit = float(m.group(1)), m.group(2)
    return {"µs": number / 1000, "ms": number, "s": number * 1000}[unit]


def parse_run(run: Path, fw: str, scenario: str) -> dict:
    text = (run / f"{fw}_{scenario}.txt").read_text(encoding="utf-8")
    rps = float(re.search(r"http_reqs\.+: \d+\s+([\d.]+)/s", text).group(1))
    err = float(re.search(r"http_req_failed\.+: ([\d.]+)%", text).group(1))
    dur = re.search(r"^\s+http_req_duration\.+:.*med=(\S+).*p\(95\)=(\S+)\s+p\(99\)=(\S+)", text, re.M)

    cpu, mem = [], []
    with open(run / "resource-usage.csv", newline="") as fh:
        for row in csv.DictReader(fh):
            if row["container"] != f"{fw}-app":
                continue
            c = float(row["cpu_perc"].rstrip("%"))
            if c > 10:  # só amostras sob carga
                cpu.append(c)
            mm = re.match(r"([\d.]+)(KiB|MiB|GiB)", row["mem_usage"])
            mem.append(float(mm.group(1)) * {"KiB": 1 / 1024, "MiB": 1, "GiB": 1024}[mm.group(2)])

    return {
        "rps": rps,
        "err": err,
        "p50": to_ms(dur.group(1)),
        "p95": to_ms(dur.group(2)),
        "p99": to_ms(dur.group(3)),
        "cpu": st.mean(cpu) if cpu else 0.0,
        "mem": max(mem) if mem else 0.0,
    }


def ci95(values: list[float]) -> float:
    n = len(values)
    return T95[n - 1] * st.stdev(values) / math.sqrt(n)


def fmt(x: float, d: int = 2) -> str:
    return f"{x:,.{d}f}".replace(",", "X").replace(".", ",").replace("X", ".")


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--scenario", default="vote-load")
    ap.add_argument("runs", nargs="+", type=Path)
    args = ap.parse_args()

    n = len(args.runs)
    if n < 2:
        raise SystemExit("são necessárias pelo menos 2 repetições")
    data = {fw: [parse_run(r, fw, args.scenario) for r in args.runs] for fw in FRAMEWORKS}

    def series(fw: str, key: str) -> list[float]:
        return [d[key] for d in data[fw]]

    out = [f"# Consolidação — `{args.scenario}` ({n} repetições)", ""]
    out.append("Repetições: " + ", ".join(f"`{r.name}`" for r in args.runs))
    out += ["", "## Throughput (req/s) por repetição", ""]
    out.append("| Framework | " + " | ".join(f"Rep. {i}" for i in range(1, n + 1)) + " | Média ± IC95 | CV |")
    out.append("|---|" + "---:|" * (n + 2))
    for fw in FRAMEWORKS:
        xs = series(fw, "rps")
        m = st.mean(xs)
        out.append(
            f"| {fw} | " + " | ".join(fmt(x) for x in xs)
            + f" | {fmt(m)} ± {fmt(ci95(xs))} | {fmt(100 * st.stdev(xs) / m, 1)}% |"
        )

    out += ["", "## Médias", "", "| Framework | req/s | p50 | p95 | p99 | Erros | CPU app | RSS máx. |",
            "|---|---:|---:|---:|---:|---:|---:|---:|"]
    for fw in FRAMEWORKS:
        g = {k: st.mean(series(fw, k)) for k in ("rps", "p50", "p95", "p99", "err", "cpu", "mem")}
        out.append(
            f"| {fw} | {fmt(g['rps'])} | {fmt(g['p50'], 0)} ms | {fmt(g['p95'], 0)} ms | {fmt(g['p99'], 0)} ms"
            f" | {fmt(g['err'], 3)}% | {fmt(g['cpu'], 1)}% | {fmt(g['mem'], 1)} MiB |"
        )

    out += ["", "## Ranking PHP-FPM (req/s, IC95)", ""]
    for i, fw in enumerate(sorted(PHP_FPM, key=lambda f: -st.mean(series(f, "rps"))), 1):
        xs = series(fw, "rps")
        m, c = st.mean(xs), ci95(xs)
        out.append(f"{i}. **{fw}** — {fmt(m)} [{fmt(m - c)} – {fmt(m + c)}]")

    out += ["", "## Comparação pareada do PivotPHP (mesma repetição)", "",
            f"t crítico (95%, gl={n - 1}): ±{T95[n - 1]}", "",
            "| Contra | Diferença por repetição | Média | t | Significativo? |", "|---|---|---:|---:|---|"]
    base = series("pivotphp", "rps")
    for fw in [f for f in PHP_FPM if f != "pivotphp"]:
        other = series(fw, "rps")
        diffs = [100 * (a - b) / b for a, b in zip(base, other)]
        sd = st.stdev(diffs)
        t = st.mean(diffs) / (sd / math.sqrt(n)) if sd else float("inf")
        sig = "sim" if abs(t) > T95[n - 1] else "não (empate)"
        out.append(
            f"| {fw} | " + " ".join(f"{d:+.1f}%" for d in diffs)
            + f" | {st.mean(diffs):+.1f}% | {t:+.2f} | {sig} |"
        )

    print("\n".join(out))


if __name__ == "__main__":
    main()
