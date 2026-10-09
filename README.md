# PivotPHP Benchmarks — API de Votação

Benchmark realista comparando frameworks PHP executando a **mesma API de votação**, em
**Docker com recursos idênticos e limitados**. Mede latência (p95/p99), throughput
sustentado, taxa de erro e eficiência de recursos.

> **Status**: Fase 1 — fundação (somente o framework **PivotPHP** implementado).

## Frameworks

| Framework | Diretório | Status |
|---|---|---|
| PivotPHP | `pivotphp/` | ✅ implementado |
| Slim 4 | `slim/` | pendente |
| Mezzio | `mezzio/` | pendente |
| Webman | `webman/` | pendente |
| Symfony (microkernel) | `symfony/` | pendente |

Cada framework é **autocontido**: `Dockerfile`, `docker/` (nginx/php configs), `app/`
(código da API), `database/` (schema + seed) e `docker-compose.yml` próprios. O ambiente de
cada framework é **isolado** (banco e cache próprios), e um framework é medido por vez.

## Contrato da API

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/health` | Health check (sem dependências) |
| `GET` | `/api/candidates` | Lista candidatos (cache Redis → banco) |
| `GET` | `/api/candidates/{id}` | Detalhe de um candidato |
| `POST` | `/api/votes` | Registra voto (valida CPF, voto duplicado, transação, invalida cache) |
| `GET` | `/api/votes/results` | Resultados agregados |
| `GET` | `/api/votes/results/{candidateId}` | Resultado de um candidato |
| `DELETE` | `/api/votes` | Limpa votos (teste) |

Regras:

- `POST /api/votes` recebe `{"candidateId": int, "cpf": string}`.
- CPF validado com dígitos verificadores.
- Voto duplicado (mesmo CPF) → `409`; candidato inexistente → `404`; entrada inválida → `422`.
- Resultados agregados via `COUNT` + `GROUP BY`, com cache Redis invalidado a cada voto.

## Estrutura

```
pivotphp-benchmarks/
├── pivotphp/              # framework autocontido (implementado)
│   ├── Dockerfile         # Nginx + PHP-FPM (idêntico entre frameworks)
│   ├── docker/            # nginx.conf, php.ini, www.conf (pm.max_children fixo)
│   ├── app/               # public/index.php + src/ (Controllers, Support)
│   ├── database/          # schema.sql + seed.sql
│   ├── composer.json
│   └── docker-compose.yml # postgres + redis + app (isolado)
├── slim/                  # (pendente)
├── mezzio/                # (pendente)
├── webman/                # (pendente)
├── symfony/               # (pendente)
└── k6/scripts/            # smoke.js (e futuros: load, spike, soak, mixed)
```

## Como rodar (PivotPHP)

```bash
cd pivotphp
docker compose up --build -d postgres redis app

# Smoke manual
curl http://localhost:8080/api/health
curl http://localhost:8080/api/candidates
curl -X POST http://localhost:8080/api/votes -H 'Content-Type: application/json' \
  -d '{"candidateId":1,"cpf":"11144477735"}'
curl http://localhost:8080/api/votes/results

# Smoke via k6
docker compose --profile loadtest run --rm k6 run /scripts/smoke.js
```

## Limites de recursos (justiça entre frameworks)

| Serviço | CPU | Memória |
|---|---|---|
| App PHP | 1.0 | 512M |
| PostgreSQL | 2.0 | 2G |
| Redis | 0.5 | 256M |

> `deploy.resources` vale em Swarm mode ou `docker compose --compatibility`. O FPM pool
> (`pm.max_children`) é idêntico entre frameworks.
