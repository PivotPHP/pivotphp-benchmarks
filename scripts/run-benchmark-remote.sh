#!/usr/bin/env bash
#
# scripts/run-benchmark-remote.sh
# Orquestrador remoto (SPEC-110): roda no gerador (notebook), comanda as stacks
# na VM (SUT) via SSH e executa o k6 LOCALMENTE (nativo), para que o gerador de
# carga não dispute CPU com as aplicações.
#
# Pré-requisitos no gerador: k6 nativo no PATH, ssh com chave sem senha para a VM,
# o repositório clonado (usa apenas k6/scripts/).
# Pré-requisitos na VM: Docker Engine, repositório clonado no MESMO caminho relativo
# usado em --remote-path (os compose montam ./database/*.sql — bind mount local).
#
# Uso:
#   ./scripts/run-benchmark-remote.sh --host bench@192.168.18.186 \
#       [--remote-path ~/pivotphp-benchmark] [--scenario vote-load] \
#       [--repeats 5] [--warmup 1] [--cpu-threshold 5]
#
# Gera em results/<run-id>/: metadata.json (com os 2 hosts, versão do k6, latência
# de rede), resource-usage.csv (containers + CPU da VM inteira), <fw>_<cenario>.txt,
# <fw>_<cenario>_summary.json, report.md. Repetições com interferência de CPU acima
# do limiar são marcadas e re-executadas automaticamente.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

# ---------- Argumentos ----------
REMOTE_HOST=""
REMOTE_PATH="~/pivotphp-benchmark"  # expandido pelo shell REMOTO (bash da VM) dentro dos comandos ssh, não aqui
SCENARIO="vote-load"
REPEATS=5
WARMUP=1
CPU_THRESHOLD=5          # % de CPU da VM atribuível a algo que não seja o benchmark
MAX_RETRIES=2            # por repetição contaminada

while [[ $# -gt 0 ]]; do
    case "$1" in
        --host) REMOTE_HOST="$2"; shift 2 ;;
        --remote-path) REMOTE_PATH="$2"; shift 2 ;;
        --scenario) SCENARIO="$2"; shift 2 ;;
        --repeats) REPEATS="$2"; shift 2 ;;
        --warmup) WARMUP="$2"; shift 2 ;;
        --cpu-threshold) CPU_THRESHOLD="$2"; shift 2 ;;
        -h|--help)
            sed -n '2,20p' "$0"; exit 0 ;;
        *) echo "Argumento desconhecido: $1" >&2; exit 1 ;;
    esac
done

if [[ -z "$REMOTE_HOST" ]]; then
    echo "Uso: $0 --host usuario@ip-da-vm [opcoes]" >&2
    exit 1
fi

if ! command -v k6 >/dev/null 2>&1; then
    echo "k6 não encontrado no PATH. Instale o k6 nativo (winget install k6.k6)." >&2
    exit 1
fi
if ! command -v ssh >/dev/null 2>&1; then
    echo "ssh não encontrado no PATH." >&2
    exit 1
fi

ssh -o BatchMode=yes -o ConnectTimeout=5 "$REMOTE_HOST" true 2>/dev/null || {
    echo "Não foi possível conectar em $REMOTE_HOST por SSH sem senha. Confira a chave." >&2
    exit 1
}

declare -A PORT=([pivotphp]=8080 [slim]=8081 [mezzio]=8082 [symfony]=8083 [webman]=8084)
BASE_FRAMEWORKS=(pivotphp slim mezzio symfony webman)

K6_VERSION="$(k6 version | head -1)"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
RUN_ID="${TIMESTAMP}_remote"
RESULTS_DIR="$ROOT_DIR/results/${RUN_ID}"
mkdir -p "$RESULTS_DIR"

# resource-usage.csv é por REPETIÇÃO (consolidate-runs.py lê "<rep_dir>/resource-usage.csv");
# host-cpu-usage.csv é global, extra, para auditar interferência por rodada inteira.
HOST_CPU_CSV="$RESULTS_DIR/host-cpu-usage.csv"
echo "timestamp,repetition,framework,vm_cpu_total_perc,containers_cpu_perc,external_cpu_perc" > "$HOST_CPU_CSV"

echo "=========================================================="
echo "  PivotPHP Benchmarks — Orquestrador REMOTO (SPEC-110)"
echo "  Run ID:        $RUN_ID"
echo "  VM (SUT):      $REMOTE_HOST:$REMOTE_PATH"
echo "  Gerador:       local (k6 nativo, $K6_VERSION)"
echo "  Cenário:       $SCENARIO"
echo "  Repetições:    $REPEATS (+ $WARMUP de aquecimento, descartada(s))"
echo "  Destino:       $RESULTS_DIR"
echo "=========================================================="
echo ""

# ---------- Latência base ----------
# ping.exe do Windows (via Git Bash) localizado em EN ("Average = Xms") ou PT-BR
# ("Média = Xms", às vezes com vírgula decimal e/ou encoding OEM no console);
# ping do Linux/macOS usa "rtt min/avg/max/mdev = .../AVG/...".
# Se nenhum padrão bater, PING_AVG fica "desconhecido" e a saída crua é salva em
# ping-raw.txt para conferência manual — a rodada não é interrompida por isso.
REMOTE_IP="${REMOTE_HOST##*@}"
echo ">> Medindo latência base da rede ($REMOTE_IP)..."
PING_OUT="$(ping -n 20 "$REMOTE_IP" 2>/dev/null || ping -c 20 "$REMOTE_IP" 2>/dev/null || echo "")"
echo "$PING_OUT" > "$RESULTS_DIR/ping-raw.txt" 2>/dev/null || true
PING_AVG="$(echo "$PING_OUT" | grep -ioE '(Average|Média) = [0-9]+[,.]?[0-9]*ms' | grep -oE '[0-9]+[,.]?[0-9]*' | tr ',' '.')"
if [[ -z "$PING_AVG" ]]; then
    PING_AVG="$(echo "$PING_OUT" | grep -oE 'rtt [a-z/]+ = [0-9.]+/[0-9.]+/[0-9.]+' | sed -E 's#.*/([0-9.]+)/[0-9.]+$#\1#')"
fi
if [[ -z "$PING_AVG" ]]; then
    echo "   Não consegui extrair a média automaticamente (saída em ping-raw.txt)."
    PING_AVG="desconhecido"
fi
echo "   Latência média: ${PING_AVG}ms"
echo ""

# ---------- Metadados do host remoto (VM) ----------
echo ">> Coletando metadados da VM..."
VM_INFO="$(ssh "$REMOTE_HOST" "nproc; grep MemTotal /proc/meminfo | awk '{print \$2}'; uname -sr; docker --version")"
VM_CPU_CORES="$(echo "$VM_INFO" | sed -n 1p)"
VM_RAM_KB="$(echo "$VM_INFO" | sed -n 2p)"
VM_RAM_MB=$((VM_RAM_KB / 1024))
VM_OS="$(echo "$VM_INFO" | sed -n 3p)"
VM_DOCKER="$(echo "$VM_INFO" | sed -n 4p)"

GEN_CPU_MODEL="$(grep -m1 "model name" /proc/cpuinfo 2>/dev/null | cut -d: -f2 | sed 's/^[ \t]*//' || echo "Desconhecido (Windows)")"
GEN_OS="$(uname -s 2>/dev/null || echo "Windows")"
GIT_COMMIT="$(git rev-parse HEAD 2>/dev/null || echo desconhecido)"

cat <<EOF > "$RESULTS_DIR/metadata.json"
{
  "run_id": "$RUN_ID",
  "scenario": "$SCENARIO",
  "mode": "remote",
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "git_commit": "$GIT_COMMIT",
  "k6_version": "$K6_VERSION",
  "network": {
    "ping_avg_ms": "$PING_AVG"
  },
  "sut_vm": {
    "host": "$REMOTE_HOST",
    "os": "$VM_OS",
    "docker": "$VM_DOCKER",
    "cpu_cores": $VM_CPU_CORES,
    "ram_total_mb": $VM_RAM_MB
  },
  "generator": {
    "os": "$GEN_OS",
    "cpu_model": "$GEN_CPU_MODEL"
  },
  "limits": {
    "app": { "cpus": 1.0, "memory": "512M" },
    "postgres": { "cpus": 2.0, "memory": "2G" },
    "redis": { "cpus": 0.5, "memory": "256M" }
  },
  "frameworks": $(printf '%s\n' "${BASE_FRAMEWORKS[@]}" | sed 's/.*/"&"/' | paste -sd, | sed 's/^/[/;s/$/]/'),
  "repeats": $REPEATS,
  "warmup_repeats": $WARMUP,
  "cpu_interference_threshold_pct": $CPU_THRESHOLD
}
EOF
echo "   VM: $VM_CPU_CORES vCPU, ${VM_RAM_MB}MB RAM, $VM_OS, $VM_DOCKER"
echo ""

remote_up() {
    local fw="$1"
    ssh "$REMOTE_HOST" "cd $REMOTE_PATH/$fw && docker compose up -d postgres redis app" >/dev/null
}

remote_down() {
    local fw="$1"
    ssh "$REMOTE_HOST" "cd $REMOTE_PATH/$fw && docker compose down -v" >/dev/null 2>&1 || true
}

wait_health() {
    local fw="$1" port="${PORT[$1]}"
    for _ in $(seq 1 60); do
        curl -sf "http://${REMOTE_IP}:${port}/api/health" >/dev/null 2>&1 && return 0
        sleep 2
    done
    return 1
}

# VM CPU total (idle% da primeira linha de `mpstat` ou /proc/stat snapshot) e CPU
# somada dos containers do benchmark; a diferença é CPU "externa" ao benchmark.
sample_host_cpu() {
    local fw="$1" rep="$2"
    local now vm_total containers_sum external
    now=$(date +%s)
    vm_total=$(ssh "$REMOTE_HOST" "top -bn2 -d0.3 | grep '^%Cpu' | tail -1 | awk -F'[,: ]+' '{for(i=1;i<=NF;i++) if(\$i==\"id\") print 100-\$(i-1)}'" 2>/dev/null || echo "0")
    containers_sum=$(ssh "$REMOTE_HOST" "docker stats --no-stream --format '{{.CPUPerc}}' 2>/dev/null | tr -d '%' | awk '{s+=\$1} END{print s+0}'" 2>/dev/null || echo "0")
    external=$(awk -v t="$vm_total" -v c="$containers_sum" 'BEGIN{d=t-c; if(d<0) d=0; printf "%.1f", d}')
    echo "${now},${rep},${fw},${vm_total},${containers_sum},${external}" >> "$HOST_CPU_CSV"
    echo "$external"
}

sample_containers() {
    # Grava em <out_dir>/resource-usage.csv — formato lido por consolidate-runs.py
    # (sem a coluna "repetition": cada repetição já tem seu próprio arquivo).
    local fw="$1" out_dir="$2"
    local csv="$out_dir/resource-usage.csv"
    [[ -f "$csv" ]] || echo "timestamp,framework,container,cpu_perc,mem_usage,mem_perc,net_io,block_io" > "$csv"
    local now
    now=$(date +%s)
    ssh "$REMOTE_HOST" "docker stats --no-stream --format '{{.Name}},{{.CPUPerc}},{{.MemUsage}},{{.MemPerc}},{{.NetIO}},{{.BlockIO}}'" 2>/dev/null | \
        while IFS= read -r line; do
            [[ -n "$line" ]] && echo "${now},${fw},${line}" >> "$csv"
        done
}

run_one() {
    local fw="$1" rep_label="$2" out_dir="$3"
    local port="${PORT[$fw]}"
    local base="http://${REMOTE_IP}:${port}"

    echo "  -> [$rep_label] $fw: subindo stack na VM..."
    remote_up "$fw"
    if ! wait_health "$fw"; then
        echo "     FALHA: $fw não respondeu em /api/health"
        ssh "$REMOTE_HOST" "cd $REMOTE_PATH/$fw && docker compose logs --tail=40 app" > "$out_dir/${fw}_boot-failure.log" 2>&1 || true
        remote_down "$fw"
        return 1
    fi

    # Amostra de CPU em repouso (framework de pé, sem carga) — detecta interferência.
    sleep 3
    local idle_external
    idle_external="$(sample_host_cpu "$fw" "$rep_label")"

    echo "     smoke..."
    k6 run -q -e BASE_URL="$base" k6/scripts/smoke.js > "$out_dir/${fw}_smoke.txt" 2>&1 || true

    # Monitor de recursos durante a carga (containers + CPU do host), a cada ~3s.
    (
        while true; do
            sample_containers "$fw" "$out_dir"
            sample_host_cpu "$fw" "$rep_label" >/dev/null
            sleep 3
        done
    ) &
    local monitor_pid=$!

    echo "     $SCENARIO..."
    k6 run -q -e BASE_URL="$base" \
        --summary-export "$out_dir/${fw}_${SCENARIO}_summary.json" \
        "k6/scripts/${SCENARIO}.js" > "$out_dir/${fw}_${SCENARIO}.txt" 2>&1 || true

    kill "$monitor_pid" 2>/dev/null || true
    wait "$monitor_pid" 2>/dev/null || true

    remote_down "$fw"

    if awk -v t="$CPU_THRESHOLD" -v x="$idle_external" 'BEGIN{exit !(x>t)}'; then
        echo "     AVISO: CPU externa de ${idle_external}% na VM antes da carga (limiar ${CPU_THRESHOLD}%)"
        return 2
    fi
    return 0
}

run_repetition() {
    local rep_label="$1"
    local frameworks=("${@:2}")
    local out_dir="$RESULTS_DIR/$rep_label"
    mkdir -p "$out_dir"
    local contaminated=0
    for fw in "${frameworks[@]}"; do
        local attempt=1 status=1
        while [[ $attempt -le $((MAX_RETRIES + 1)) ]]; do
            # Sucesso (status 0): sai no 'then' com break antes da linha seguinte.
            # Falha/interferência: cai aqui e $? ainda é o de run_one (nada rodou entre o if e esta linha).
            if run_one "$fw" "$rep_label" "$out_dir"; then status=0; break; fi
            status=$?
            if [[ $status -eq 2 ]]; then
                echo "     Repetindo $fw (tentativa $((attempt + 1)))..."
                attempt=$((attempt + 1))
                continue
            fi
            break
        done
        [[ $status -ne 0 ]] && contaminated=1
    done
    return $contaminated
}

rotate() {
    # Rotaciona o array de entrada em $1 posições (rodízio de ordem por repetição).
    local n="$1"; shift
    local arr=("$@")
    local len=${#arr[@]}
    local r=$((n % len))
    echo "${arr[@]:r}" "${arr[@]:0:r}"
}

# ---------- Aquecimento (descartado) ----------
for w in $(seq 1 "$WARMUP"); do
    echo "=========================================================="
    echo "  Aquecimento $w/$WARMUP (descartado)"
    echo "=========================================================="
    read -ra order <<< "$(rotate $((w - 1)) "${BASE_FRAMEWORKS[@]}")"
    run_repetition "warmup${w}" "${order[@]}" || echo "  (aquecimento com aviso de interferência — ok, é descartado)"
    rm -rf "$RESULTS_DIR/warmup${w}"
    echo ""
done

# ---------- Repetições medidas ----------
for r in $(seq 1 "$REPEATS"); do
    echo "=========================================================="
    echo "  Repetição $r/$REPEATS"
    echo "=========================================================="
    read -ra order <<< "$(rotate $((WARMUP + r - 1)) "${BASE_FRAMEWORKS[@]}")"
    echo "  Ordem: ${order[*]}"
    run_repetition "rep${r}" "${order[@]}"
    status=$?
    [[ $status -ne 0 ]] && echo "  ATENÇÃO: repetição $r excedeu as novas tentativas com interferência de CPU detectada."
    echo ""
done

echo ">> Consolidando repetições..."
PYTHON_BIN="$(command -v python3 || command -v python || true)"
if [[ -n "$PYTHON_BIN" ]]; then
    "$PYTHON_BIN" "$ROOT_DIR/scripts/consolidate-runs.py" --scenario "$SCENARIO" "$RESULTS_DIR"/rep* \
        > "$RESULTS_DIR/consolidated.md" 2>&1 \
        && cat "$RESULTS_DIR/consolidated.md" \
        || echo "   (consolidação falhou — rode manualmente depois de conferir os dados: cat \"$RESULTS_DIR/consolidated.md\")"
else
    echo "   python não encontrado no PATH. Rode manualmente:"
    echo "   python scripts/consolidate-runs.py --scenario $SCENARIO \"$RESULTS_DIR\"/rep*"
fi

echo ""
echo "Resultados em: $RESULTS_DIR"
