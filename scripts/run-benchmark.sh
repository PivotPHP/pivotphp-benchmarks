#!/bin/bash
#
# Orquestrador do benchmark: sobe cada framework (isolado), roda os cenários k6,
# coleta os resultados e derruba a stack.
#
# Uso:
#   ./scripts/run-benchmark.sh [framework] [cenario]
#   ./scripts/run-benchmark.sh                # todos os frameworks, vote-load
#   ./scripts/run-benchmark.sh slim            # só o slim
#   ./scripts/run-benchmark.sh pivotphp spike  # pivotphp, cenário spike

set -euo pipefail

FRAMEWORKS=("pivotphp" "slim" "mezzio" "symfony" "webman")
SCENARIO="${2:-vote-load}"

if [[ $# -ge 1 && "${1:-}" != "" && " ${FRAMEWORKS[*]} " == *" ${1} "* ]]; then
    FRAMEWORKS=("$1")
fi

RESULTS_DIR="./results/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$RESULTS_DIR"

echo "== Benchmark ($SCENARIO) — $(date) =="
echo "Resultados em: $RESULTS_DIR"
echo ""

for fw in "${FRAMEWORKS[@]}"; do
    echo "=== $fw ==="
    (
        cd "$fw"

        docker compose up -d --build postgres redis app >/dev/null 2>&1
        sleep 10

        # Smoke (validação) + cenário principal
        docker compose --profile loadtest run --rm k6 run smoke.js \
            > "$RESULTS_DIR/${fw}_smoke.txt" 2>&1 || true

        docker compose --profile loadtest run --rm k6 run "$SCENARIO.js" \
            > "$RESULTS_DIR/${fw}_${SCENARIO}.txt" 2>&1 || true

        docker compose down >/dev/null 2>&1
    )
    echo ""
done

echo "== Concluído. Sumário: =="
echo ""

# Extrai métricas-chave de cada resultado.
printf "%-12s | %-12s | %-8s | %-8s | %s\n" "framework" "req/s" "p95(vote)" "erros" "arquivo"
for fw in "${FRAMEWORKS[@]}"; do
    f="$RESULTS_DIR/${fw}_${SCENARIO}.txt"
    if [[ -f "$f" ]]; then
        reqs=$(grep -E '^\s+http_reqs' "$f" | awk '{print $3}' | head -1)
        p95=$(grep -E '^\s+vote_duration_ms' "$f" | grep -oE 'p\(95\)=[0-9.]+[a-zµ]*' | head -1)
        failed=$(grep -E '^\s+http_req_failed' "$f" | awk '{print $2}' | head -1)
        printf "%-12s | %-12s | %-8s | %-8s | %s\n" "$fw" "$reqs" "${p95:-—}" "$failed" "${fw}_${SCENARIO}.txt"
    else
        printf "%-12s | %-12s | %-8s | %-8s | %s\n" "$fw" "—" "—" "—" "(sem resultado)"
    fi
done
