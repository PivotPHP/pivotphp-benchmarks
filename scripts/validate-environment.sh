#!/usr/bin/env bash
#
# scripts/validate-environment.sh
# Validador de ambiente e garantia de limites de recursos para a suíte de benchmarks (SPEC-106).
#

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}  PivotPHP Benchmarks — Validação de Ambiente (SPEC-106)  ${NC}"
echo -e "${BLUE}======================================================${NC}"
echo ""

ERRORS=0
WARNINGS=0

# 1. Ferramentas essenciais no host
echo -e "1. ${BLUE}Verificando utilitários do sistema...${NC}"

for cmd in curl jq awk sha256sum; do
    if command -v "$cmd" >/dev/null 2>&1; then
        echo -e "  [${GREEN}OK${NC}] Comando '$cmd' disponível."
    else
        echo -e "  [${RED}FALHA${NC}] Comando '$cmd' não encontrado."
        ERRORS=$((ERRORS + 1))
    fi
done

# 2. Docker e Docker Compose
echo ""
echo -e "2. ${BLUE}Verificando Docker e Docker Compose...${NC}"

if command -v docker >/dev/null 2>&1; then
    DOCKER_VERSION=$(docker --version 2>/dev/null || echo "desconhecido")
    echo -e "  [${GREEN}OK${NC}] Docker instalado: $DOCKER_VERSION"

    if docker info >/dev/null 2>&1; then
        echo -e "  [${GREEN}OK${NC}] Docker daemon está acessível e em execução."

        # Verificar Docker Compose
        if docker compose version >/dev/null 2>&1; then
            COMPOSE_VERSION=$(docker compose version 2>/dev/null)
            echo -e "  [${GREEN}OK${NC}] Docker Compose (v2 plugin): $COMPOSE_VERSION"
        elif command -v docker-compose >/dev/null 2>&1; then
            COMPOSE_VERSION=$(docker-compose --version 2>/dev/null)
            echo -e "  [${YELLOW}AVISO${NC}] docker-compose v1 encontrado: $COMPOSE_VERSION. Recomendado plugin v2."
            WARNINGS=$((WARNINGS + 1))
        else
            echo -e "  [${RED}FALHA${NC}] Docker Compose não disponível."
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo -e "  [${YELLOW}AVISO${NC}] Docker daemon não está rodando no momento ou permissão negada."
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "  [${YELLOW}AVISO${NC}] Docker CLI não encontrado no PATH atual."
    echo -e "          (Ambientes de sandbox/CI sem Docker podem validar apenas arquivos e schemas)."
    WARNINGS=$((WARNINGS + 1))
fi

# 3. Informações de hardware e sistema
echo ""
echo -e "3. ${BLUE}Coletando especificações da máquina hospedeira...${NC}"

OS_NAME=$(uname -s 2>/dev/null || echo "Desconhecido")
KERNEL_VER=$(uname -r 2>/dev/null || echo "Desconhecido")
ARCH=$(uname -m 2>/dev/null || echo "Desconhecido")
CPU_MODEL=$(grep -m1 "model name" /proc/cpuinfo 2>/dev/null | cut -d: -f2 | sed 's/^[ \t]*//' || echo "Desconhecido")
CPU_CORES=$(nproc 2>/dev/null || grep -c "^processor" /proc/cpuinfo 2>/dev/null || echo "1")
TOTAL_MEM_KB=$(grep MemTotal /proc/meminfo 2>/dev/null | awk '{print $2}' || echo "0")
TOTAL_MEM_MB=$((TOTAL_MEM_KB / 1024))

echo -e "  Sistema Operacional: $OS_NAME ($ARCH)"
echo -e "  Kernel Linux:       $KERNEL_VER"
echo -e "  Modelo de CPU:      $CPU_MODEL"
echo -e "  Núcleos de CPU:     $CPU_CORES"
echo -e "  Memória RAM Total:  ${TOTAL_MEM_MB} MB (~$((TOTAL_MEM_MB / 1024)) GB)"

if [[ "$CPU_CORES" -lt 4 ]]; then
    echo -e "  [${YELLOW}AVISO${NC}] Recomendado ao menos 4 vCPUs para isolamento adequado de carga."
    WARNINGS=$((WARNINGS + 1))
fi

if [[ "$TOTAL_MEM_MB" -lt 4096 ]]; then
    echo -e "  [${YELLOW}AVISO${NC}] Recomendado ao menos 4 GB de RAM total para o ambiente de testes."
    WARNINGS=$((WARNINGS + 1))
fi

# 4. Integridade dos schemas e dados de referência
echo ""
echo -e "4. ${BLUE}Verificando integridade e hashes dos arquivos de banco...${NC}"

FRAMEWORKS=("pivotphp" "slim" "mezzio" "symfony" "webman")

for fw in "${FRAMEWORKS[@]}"; do
    if [[ -d "$fw" ]]; then
        schema="$fw/database/schema.sql"
        seed="$fw/database/seed.sql"
        compose="$fw/docker-compose.yml"

        if [[ -f "$schema" && -f "$seed" && -f "$compose" ]]; then
            schema_hash=$(sha256sum "$schema" | awk '{print $1}')
            seed_hash=$(sha256sum "$seed" | awk '{print $1}')
            echo -e "  [${GREEN}OK${NC}] $fw: schema ($schema_hash) | seed ($seed_hash)"
        else
            echo -e "  [${RED}FALHA${NC}] $fw: arquivos de banco ou compose ausentes."
            ERRORS=$((ERRORS + 1))
        fi
    else
        echo -e "  [${RED}FALHA${NC}] Diretório '$fw' não encontrado."
        ERRORS=$((ERRORS + 1))
    fi
done

# 5. Teste de limites efetivos em contêiner (se Docker estiver rodando)
echo ""
echo -e "5. ${BLUE}Verificando aplicação efetiva de limites de recursos...${NC}"

if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    echo "  Executando contêiner de teste com --cpus=1.0 e -m=512m..."
    CGROUP_OUTPUT=$(docker run --rm --cpus=1.0 -m=512m alpine sh -c '
        if [ -f /sys/fs/cgroup/cpu.max ]; then
            quota=$(cat /sys/fs/cgroup/cpu.max | awk "{print \$1}")
            period=$(cat /sys/fs/cgroup/cpu.max | awk "{print \$2}")
            echo "cgroups_v2 cpu: $quota/$period"
        elif [ -f /sys/fs/cgroup/cpu/cpu.cfs_quota_us ]; then
            quota=$(cat /sys/fs/cgroup/cpu/cpu.cfs_quota_us)
            period=$(cat /sys/fs/cgroup/cpu/cpu.cfs_period_us)
            echo "cgroups_v1 cpu: $quota/$period"
        fi
        if [ -f /sys/fs/cgroup/memory.max ]; then
            echo "cgroups_v2 mem: $(cat /sys/fs/cgroup/memory.max)"
        elif [ -f /sys/fs/cgroup/memory/memory.limit_in_bytes ]; then
            echo "cgroups_v1 mem: $(cat /sys/fs/cgroup/memory/memory.limit_in_bytes)"
        fi
    ' 2>/dev/null || echo "falha_execucao")

    if [[ "$CGROUP_OUTPUT" != "falha_execucao" ]]; then
        echo -e "  [${GREEN}OK${NC}] Limites no contêiner detectados:"
        echo "$CGROUP_OUTPUT" | sed 's/^/    /'
    else
        echo -e "  [${RED}FALHA${NC}] Não foi possível aplicar limites efetivos no contêiner."
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "  [${YELLOW}PULADO${NC}] Docker não disponível para teste dinâmico de cgroups."
fi

# Resumo
echo ""
echo -e "${BLUE}======================================================${NC}"
if [[ $ERRORS -eq 0 ]]; then
    echo -e "${GREEN}Validação concluída com sucesso!${NC} (Erros: 0, Avisos: $WARNINGS)"
    exit 0
else
    echo -e "${RED}Validação falhou com $ERRORS erro(s).${NC} (Avisos: $WARNINGS)"
    exit 1
fi
