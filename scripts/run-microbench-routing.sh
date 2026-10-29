#!/usr/bin/env bash
#
# scripts/run-microbench-routing.sh
# Microbench de roteamento: compara versões do pivotphp/core-routing sem rede nem banco.
#
# Cada medição roda em um container novo (PHP CLI, 1 vCPU). As versões são alternadas a cada
# rodada (A B, B A, ...) para que a deriva do host afete as duas igualmente. Ao final, gera a
# comparação com scripts/compare-microbench-routing.py.
#
# Uso:
#   ./scripts/run-microbench-routing.sh [--rounds N] [--routes "18 100 500"] [--samples N] [--iterations N]
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

VERSIONS=("3.0.0" "3.1.0")
ROUNDS=5
ROUTES="18 100 500"
SAMPLES=30
ITERATIONS=2000
IMAGE="pivotphp-microbench-routing:local"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --rounds) ROUNDS="$2"; shift 2 ;;
        --routes) ROUTES="$2"; shift 2 ;;
        --samples) SAMPLES="$2"; shift 2 ;;
        --iterations) ITERATIONS="$2"; shift 2 ;;
        *) echo "Opção desconhecida: $1" >&2; exit 2 ;;
    esac
done

RUN_ID="$(date +%Y%m%d_%H%M%S)_microbench-routing"
RESULTS_DIR="$ROOT_DIR/results/${RUN_ID}"
mkdir -p "$RESULTS_DIR"

echo "=========================================================="
echo "  PivotPHP Benchmarks — Microbench de roteamento"
echo "  Run ID:    $RUN_ID"
echo "  Versões:   ${VERSIONS[*]}"
echo "  Rotas:     $ROUTES"
echo "  Rodadas:   $ROUNDS (amostras: $SAMPLES, iterações: $ITERATIONS)"
echo "=========================================================="

echo ">> Construindo imagem..."
docker build -q -t "$IMAGE" microbench/routing >/dev/null

cat > "$RESULTS_DIR/metadata.json" <<EOF
{
  "run_id": "$RUN_ID",
  "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo desconhecido)",
  "host_cpu": "$(grep -m1 'model name' /proc/cpuinfo 2>/dev/null | cut -d: -f2 | sed 's/^[ \t]*//')",
  "docker": "$(docker --version)",
  "container_cpus": 1.0,
  "versions": ["${VERSIONS[0]}", "${VERSIONS[1]}"],
  "routes": "$ROUTES",
  "rounds": $ROUNDS,
  "samples": $SAMPLES,
  "iterations": $ITERATIONS
}
EOF

for ((round = 1; round <= ROUNDS; round++)); do
    order=("${VERSIONS[@]}")
    if ((round % 2 == 0)); then
        order=("${VERSIONS[1]}" "${VERSIONS[0]}")
    fi

    for routes in $ROUTES; do
        for version in "${order[@]}"; do
            out="$RESULTS_DIR/routes-${routes}_v${version}_r${round}.json"
            echo ">> Rodada $round/$ROUNDS — $routes rotas — core-routing $version"
            docker run --rm --cpus=1 --memory=512m "$IMAGE" \
                --autoload="versions/${version}/vendor/autoload.php" \
                --routes="$routes" --samples="$SAMPLES" --iterations="$ITERATIONS" > "$out"
        done
    done
done

python3 scripts/compare-microbench-routing.py "$RESULTS_DIR" > "$RESULTS_DIR/report.md"
echo ""
echo ">> Relatório: $RESULTS_DIR/report.md"
