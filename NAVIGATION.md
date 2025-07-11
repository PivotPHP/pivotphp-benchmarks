# 🧭 Navegação do Projeto - PivotPHP Benchmarks

## 📍 Mapa de Navegação

### **🏠 Pontos de Entrada**
- **[🚀 Início Rápido](./QUICK_START.md)** - Comece aqui se é novo no projeto
- **[🎯 Visão Geral](./PROJECT_OVERVIEW.md)** - Dashboard e status do projeto
- **[📚 Documentação](./docs/README.md)** - Índice completo da documentação
- **[🔧 Comandos](./Makefile)** - Todos os comandos make disponíveis

### **📊 Por Tipo de Usuário**

#### **👨‍💼 Executivos e Gestores**
```
🎯 FOCO: ROI, decisões estratégicas, impacto no negócio
├── 📊 Visão Geral do Projeto        → PROJECT_OVERVIEW.md
├── 💰 Análise de Impacto de Negócio → docs/BENCHMARK_METRICS.md#métricas-de-valor-real
├── 🏆 Ranking e Comparação          → docs/CROSS_FRAMEWORK_COMPARISON.md
├── 📈 Relatórios Executivos         → results/reports/executive-*
└── 🎯 Casos de Uso                  → docs/TEST_SCENARIOS.md#impacto-no-negócio
```

#### **👨‍💻 Desenvolvedores**
```
⚡ FOCO: Performance técnica, implementação, detalhes
├── 🚀 Guia de Início Rápido         → QUICK_START.md
├── 🔬 Metodologia Técnica           → docs/METHODOLOGY.md
├── 📊 Métricas Detalhadas           → docs/BENCHMARK_METRICS.md
├── 🧪 Cenários de Teste             → docs/TEST_SCENARIOS.md
├── 🔄 Comparação de Frameworks      → docs/CROSS_FRAMEWORK_COMPARISON.md
├── 🤖 Scripts de Automação          → scripts/
└── 📋 Templates                     → templates/
```

#### **🔬 Arquitetos e Tech Leads**
```
🏗️ FOCO: Arquitetura, decisões técnicas, validação
├── 🔬 Metodologia Científica        → docs/METHODOLOGY.md
├── 🏆 Análise Comparativa           → docs/CROSS_FRAMEWORK_COMPARISON.md
├── 📊 Métricas de Arquitetura       → docs/BENCHMARK_METRICS.md
├── 🎯 Cenários de Validação         → docs/TEST_SCENARIOS.md
├── 📋 Padrões de Relatório          → docs/REPORT_STANDARD.md
└── 🔄 Interpretação de Resultados   → docs/RESULTS_GUIDE.md
```

#### **🧪 QA e Testes**
```
🔍 FOCO: Validação, qualidade, reprodutibilidade
├── 🧪 Ambiente de Testes            → frameworks/
├── 🔬 Metodologia de Validação      → docs/METHODOLOGY.md
├── 📊 Métricas de Qualidade         → docs/BENCHMARK_METRICS.md
├── 🎯 Cenários de Teste             → docs/TEST_SCENARIOS.md
├── 🤖 Scripts de Automação          → scripts/
└── 📋 Relatórios de Validação       → results/reports/
```

## 🗂️ Índice por Categoria

### **📚 Documentação**
```
📖 DOCUMENTAÇÃO COMPLETA
├── 📋 Índice Geral                  → docs/README.md
├── 🔬 Metodologia                   → docs/METHODOLOGY.md
├── 🎯 Cenários de Teste             → docs/TEST_SCENARIOS.md
├── 📊 Métricas e Análise            → docs/BENCHMARK_METRICS.md
├── 📋 Padrões de Relatório          → docs/REPORT_STANDARD.md
├── 🔄 Comparação Cross-Framework    → docs/CROSS_FRAMEWORK_COMPARISON.md
└── 📖 Guia de Interpretação         → docs/RESULTS_GUIDE.md
```

### **🤖 Automação**
```
⚙️ SCRIPTS E AUTOMAÇÃO
├── 📊 Sistema de Scoring            → scripts/scoring-system.py
├── 📝 Gerador de Relatórios         → scripts/generate-report.py
├── 🔄 Executor de Benchmarks        → scripts/run-benchmarks-new.php
├── 📈 Análise Paralela/Sequencial   → scripts/parallel-benchmark-runner.php
├── 🛠️ Utilitários                   → scripts/install-requirements.py
└── 📊 Análise de Tendências         → scripts/generate-visual-report.php
```

### **🧪 Ambiente de Testes**
```
🏗️ INFRAESTRUTURA DE TESTES
├── 🚀 PivotPHP Core                 → frameworks/pivotphp-core/
├── 🗄️ PivotPHP ORM                  → frameworks/pivotphp-orm/
├── ⚡ PivotPHP ReactPHP              → frameworks/pivotphp-reactphp/
├── 🔷 Slim 4                        → frameworks/slim4/
├── 🔶 Lumen                         → frameworks/lumen/
├── 🔸 Flight                        → frameworks/flight/
├── 🔹 Symfony                       → frameworks/symfony/
├── 🐳 Docker Compose                → docker-compose.yml
└── 🔧 Componentes Compartilhados    → shared/
```

### **📊 Resultados**
```
📈 RESULTADOS E ANÁLISES
├── 📊 Dados Brutos                  → results/raw/
├── 📋 Dados Processados             → results/processed/
├── 📝 Relatórios Gerados            → results/reports/
├── 📈 Análises Comparativas         → results/comparative-*
└── 🎯 Dashboards                    → results/dashboards/
```

## 🔍 Busca Rápida

### **Por Palavra-Chave**
```bash
# Buscar na documentação
grep -r "performance" docs/
grep -r "benchmark" docs/
grep -r "ROI" docs/

# Buscar nos scripts
grep -r "scoring" scripts/
grep -r "report" scripts/

# Buscar nos resultados
find results/ -name "*pivotphp*"
find results/ -name "*comparative*"
```

### **Por Tipo de Arquivo**
```bash
# Documentação
find . -name "*.md" | head -10

# Scripts Python
find . -name "*.py" | head -10

# Scripts PHP
find . -name "*.php" | head -10

# Configurações
find . -name "*.json" -o -name "*.yml" -o -name "*.yaml" | head -10
```

## 🛣️ Fluxos de Trabalho

### **🚀 Fluxo: Primeiro Uso**
```
1. README.md → 2. QUICK_START.md → 3. make benchmark-pivotphp → 4. results/reports/
```

### **📊 Fluxo: Análise Completa**
```
1. PROJECT_OVERVIEW.md → 2. docs/METHODOLOGY.md → 3. make benchmark-all → 4. scripts/scoring-system.py → 5. docs/RESULTS_GUIDE.md
```

### **💼 Fluxo: Apresentação Executiva**
```
1. PROJECT_OVERVIEW.md → 2. docs/BENCHMARK_METRICS.md → 3. results/reports/executive-summary.md → 4. docs/CROSS_FRAMEWORK_COMPARISON.md
```

### **🔬 Fluxo: Validação Técnica**
```
1. docs/METHODOLOGY.md → 2. docs/TEST_SCENARIOS.md → 3. frameworks/pivotphp-core/ → 4. make validate → 5. docs/RESULTS_GUIDE.md
```

## 🎯 Atalhos Úteis

### **Comandos Frequentes**
```bash
# Status rápido
make status

# Benchmark básico
make benchmark-pivotphp

# Relatório rápido
make report

# Limpeza
make clean

# Help
make help
```

### **Arquivos Importantes**
```bash
# Configuração principal
vim Makefile

# Configuração Docker
vim docker-compose.yml

# Resultados recentes
ls -la results/raw/ | head -5

# Relatórios recentes
ls -la results/reports/ | head -5
```

## 🔗 Links Externos

### **Repositórios Relacionados**
- 🌐 **[PivotPHP Core](https://github.com/PivotPHP/pivotphp-core)**
- 🗄️ **[PivotPHP Cycle ORM](https://github.com/PivotPHP/pivotphp-cycle-orm)**
- ⚡ **[PivotPHP ReactPHP](https://github.com/PivotPHP/pivotphp-reactphp)**
- 📚 **[Documentação](https://github.com/PivotPHP/website)**

### **Comunidade**
- 💬 **[Discord](https://discord.gg/DMtxsP7z)**
- 🐦 **[Twitter](https://twitter.com/pivotphp)**
- 📧 **[Discussions](https://github.com/PivotPHP/pivotphp-benchmarks/discussions)**

### **Pacotes**
- 📦 **[Core - Packagist](https://packagist.org/packages/pivotphp/core)**
- 📦 **[ORM - Packagist](https://packagist.org/packages/pivotphp/cycle-orm)**
- 📦 **[ReactPHP - Packagist](https://packagist.org/packages/pivotphp/reactphp)**

## 📱 Navegação Mobile

### **Visualização Rápida**
```
📱 MOBILE FRIENDLY
├── 📊 PROJECT_OVERVIEW.md     # Dashboard visual
├── 🚀 QUICK_START.md          # Guia prático
├── 📋 Makefile                # Comandos essenciais
└── 📈 results/reports/latest-* # Últimos resultados
```

### **Comandos Essenciais**
```bash
# Status em uma linha
make status | head -3

# Resultado mais recente
ls -t results/reports/ | head -1

# Performance atual
grep -E "score|rank" results/reports/latest-* | head -5
```

## 🧭 Navegação Contextual

### **Se você está procurando...**

**🎯 Como começar rapidamente**
→ [QUICK_START.md](./QUICK_START.md)

**📊 Status atual do projeto**
→ [PROJECT_OVERVIEW.md](./PROJECT_OVERVIEW.md)

**🔬 Como funciona a metodologia**
→ [docs/METHODOLOGY.md](./docs/METHODOLOGY.md)

**💰 Qual o valor de negócio**
→ [docs/BENCHMARK_METRICS.md](./docs/BENCHMARK_METRICS.md)

**🏆 Como PivotPHP se compara**
→ [docs/CROSS_FRAMEWORK_COMPARISON.md](./docs/CROSS_FRAMEWORK_COMPARISON.md)

**📋 Como interpretar resultados**
→ [docs/RESULTS_GUIDE.md](./docs/RESULTS_GUIDE.md)

**🤖 Como automatizar testes**
→ [scripts/](./scripts/)

**🧪 Como os testes funcionam**
→ [frameworks/](./frameworks/)

**📊 Onde estão os resultados**
→ [results/](./results/)

**🎨 Como personalizar**
→ [templates/](./templates/)

---

**🎯 Dica**: Use Ctrl+F (ou Cmd+F) para buscar rapidamente em qualquer documento.

**📞 Ajuda**: Se não encontrar o que precisa, abra uma issue no GitHub ou pergunte no Discord.

**⚡ Atalho**: `make help` mostra todos os comandos disponíveis a qualquer momento.