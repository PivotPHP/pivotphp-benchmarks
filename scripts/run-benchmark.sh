#!/usr/bin/env bash
#
# scripts/run-benchmark.sh
# Orquestrador reproduzível do benchmark (SPEC-106).
#
# Sobe cada framework em ambiente isolado com limites estritos de recursos,
# monitora CPU/RAM em tempo real, executa k6 com exportação de sumário JSON,
# armazena metadados e gera report.md dividindo os resultados em rankings:
#   1. Grupo PHP-FPM (Nginx + PHP-FPM)
#   2. Comparativo Geral de Arquiteturas (incluindo Webman - async)
#
# Uso:
#   ./scripts/run-benchmark.sh [framework] [cenario]
#   ./scripts/run-benchmark.sh                 # todos os frameworks, vote-load
#   ./scripts/run-benchmark.sh pivotphp        # só pivotphp
#   ./scripts/run-benchmark.sh pivotphp spike  # pivotphp, cenário spike
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

FRAMEWORKS=("pivotphp" "slim" "mezzio" "symfony" "webman")
SCENARIO="${2:-vote-load}"

if [[ $# -ge 1 && "${1:-}" != "" && " ${FRAMEWORKS[*]} " == *" ${1} "* ]]; then
    FRAMEWORKS=("$1")
fi

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
RUN_ID="${TIMESTAMP}"
RESULTS_DIR="$ROOT_DIR/results/${RUN_ID}"
mkdir -p "$RESULTS_DIR"

echo "=========================================================="
echo "  PivotPHP Benchmarks — Orquestrador (SPEC-106)"
echo "  Run ID:    $RUN_ID"
echo "  Cenário:   $SCENARIO"
echo "  Destino:   $RESULTS_DIR"
echo "=========================================================="
echo ""

# 1. Validação de ambiente
if [[ -f "$ROOT_DIR/scripts/validate-environment.sh" ]]; then
    echo ">> Validando ambiente..."
    "$ROOT_DIR/scripts/validate-environment.sh" || {
        echo "Aviso: Ambiente apresentou inconsistências, mas continuando..."
    }
    echo ""
fi

# 2. Coletar metadados do host e do git
GIT_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "desconhecido")
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "desconhecido")
CPU_MODEL=$(grep -m1 "model name" /proc/cpuinfo 2>/dev/null | cut -d: -f2 | sed 's/^[ \t]*//' || echo "Desconhecido")
CPU_CORES=$(nproc 2>/dev/null || grep -c "^processor" /proc/cpuinfo 2>/dev/null || echo "1")
RAM_KB=$(grep MemTotal /proc/meminfo 2>/dev/null | awk '{print $2}' || echo "0")
RAM_MB=$((RAM_KB / 1024))
OS_INFO="$(uname -s 2>/dev/null || echo "Linux") $(uname -r 2>/dev/null || echo "")"

cat <<EOF > "$RESULTS_DIR/metadata.json"
{
  "run_id": "$RUN_ID",
  "scenario": "$SCENARIO",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "git": {
    "commit": "$GIT_COMMIT",
    "branch": "$GIT_BRANCH"
  },
  "host": {
    "os": "$OS_INFO",
    "cpu_model": "$CPU_MODEL",
    "cpu_cores": $CPU_CORES,
    "ram_total_mb": $RAM_MB
  },
  "limits": {
    "app": {
      "cpus": 1.0,
      "memory": "512M"
    },
    "postgres": {
      "cpus": 2.0,
      "memory": "2G"
    },
    "redis": {
      "cpus": 0.5,
      "memory": "256M"
    }
  },
  "frameworks": $(printf '%s\n' "${FRAMEWORKS[@]}" | jq -R . | jq -s .)
}
EOF

# 3. Inicializa arquivo CSV de métricas de recursos
RESOURCE_CSV="$RESULTS_DIR/resource-usage.csv"
echo "timestamp,framework,container,cpu_perc,mem_usage,mem_perc,net_io,block_io" > "$RESOURCE_CSV"

# Função monitora recursos dos containers a cada 2s
MONITOR_PID=""
start_resource_monitor() {
    local current_fw="$1"
    if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        (
            while true; do
                local now
                now=$(date +%s)
                docker stats --no-stream --format "{{.Name}},{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}}" 2>/dev/null | while read -r line; do
                    if [[ -n "$line" ]]; then
                        echo "${now},${current_fw},${line}" >> "$RESOURCE_CSV"
                    fi
                done
                sleep 2
            done
        ) &
        MONITOR_PID=$!
    fi
}

stop_resource_monitor() {
    if [[ -n "$MONITOR_PID" ]]; then
        kill "$MONITOR_PID" 2>/dev/null || true
        wait "$MONITOR_PID" 2>/dev/null || true
        MONITOR_PID=""
    fi
}

trap stop_resource_monitor EXIT INT TERM

# 4. Executar benchmark para cada framework
for fw in "${FRAMEWORKS[@]}"; do
    echo "=========================================================="
    echo "  Executando: $fw"
    echo "=========================================================="

    if ! command -v docker >/dev/null 2>&1; then
        echo "Docker não disponível no host. Simulando log de teste..."
        echo "Simulação de execução para $fw (sem Docker no host)." > "$RESULTS_DIR/${fw}_${SCENARIO}.txt"
        continue
    fi

    (
        cd "$fw"

        # Sobe dependências e aplicação com limites estritos
        docker compose up --build -d postgres redis app >/dev/null 2>&1 || docker compose up -d postgres redis app >/dev/null 2>&1
        sleep 10

        start_resource_monitor "$fw"

        # Smoke test determinístico (reseta dados e valida)
        echo "  -> Executando smoke test de validação..."
        docker compose --profile loadtest run --rm k6 run smoke.js \
            > "$RESULTS_DIR/${fw}_smoke.txt" 2>&1 || true

        # Cenário principal com exportação de summary JSON
        echo "  -> Executando cenário de carga ($SCENARIO)..."
        docker compose --profile loadtest run --rm \
            -v "$RESULTS_DIR":/out \
            k6 run --summary-export "/out/${fw}_${SCENARIO}_summary.json" "$SCENARIO.js" \
            > "$RESULTS_DIR/${fw}_${SCENARIO}.txt" 2>&1 || true

        stop_resource_monitor

        docker compose down -v >/dev/null 2>&1
    )
    echo "  -> Concluído: $fw"
    echo ""
done

# 5. Geração do relatório consolidado
if [[ -f "$ROOT_DIR/scripts/generate-report.py" ]] && command -v python3 >/dev/null 2>&1; then
    echo ">> Gerando relatório consolidado..."
    python3 "$ROOT_DIR/scripts/generate-report.py" "$RESULTS_DIR"
    echo ""
fi

# Exibir report gerado no terminal se existir
if [[ -f "$RESULTS_DIR/report.md" ]]; then
    cat "$RESULTS_DIR/report.md"
fi
