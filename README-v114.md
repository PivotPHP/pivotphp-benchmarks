# PivotPHP v1.1.4 Benchmark Suite

🚀 **Ambiente de benchmarks atualizado para PivotPHP Core v1.1.4 Enhanced Edition**

Este projeto foi completamente atualizado para trabalhar com a versão mais recente do PivotPHP, incluindo sintaxe otimizada, melhorias de performance e ambiente Docker isolado.

## 🆕 Novidades da v1.1.4

- ✅ **Sintaxe atualizada** para PivotPHP Core v1.1.4
- ✅ **Ambiente Docker isolado** para testes consistentes
- ✅ **Benchmarks otimizados** com novos endpoints
- ✅ **Relatórios aprimorados** em JSON e Markdown
- ✅ **Configurações de performance** otimizadas
- ✅ **Compatibilidade total** com extensões (ORM, ReactPHP)

## 📋 Pré-requisitos

- Docker 20.0+
- Docker Compose 2.0+
- Make (opcional, mas recomendado)
- curl e jq (para testes)

## 🚀 Quick Start

### 1. Construir e Iniciar Ambiente

```bash
# Usando Makefile (recomendado)
make -f Makefile-v114 build
make -f Makefile-v114 up

# Ou usando Docker Compose diretamente
docker-compose -f docker-compose-v114.yml build
docker-compose -f docker-compose-v114.yml up -d
```

### 2. Verificar Status

```bash
# Verificar saúde dos containers
make -f Makefile-v114 health

# Ver status dos containers
make -f Makefile-v114 status
```

### 3. Executar Benchmarks

```bash
# Benchmark completo
make -f Makefile-v114 benchmark-all

# Benchmark apenas do Core
make -f Makefile-v114 benchmark-core

# Benchmark Phase 2 (Extensions)
make -f Makefile-v114 benchmark-phase2
```

## 🏗️ Arquitetura

### Containers Disponíveis

| Container | Porta | Descrição |
|-----------|-------|-----------|
| `pivotphp-core-v114` | 8080 | PivotPHP Core v1.1.4 |
| `pivotphp-orm-v114` | 8081 | PivotPHP + Cycle ORM |
| `pivotphp-reactphp-v114` | 8082 | PivotPHP + ReactPHP |
| `mysql-benchmark-v114` | 3306 | MySQL 8.0 para ORM |
| `redis-benchmark-v114` | 6379 | Redis 7 para cache |

### Endpoints de Teste

#### Core (Port 8080)
- `GET /health` - Health check
- `GET /meta` - Metadados da aplicação
- `GET /json/small` - JSON pequeno (< 1KB)
- `GET /json/medium` - JSON médio (1KB - 10KB)
- `GET /json/large` - JSON grande (10KB - 100KB)
- `GET /json/variable?size=N` - JSON variável
- `POST /json/echo` - Echo test
- `GET /stress/cpu?iterations=N` - Stress test CPU
- `GET /stress/memory?size=N` - Stress test Memory
- `GET /param/{id}` - Teste de parâmetros
- `GET /performance` - Teste de performance

## 📊 Executando Benchmarks

### Benchmarks Automatizados

```bash
# Phase 1: Core puro
docker exec pivotphp-core-v114 php benchmarks/run-v114.php

# Phase 2: Com extensões
docker exec pivotphp-orm-v114 php benchmarks/run-v114.php
```

### Benchmarks Manuais

```bash
# Teste de health
curl http://localhost:8080/health

# Teste de JSON pequeno
curl http://localhost:8080/json/small

# Stress test CPU
curl "http://localhost:8080/stress/cpu?iterations=10000"

# Teste de parâmetros
curl http://localhost:8080/param/123
```

## 🛠️ Comandos Úteis

### Desenvolvimento

```bash
# Instalar dependências
make -f Makefile-v114 install-deps

# Atualizar para v1.1.4
make -f Makefile-v114 update-deps

# Validar versões
make -f Makefile-v114 test-validation
```

### Debug e Logs

```bash
# Ver logs de todos os containers
make -f Makefile-v114 logs

# Ver logs específicos
make -f Makefile-v114 logs-core
make -f Makefile-v114 logs-orm
make -f Makefile-v114 logs-reactphp

# Acesso shell
make -f Makefile-v114 shell-core
make -f Makefile-v114 shell-orm
make -f Makefile-v114 shell-mysql
```

### Relatórios

```bash
# Gerar relatórios
make -f Makefile-v114 report

# Limpar relatórios antigos
make -f Makefile-v114 clean-reports
```

## 📈 Resultados Esperados

### Performance Baseline v1.1.4

A versão 1.1.4 Enhanced Edition deve demonstrar:

- **Health Check**: ~15,000+ req/s
- **JSON Small**: ~12,000+ req/s
- **JSON Medium**: ~8,000+ req/s
- **JSON Large**: ~3,000+ req/s
- **Routing**: ~10,000+ req/s
- **Latência P95**: < 10ms para a maioria dos endpoints

### Comparação com Extensões

- **Core**: Performance baseline
- **ORM**: ~70-80% da performance do Core (devido a operações de DB)
- **ReactPHP**: ~120-150% da performance do Core (runtime contínuo)

## 🐛 Troubleshooting

### Container não inicia

```bash
# Verificar logs
docker-compose -f docker-compose-v114.yml logs <container-name>

# Verificar saúde
docker-compose -f docker-compose-v114.yml ps
```

### Dependências desatualizadas

```bash
# Atualizar dependências
composer update pivotphp/core --with-dependencies --working-dir=phase1
composer update pivotphp/core --with-dependencies --working-dir=phase2
```

### Performance baixa

1. Verificar se OPcache está habilitado
2. Verificar recursos do Docker (CPU/Memory)
3. Verificar se há outros processos consumindo recursos

```bash
# Verificar métricas
make -f Makefile-v114 monitor
```

## 📁 Estrutura de Arquivos

```
pivotphp-benchmarks/
├── phase1/                     # PivotPHP Core puro
│   ├── public/index-v114.php  # Aplicação v1.1.4
│   ├── benchmarks/run-v114.php # Benchmark runner
│   └── Dockerfile-v114         # Container otimizado
├── phase2/                     # PivotPHP + Extensions
│   └── benchmarks/run-v114.php # Benchmark runner extensões
├── docker-compose-v114.yml    # Orquestração completa
├── Makefile-v114              # Comandos automatizados
└── README-v114.md             # Esta documentação
```

## 🔗 Links Úteis

- [PivotPHP Core v1.1.4](https://github.com/PivotPHP/pivotphp-core)
- [Documentação Oficial](https://docs.pivotphp.com)
- [Discord Comunidade](https://discord.gg/DMtxsP7z)

## 📄 Licença

Este projeto de benchmarks está sob a mesma licença do PivotPHP Core.

---

**✨ PivotPHP v1.1.4 Enhanced Edition - Performance que você pode confiar!**