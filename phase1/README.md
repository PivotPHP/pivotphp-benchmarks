# FASE 1: Benchmark Científico PivotPHP Core Isolado

**Objetivo**: Estabelecer baseline confiável e reproduzível do PivotPHP Core através de metodologia científica rigorosa.

## 🎯 Estrutura da Fase 1

### Diretórios
```
phase1/
├── README.md                   # Este arquivo
├── docker/                    # Ambiente determinístico
│   ├── Dockerfile             # Container PivotPHP Core
│   ├── docker-compose.yml     # Orquestração
│   └── php.ini               # Configuração padronizada
├── src/                       # Código da aplicação teste
│   ├── app.php               # Bootstrap PivotPHP Core
│   ├── routes.php            # Endpoints de teste
│   └── middleware.php        # Pipeline de validação
├── benchmarks/                # Scripts de benchmark
│   ├── DataGenerator.php     # Gerador JSON determinístico
│   ├── IntegrityValidator.php # Validação de dados
│   ├── HttpBenchmark.php     # Framework HTTP real
│   ├── MetricsCollector.php  # Coleta de métricas sistema
│   └── StatisticalAnalyzer.php # Análise estatística
├── tests/                     # Testes de validação
│   ├── ReproducibilityTest.php
│   ├── IntegrityTest.php
│   └── BaselineTest.php
├── config/                    # Configurações
│   ├── benchmark.json        # Parâmetros de teste
│   └── seeds.json           # Seeds determinísticos
└── results/                   # Resultados
    ├── raw/                  # Dados brutos
    ├── processed/            # Análises
    └── reports/              # Relatórios finais
```

## 📊 Métricas Implementadas

### 1. Throughput HTTP Real
- Requisições/segundo por tamanho de JSON
- Medição end-to-end via cURL
- Warm-up e múltiplas execuções

### 2. Latência Precisa
- P50, P95, P99 percentis
- Tempo médio e desvio padrão
- Cold start vs warm

### 3. Integridade Total
- Hash MD5/SHA256 verification
- Validação estrutural JSON
- Detecção de corrupção

### 4. Recursos Sistema
- Memória RSS/VSZ
- CPU utilization
- I/O de rede

### 5. Reprodutibilidade
- CV < 5% entre execuções
- Determinismo com seeds
- Estabilidade temporal

## 🔬 Metodologia Científica

- **Ambiente**: Docker isolado, recursos fixos
- **Execuções**: Mínimo 100 por cenário
- **Validação**: Zero tolerância a erros
- **Análise**: Estatística completa
- **Documentação**: Auditável e reproduzível

## 🚀 Status

- [x] **Ambiente Docker** - Container determinístico configurado
- [x] **Geração de dados** - JSONs determinísticos com seeds fixos
- [x] **Validação de integridade** - Zero tolerância a corrupção
- [x] **Framework HTTP** - Requisições reais via Guzzle
- [x] **Coleta de métricas** - Monitoramento sistema completo
- [x] **Análise estatística** - Análise científica rigorosa
- [x] **Aplicação PivotPHP** - Endpoints de teste implementados
- [x] **Script de execução** - Automação completa
- [x] **Makefile** - Comandos de desenvolvimento

## 🎯 Como Usar

### Setup Rápido
```bash
# 1. Instalar dependências
make install

# 2. Setup completo
make dev-setup

# 3. Executar benchmark
make benchmark
```

### Comandos Principais
```bash
make help          # Ver todos os comandos
make build          # Construir container
make up             # Iniciar ambiente  
make test-all       # Testar endpoints
make benchmark      # Benchmark completo
make analyze        # Analisar resultados
make clean          # Limpar ambiente
```

### Validação Rápida
```bash
make benchmark-validate  # Verificar reprodutibilidade
make quick-metrics      # Métricas rápidas
make status            # Status do ambiente
```