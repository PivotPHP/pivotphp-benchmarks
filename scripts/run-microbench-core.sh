#!/usr/bin/env bash
#
# scripts/run-microbench-core.sh
# Microbench do pivotphp/core no PHP-FPM real (SPEC-116): mede a CPU por requisição do container
# (Nginx + PHP-FPM, 1 vCPU) lendo o contador de CPU do cgroup antes e depois de um número fixo de
# requisições. A medida não depende do gerador de carga nem do throttling da cota de CPU.
#
# Variantes:
#   released        core instalado pelo composer.lock da variante pivotphp/
#   candidate       src/ de um checkout local do core            (com --core-src)
#   candidate-lazy  candidate + Application::lazyHandlers()      (com --core-src)
#   floor           PHP puro no mesmo Nginx + PHP-FPM (piso)
#   slim, symfony   referência                                   (com --with-frameworks)
#
# As variantes são alternadas a cada rodada. Ao final gera results/<run>/report.md.
#
# Uso:
#   ./scripts/run-microbench-core.sh [--core-src ../pivotphp-core] [--rounds 5] [--requests 20000]
#                                    [--vus 8] [--route /api/bare] [--with-frameworks]
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

CORE_SRC=""
ROUNDS=5
REQUESTS=20000
VUS=8
ROUTE="/api/bare"
WITH_FRAMEWORKS=0
K6_IMAGE="grafana/k6:2.3.0"
PREFIX="pivotphp-microbench-core"
NETWORK="${PREFIX}-net"
APP="${PREFIX}-app"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --core-src) CORE_SRC="$2"; shift 2 ;;
        --rounds) ROUNDS="$2"; shift 2 ;;
        --requests) REQUESTS="$2"; shift 2 ;;
        --vus) VUS="$2"; shift 2 ;;
        --route) ROUTE="$2"; shift 2 ;;
        --with-frameworks) WITH_FRAMEWORKS=1; shift ;;
        *) echo "Opção desconhecida: $1" >&2; exit 2 ;;
    esac
done

RUN_ID="$(date +%Y%m%d_%H%M%S)_microbench-core"
RESULTS_DIR="$ROOT_DIR/results/${RUN_ID}"
mkdir -p "$RESULTS_DIR"

cleanup() {
    docker rm -f "$APP" >/dev/null 2>&1 || true
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
}
trap cleanup EXIT

echo "=========================================================="
echo "  PivotPHP Benchmarks — Microbench do core (CPU por requisição)"
echo "  Run ID:      $RUN_ID"
echo "  Rota:        $ROUTE"
echo "  Rodadas:     $ROUNDS × $REQUESTS requisições ($VUS VUs)"
echo "  Core local:  ${CORE_SRC:-(nenhum — só a versão instalada)}"
echo "=========================================================="

echo ">> Construindo imagens..."
VARIANTS=("released")
docker build -q -t "${PREFIX}:released" pivotphp >/dev/null

CORE_COMMIT=""
if [[ -n "$CORE_SRC" ]]; then
    [[ -d "$CORE_SRC/src" ]] || { echo "Checkout do core inválido: $CORE_SRC" >&2; exit 2; }
    CORE_COMMIT="$(git -C "$CORE_SRC" rev-parse --short HEAD 2>/dev/null || echo desconhecido)"
    if [[ -n "$(git -C "$CORE_SRC" status --porcelain -- src 2>/dev/null)" ]]; then
        CORE_COMMIT="${CORE_COMMIT}+alterações locais"
    fi
    docker build -q -t "${PREFIX}:candidate" --build-arg "BASE_IMAGE=${PREFIX}:released" \
        -f microbench/core/Dockerfile.candidate "$CORE_SRC/src" >/dev/null
    docker build -q -t "${PREFIX}:candidate-lazy" --build-arg "BASE_IMAGE=${PREFIX}:candidate" \
        -f microbench/core/Dockerfile.lazy microbench/core >/dev/null
    VARIANTS+=("candidate" "candidate-lazy")
fi

docker build -q -t "${PREFIX}:floor" --build-arg "BASE_IMAGE=${PREFIX}:released" \
    -f microbench/core/Dockerfile.floor microbench/core >/dev/null
VARIANTS+=("floor")

if [[ "$WITH_FRAMEWORKS" -eq 1 ]]; then
    for fw in slim symfony; do
        docker build -q -t "${PREFIX}:${fw}" "$fw" >/dev/null
        VARIANTS+=("$fw")
    done
fi

cat > "$RESULTS_DIR/metadata.json" <<META
{
  "run_id": "$RUN_ID",
  "git_commit": "$(git rev-parse HEAD 2>/dev/null || echo desconhecido)",
  "core_released": "$(docker run --rm --entrypoint php "${PREFIX}:released" -r 'require "vendor/autoload.php"; echo \Composer\InstalledVersions::getPrettyVersion("pivotphp/core");')",
  "core_candidate_commit": "$CORE_COMMIT",
  "php": "$(docker run --rm --entrypoint php "${PREFIX}:released" -r 'echo PHP_VERSION;')",
  "host_cpu": "$(grep -m1 'model name' /proc/cpuinfo 2>/dev/null | cut -d: -f2 | sed 's/^[ \t]*//')",
  "docker": "$(docker --version)",
  "k6_image": "$K6_IMAGE",
  "container_cpus": 1.0,
  "route": "$ROUTE",
  "rounds": $ROUNDS,
  "requests": $REQUESTS,
  "vus": $VUS
}
META

docker network inspect "$NETWORK" >/dev/null 2>&1 || docker network create "$NETWORK" >/dev/null

load() {
    docker run --rm -i --network "$NETWORK" \
        -e "REQUESTS=$1" -e "VUS=$VUS" -e "ROUTE=$ROUTE" -e "TARGET=${APP}:8080" \
        "$K6_IMAGE" run --quiet - < microbench/core/load.js 2>&1
}

cpu_usec() {
    docker exec "$APP" sh -c 'grep "^usage_usec" /sys/fs/cgroup/cpu.stat' | awk '{print $2}'
}

measure() {
    local variant="$1" round="$2" up=0 before after out checks
    docker rm -f "$APP" >/dev/null 2>&1 || true
    docker run -d --name "$APP" --network "$NETWORK" --cpus=1 --memory=512m -e APP_ENV=benchmark \
        "${PREFIX}:${variant}" >/dev/null

    for _ in $(seq 1 40); do
        if docker exec "$APP" php -r "exit(@file_get_contents('http://127.0.0.1:8080${ROUTE}') === false ? 1 : 0);" \
            2>/dev/null; then
            up=1
            break
        fi
        sleep 0.5
    done
    [[ "$up" -eq 1 ]] || { echo "A variante $variant não respondeu em $ROUTE" >&2; exit 1; }

    load "$((REQUESTS / 5))" >/dev/null # aquecimento (OPcache e workers do FPM)

    before="$(cpu_usec)"
    out="$(load "$REQUESTS")"
    after="$(cpu_usec)"

    checks="$(echo "$out" | grep -E 'checks_succeeded' | grep -oE '[0-9]+\.[0-9]+%' | head -1 | tr -d '%')"
    [[ "$checks" == "100.00" ]] || { echo "Variante $variant: checks ${checks:-?}% (esperado 100%)" >&2; exit 1; }

    echo "${variant},${round},${REQUESTS},$((after - before))" >> "$RESULTS_DIR/cpu.csv"
    echo ">> Rodada $round/$ROUNDS — $variant: $(((after - before) / REQUESTS)) µs/req"
}

echo "variant,round,requests,cpu_usec" > "$RESULTS_DIR/cpu.csv"

for ((round = 1; round <= ROUNDS; round++)); do
    # Rotaciona a ordem: cada variante passa por posições diferentes ao longo das rodadas.
    offset=$(((round - 1) % ${#VARIANTS[@]}))
    order=("${VARIANTS[@]:offset}" "${VARIANTS[@]:0:offset}")
    for variant in "${order[@]}"; do
        measure "$variant" "$round"
    done
done

python3 scripts/report-microbench-core.py "$RESULTS_DIR" > "$RESULTS_DIR/report.md"
echo ""
echo ">> Relatório: $RESULTS_DIR/report.md"
