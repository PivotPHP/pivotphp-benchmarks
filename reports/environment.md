# Especificação do Ambiente de Benchmark — PivotPHP (SPEC-106)

Este documento descreve os parâmetros de referência e configuração do ambiente de testes
para garantir imparcialidade e reprodutibilidade dos benchmarks.

---

## 1. Perfil de Referência de Hardware

Para benchmarks oficiais de publicação, o ambiente de referência segue as diretrizes abaixo:

- **Arquitetura**: `x86_64` (Linux Kernel $\ge 6.1$)
- **Processador**: 4 vCPUs ou mais dedicadas ao sistema de teste
- **Memória RAM**: Mínimo 8 GB (16 GB recomendado para absorver buffers de I/O)
- **Armazenamento**: SSD NVMe (para evitar que o write-ahead log do PostgreSQL vire gargalo artificial)
- **Gerador de Carga**: k6 executado de forma dedicada ou isolada, sem contenção de CPU com a aplicação.

---

## 2. Alocação de Recursos por Contêiner

Todos os contêineres são limitados diretamente via Compose e cgroups:

| Serviço | Contêiner | Limite CPU | Limite Memória | Swap |
|:---|:---|:---:|:---:|:---:|
| **App (Framework PHP)** | `<fw>-app` | `1.0` | `512 MB` | `0` (desativado) |
| **Banco de Dados (PostgreSQL)** | `<fw>-postgres` | `2.0` | `2048 MB` | `0` |
| **Cache (Redis)** | `<fw>-redis` | `0.5` | `256 MB` | `0` |
| **Test Runner (k6)** | `<fw>-k6` | Sem limite host | Sem limite host | — |

---

## 3. Configurações dos Runtimes

### A. PHP-FPM 8.3 & OPcache
Configurações aplicadas igualmente a todos os frameworks PHP-FPM (`pivotphp`, `slim`, `mezzio`, `symfony`):

```ini
; php.ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=0
opcache.fast_shutdown=1
memory_limit=256M
```

```ini
; www.conf (Pool PHP-FPM)
pm = static
pm.max_children = 20
pm.max_requests = 10000
listen = 127.0.0.1:9000
```

### B. Nginx Reverse Proxy
Configuração de proxy local apontando para o PHP-FPM na porta 9000:
- `worker_processes auto;`
- `worker_connections 2048;`
- `keepalive_timeout 65;`

### C. PostgreSQL 17
- Base: Imagem oficial `postgres:17-alpine`
- Conexões: max_connections = 100
- Autenticação: usuário `bench`, base `voting_db`

### D. Redis 7
- Base: Imagem oficial `redis:7-alpine`
- Política de memória: `--maxmemory 128mb --maxmemory-policy allkeys-lru`

---

## 4. Script de Validação Automática

Antes de qualquer execução formal, execute:
```bash
./scripts/validate-environment.sh
```
O script confirma os cgroups ativos, as cotas de CPU/Memória, as ferramentas instaladas
e os hashes dos arquivos de schema/seed.
