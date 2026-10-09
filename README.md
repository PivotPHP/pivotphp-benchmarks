# PivotPHP Benchmarks

Suite de benchmarks Docker-isolada que compara variantes do [PivotPHP](https://github.com/PivotPHP) (Core, Core+Cycle ORM) entre si e contra outros microframeworks PHP (Slim 4, Lumen, Flight, Symfony), usando métricas HTTP reais (throughput, latência, uso de memória).

> ⚠️ **Registro histórico — extensão ReactPHP descontinuada em 2026-10-09.** A variante
> Core+ReactPHP foi removida desta suíte (pacote `pivotphp/reactphp` arquivado). Relatórios
> e scripts históricos que ainda citam ReactPHP são mantidos apenas como registro.

> Este README descreve a linha **v1.2.0** (`Makefile-v120` / `docker-compose-v120.yml`), a mais atual no repositório hoje. A linha **v1.1.4** (`Makefile-v114` / `docker-compose-v114.yml`) continua presente e funcional para comparação histórica de versões — veja a seção [Linha v1.1.4](#linha-v114-comparação-histórica).

## O que este repositório é (e o que não é)

- É uma suite de benchmarks com ambientes Docker isolados por variante/framework, cada um com seu próprio `composer.json` e container, para evitar conflito de dependências e garantir comparação justa.
- Está organizado em três fases (`phase1/`, `phase2/`, `phase3/`) mais um conjunto de scripts de benchmark standalone na raiz.
- **Não** é um pacote instalável via `composer require`; é um projeto de testes que se roda localmente/via Docker.
- `composer.json` na raiz declara autoload PSR-4 para diretórios (`src/`, `tests/`) que não existem — é resíduo de uma estrutura anterior. Ver [Divergências conhecidas](#divergências-conhecidas).

## Pré-requisitos

- Docker e Docker Compose
- PHP 8.1+ (8.2+ para `phase1/`) para rodar scripts standalone fora de container
- Composer
- `make` (opcional, mas os comandos abaixo assumem `make`)
- `curl` e `jq` (para checagens manuais de health-check)

## Instalação

```bash
# Dependências de cada fase (phase1, phase2, phase3)
make -f Makefile-v120 install-deps

# Equivalente manual, fase a fase
cd phase1 && composer install && cd ..
cd phase2 && composer install && cd ..
cd phase3 && composer install && cd ..
```

Não existe um `composer.json` funcional na raiz para `composer install` — o `composer.json` da raiz declara apenas scripts de benchmark (ver abaixo), sem dependências de runtime além de `pivotphp/core`. `composer validate` no `composer.json` raiz passa (é sintaticamente válido).

## Como rodar (linha v1.2.0)

### 1. Subir o ambiente Docker

```bash
make -f Makefile-v120 build   # constrói as imagens (docker-compose-v120.yml)
make -f Makefile-v120 up      # sobe os containers
make -f Makefile-v120 status  # confere se os containers subiram
make -f Makefile-v120 down    # derruba o ambiente
```

### 2. Checar saúde dos containers

```bash
make -f Makefile-v120 health
```

### 3. Rodar benchmarks standalone (sem precisar dos containers)

Estes três comandos correspondem a arquivos reais na raiz do projeto e também aos scripts declarados em `composer.json`:

```bash
php simple_benchmark_v120.php                    # composer run benchmark-v120
php openapi_benchmark_v120.php                   # composer run benchmark-openapi
php benchmarks/v114-vs-v120-comparison.php        # composer run benchmark-comparison
composer test-all                                 # roda os três em sequência
```

Ou via Makefile:

```bash
make -f Makefile-v120 benchmark-core     # simple_benchmark_v120.php
make -f Makefile-v120 benchmark-openapi  # openapi_benchmark_v120.php
make -f Makefile-v120 benchmark-legacy   # benchmarks/v114-vs-v120-comparison.php
```

### 4. Acesso aos containers

```bash
make -f Makefile-v120 shell-core       # shell no container pivotphp-core-v120
make -f Makefile-v120 shell-orm        # shell no container pivotphp-orm-v120
make -f Makefile-v120 shell-reactphp   # shell no container pivotphp-reactphp-v120
make -f Makefile-v120 logs             # logs de todos os containers
make -f Makefile-v120 logs-core        # logs só do core
```

### Containers e portas (v1.2.0)

| Container | Porta host | Descrição |
|---|---|---|
| `pivotphp-core-v120` | 8090 | PivotPHP Core v1.2.0 |
| `pivotphp-orm-v120` | 8091 | PivotPHP + Cycle ORM |
| `pivotphp-reactphp-v120` | 8092 | PivotPHP + ReactPHP |
| `pivotphp-ecosystem-v120` | 8093 | Cenário de ecossistema completo |
| `mysql-benchmark-v120` | 3307 | MySQL para testes ORM |
| `redis-benchmark-v120` | 6380 | Redis para cache |

### Comandos do Makefile-v120 confirmados funcionais

Os alvos abaixo foram checados um a um: os scripts/arquivos que eles invocam existem no repositório.

`help`, `build`, `up`, `down`, `restart`, `clean`, `validate`, `status`, `health`, `benchmark-core`, `benchmark-openapi`, `benchmark-legacy`, `shell-core`, `shell-orm`, `shell-reactphp`, `swagger-ui`, `logs`, `logs-core`, `logs-orm`, `logs-reactphp`, `install-deps`, `dev-setup`, `info`.

**Atenção:** vários outros alvos existem no `Makefile-v120` mas invocam arquivos `.php` que **não existem** no repositório (ex.: `phase1-test`, `phase2-test`, `phase3-test`, `openapi-test`, `benchmark-simplicity`, `report`, `full-validation`) — eles falham em runtime com "No such file or directory". Não estão documentados aqui como funcionais. Ver [Divergências conhecidas](#divergências-conhecidas).

## Linha v1.1.4 (comparação histórica)

A suite mantém a linha v1.1.4 completa e funcional para comparação:

```bash
make -f Makefile-v114 build
make -f Makefile-v114 up
make -f Makefile-v114 health
make -f Makefile-v114 benchmark-all      # benchmark-core + benchmark-phase2
php simple_benchmark_v114.php
```

Containers: `pivotphp-core-v114` (porta 8080), `pivotphp-orm-v114` (8081), `pivotphp-reactphp-v114` (8082), `mysql-benchmark-v114` (3306), `redis-benchmark-v114` (6379).

Documentação dedicada: [`README-v114.md`](README-v114.md).

## Estrutura real do repositório

```
pivotphp-benchmarks/
├── README.md                    # este arquivo
├── README-v114.md               # documentação específica da linha v1.1.4
├── CLAUDE.md                    # guia para Claude Code (não versionado no git)
├── composer.json                # scripts de benchmark v1.2.0 (raiz)
├── Makefile-v114 / Makefile-v120        # automação por linha de versão
├── docker-compose-v114.yml / docker-compose-v120.yml
├── docker/                      # Dockerfile-runner-v120
│
├── *.php (raiz)                 # benchmarks standalone, ex.:
│   ├── simple_benchmark_v120.php
│   ├── simple_benchmark_v114.php
│   ├── benchmark_v114.php
│   ├── benchmark_simulation_v120.php
│   ├── openapi_benchmark_v120.php
│   ├── http_benchmark_v120.php
│   ├── core-vs-reactphp-benchmark.php
│   ├── reactphp-conversion-analysis.php
│   ├── test_routing.php
│   └── validate-versions.php
│
├── benchmarks/                  # v114-vs-v120-comparison.php
│
├── phase1/                      # Fase 1: Core isolado
│   ├── composer.json, Makefile, README.md
│   ├── Dockerfile-v114, Dockerfile-v120
│   ├── docker/ (Dockerfile, docker-compose.yml — setup legado, ver nota)
│   ├── src/, public/
│   └── benchmarks/ (run.php, run-simple.php, run-simple-v114.php,
│       run-simple-v120.php, run-v114.php)
│
├── phase2/                      # Fase 2: Extensões (Cycle ORM, ReactPHP)
│   ├── composer.json, Makefile, README.md
│   ├── docker/ (Dockerfile-orm, Dockerfile-orm-v120,
│   │   Dockerfile-reactphp, Dockerfile-reactphp-v120, docker-compose.yml)
│   ├── config/, src/ (high-performance/, orm/, reactphp/)
│   └── benchmarks/ (run-phase2.php, run-phase2-v114*.php, run-phase2-v120.php,
│       run-v114.php, test-high-performance.php)
│
├── phase3/                      # Fase 3: Comparação cross-framework (Slim 4)
│   ├── composer.json, README.md
│   ├── docker/ (Dockerfile-ecosystem-v120, Dockerfile-pivotphp-v114,
│   │   Dockerfile-runner, Dockerfile-slim4, docker-compose-v114.yml)
│   ├── src/slim4/, public/
│   ├── benchmarks/ (run-phase3*.php, run-phase3-v114*.php)
│   └── ecosystem-validation/    # sub-harness próprio, com seu Makefile,
│       # docker-compose-*.yml, Dockerfiles e scripts dedicados
│
└── 14 relatórios .md soltos na raiz (RELATORIO_*, RESUMO_*, INDICE_*, etc.)
    # resultados de execuções datadas — ver aviso no topo de cada arquivo
```

Não existem `frameworks/`, `scripts/`, `results/`, `src/` ou `tests/` na raiz, nem `shared/` em nenhum lugar do repositório, apesar de serem referenciados em `composer.json` (autoload) e em versões anteriores da documentação.

## Divergências conhecidas

- **`composer.json` raiz**: `description` cita "v1.2.0" (consistente com o estado atual do repo), mas o bloco `autoload` (PSR-4) aponta para `src/`, `phase1/src/`, `phase2/src/`, `phase3/src/`, e `autoload-dev` aponta para `tests/` — nenhum `src/` ou `tests/` existe na raiz (só dentro de `phase1/`, `phase2/`, `phase3/`, que já têm seus próprios `composer.json` com autoload próprio). É autoload morto; `composer validate` não acusa erro porque só valida sintaxe, não existência de diretórios.
- **`Makefile-v120`**: vários alvos (`phase1-test`, `phase2-test`, `phase3-test`, `openapi-test`, `benchmark-simplicity`, `benchmark-all`, `compare-versions`, `report`, `openapi-specs`, `full-validation`) invocam scripts `.php` ou diretórios (`results/`, `scripts/`) que não existem no repositório. Não são documentados como funcionais neste README.
- **`phase2/composer.json`** ainda fixa `pivotphp/core: ^1.1.4` (não `^1.2.0`), diferente de `phase1/composer.json` e do `composer.json` raiz.
- **`phase1/Makefile`** usa seu próprio `docker/docker-compose.yml` + `docker/Dockerfile` (build de contexto próprio, portas fixas em 8080, dependente de `results/` e `config/` locais que não existem em `phase1/`) — é um setup paralelo e mais antigo, distinto do fluxo `docker-compose-v120.yml`/`docker-compose-v114.yml` na raiz. Os dois não devem ser confundidos.

## Licença

`composer.json` declara `MIT`, mas não há arquivo `LICENSE` no repositório.
