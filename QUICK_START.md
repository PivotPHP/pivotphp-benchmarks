# 🚀 Guia de Início Rápido - PivotPHP Benchmarks

## ⚡ Começando em 5 Minutos

### **Pré-requisitos**
```bash
# Verificar instalações necessárias
docker --version          # Docker 20.10+
docker-compose --version  # Docker Compose 1.29+
php --version             # PHP 8.1+
python3 --version         # Python 3.8+
```

### **Instalação Rápida**
```bash
# 1. Clonar repositório
git clone https://github.com/PivotPHP/pivotphp-benchmarks
cd pivotphp-benchmarks

# 2. Instalar dependências
make install-deps
python3 scripts/install-requirements.py

# 3. Validar ambiente
make validate
```

### **Primeiro Benchmark**
```bash
# Executar benchmark básico do PivotPHP
make benchmark-pivotphp

# Resultado esperado: ~5 minutos
# Arquivo gerado: results/raw/pivotphp-*.json
```

### **Primeiro Relatório**
```bash
# Gerar relatório automático
make report

# Visualizar resultado
ls results/reports/
cat results/reports/latest-report.md
```

## 🎯 Cenários Comuns

### **Cenário 1: Validação de Performance**
```bash
# Objetivo: Validar superioridade do PivotPHP
# Tempo: ~10 minutos

# Executar todos os frameworks
make benchmark-all

# Gerar comparação
python3 scripts/scoring-system.py \
  --input results/raw/ \
  --output results/reports/comparison.json \
  --verbose

# Resultado: Ranking completo com scores
```

### **Cenário 2: Análise de Negócio**
```bash
# Objetivo: Calcular ROI e impacto financeiro
# Tempo: ~3 minutos

# Executar análise específica
make business-analysis

# Gerar relatório executivo
python3 scripts/generate-report.py \
  --type executive \
  --input results/raw/pivotphp-core-latest.json \
  --output results/reports/executive-summary.md

# Resultado: Métricas financeiras e ROI
```

### **Cenário 3: Teste Específico**
```bash
# Objetivo: Testar cenário específico
# Tempo: ~2 minutos

# Apenas JSON performance
make benchmark-json

# Apenas middleware
make benchmark-middleware

# Apenas concorrência
make benchmark-concurrency
```

## 📊 Interpretação Rápida

### **Métricas Principais**
```
🎯 COMO LER OS RESULTADOS

Throughput (ops/sec):
├── > 1M = Excelente
├── 500K-1M = Bom
├── 100K-500K = Regular
└── < 100K = Ruim

Latência (ms):
├── < 1ms = Excelente
├── 1-5ms = Bom
├── 5-20ms = Regular
└── > 20ms = Ruim

Score Geral:
├── 4.0-5.0 = Líder
├── 3.0-4.0 = Competitivo
├── 2.0-3.0 = Adequado
└── < 2.0 = Problemático
```

### **Sinais de Sucesso**
- ✅ **PivotPHP em 1º lugar** no ranking
- ✅ **Score > 4.0** para PivotPHP
- ✅ **Vantagem > 2x** sobre concorrentes
- ✅ **Variação < 5%** entre execuções

## 🔧 Comandos Essenciais

### **Execução de Benchmarks**
```bash
# Básicos
make benchmark-pivotphp       # Apenas PivotPHP
make benchmark-comparative    # Apenas concorrentes
make benchmark-all           # Todos os frameworks

# Específicos
make benchmark-core          # PivotPHP Core
make benchmark-orm           # PivotPHP ORM
make benchmark-reactphp      # PivotPHP ReactPHP

# Avançados
make parallel-sequential-analysis  # Análise CPU warmup
make benchmark-concurrency         # Testes de concorrência
make stress-test                   # Testes de stress
```

### **Geração de Relatórios**
```bash
# Automático
make report                  # Relatório padrão

# Específicos
python3 scripts/generate-report.py --type technical \
  --input results/raw/pivotphp-core-latest.json \
  --output results/reports/technical.md

python3 scripts/generate-report.py --type comparative \
  --input results/reports/scoring.json \
  --output results/reports/comparative.md
```

### **Monitoramento**
```bash
# Status
make status                  # Status geral
make logs-all               # Logs de todos containers
make docker-ps              # Lista containers ativos

# Específicos
make logs-pivotphp-core     # Logs PivotPHP Core
make shell-pivotphp-core    # Shell do container
make db-shell               # Shell do MySQL
```

### **Limpeza**
```bash
make clean                  # Limpa resultados
make clean-all             # Limpa tudo (containers + results)
make reset                 # Reset completo
```

## 🎨 Personalização Rápida

### **Ajustar Configurações**
```bash
# Editar configurações principais
vim Makefile                # Comandos e parâmetros
vim docker-compose.yml      # Configuração containers
vim .env                    # Variáveis de ambiente
```

### **Customizar Benchmarks**
```bash
# Ajustar iterações
export BENCHMARK_ITERATIONS=50000
export WARMUP_ITERATIONS=5000

# Executar com configuração customizada
make benchmark-pivotphp
```

### **Modificar Relatórios**
```bash
# Editar templates
vim templates/report-template.md
vim templates/comparative-report-template.md

# Regenerar com template customizado
make report
```

## 🚨 Troubleshooting

### **Problemas Comuns**
```bash
# Containers não iniciam
make clean && make build

# Permissões
sudo chown -R $USER:$USER results/
chmod +x scripts/*.py

# Dependências Python
pip3 install jinja2 python-dateutil

# Espaço em disco
docker system prune -a
```

### **Validação do Ambiente**
```bash
# Verificar Docker
docker run hello-world

# Verificar Python
python3 -c "import jinja2; print('OK')"

# Verificar PHP
php -m | grep -E "(opcache|json)"

# Verificar Make
make --version
```

### **Logs de Debug**
```bash
# Logs detalhados
make logs-all > debug.log 2>&1

# Verificar erros específicos
make logs-pivotphp-core | grep -i error
make logs-mysql | grep -i error
```

## 📋 Checklist de Verificação

### **Antes de Executar**
- [ ] Docker e Docker Compose instalados
- [ ] Python 3.8+ com pip
- [ ] PHP 8.1+ com extensões necessárias
- [ ] Pelo menos 8GB RAM disponível
- [ ] Pelo menos 10GB espaço em disco

### **Após Execução**
- [ ] Resultados gerados em `results/raw/`
- [ ] Relatórios criados em `results/reports/`
- [ ] Logs sem erros críticos
- [ ] Métricas dentro do esperado

### **Validação de Resultados**
- [ ] PivotPHP com melhor performance
- [ ] Variação < 5% entre execuções
- [ ] Todos os frameworks testados
- [ ] Relatórios gerados corretamente

## 🔗 Próximos Passos

### **Aprofundamento**
- 📚 **[Documentação Completa](./docs/README.md)**
- 🔬 **[Metodologia](./docs/METHODOLOGY.md)**
- 📊 **[Métricas](./docs/BENCHMARK_METRICS.md)**
- 🔄 **[Comparação](./docs/CROSS_FRAMEWORK_COMPARISON.md)**

### **Casos Avançados**
- 🎯 **[Cenários Específicos](./docs/TEST_SCENARIOS.md)**
- 📋 **[Relatórios Customizados](./docs/REPORT_STANDARD.md)**
- 🎨 **[Personalização](./examples/customization/)**

### **Contribuição**
- 🐛 **[Issues](https://github.com/PivotPHP/pivotphp-benchmarks/issues)**
- 🔧 **[Pull Requests](https://github.com/PivotPHP/pivotphp-benchmarks/pulls)**
- 💡 **[Discussions](https://github.com/PivotPHP/pivotphp-benchmarks/discussions)**

---

**🎯 Dica**: Use `make help` para ver todos os comandos disponíveis a qualquer momento.

**📞 Suporte**: Para dúvidas, abra uma issue no GitHub ou entre em contato via Discord.

**⚡ Performance**: Resultados típicos: PivotPHP 2.5M ops/sec, 1º lugar no ranking com score 4.2/5.0.