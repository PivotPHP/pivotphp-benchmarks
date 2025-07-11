# Plano: Benchmark Cross-Framework Unificado para APIs
## Aplicável a Todos os Frameworks

**Objetivo**: Criar benchmark de API padronizado aplicável a todos os frameworks  
**Foco**: req/sec, latência, throughput comparáveis entre frameworks  
**Status**: Plano de implementação  

---

## 🎯 **Problema Identificado**

### Estrutura Atual (Inconsistente)
```
frameworks/
├── pivotphp-core/benchmarks/    # 11 benchmarks
├── slim4/benchmarks/            # 9 benchmarks
├── lumen/benchmarks/            # 2 benchmarks
├── flight/benchmarks/           # 2 benchmarks
└── symfony/benchmarks/          # 2 benchmarks
```

### Limitações
- **Benchmarks diferentes** entre frameworks
- **Métricas inconsistentes** (ops/sec vs req/sec)
- **Endpoints diferentes** testados
- **Impossível comparar** performance real

---

## 🚀 **Solução: Benchmark Unificado**

### 1. **Benchmark Padrão Cross-Framework**
Criar `unified-api-benchmark.php` para **todos os frameworks**:

```php
class UnifiedApiBenchmark 
{
    // Endpoints padronizados
    private array $endpoints = [
        'GET /',
        'GET /api/health',
        'GET /api/users',
        'GET /api/users/{id}',
        'POST /api/users',
        'PUT /api/users/{id}',
        'DELETE /api/users/{id}'
    ];
    
    // Métricas padronizadas
    public function measureApiPerformance(): array {
        return [
            'requests_per_second' => $rps,
            'avg_latency_ms' => $avgLatency,
            'p95_latency_ms' => $p95,
            'p99_latency_ms' => $p99,
            'error_rate_percent' => $errorRate,
            'memory_usage_mb' => $memoryUsage
        ];
    }
}
```

### 2. **Estrutura Padronizada**
```
frameworks/
├── pivotphp-core/
│   ├── benchmarks/unified-api-benchmark.php
│   ├── public/index.php (endpoints padronizados)
│   └── routes/api.php (mesmas rotas)
├── slim4/
│   ├── benchmarks/unified-api-benchmark.php
│   ├── public/index.php (endpoints padronizados)
│   └── routes/api.php (mesmas rotas)
├── lumen/
│   ├── benchmarks/unified-api-benchmark.php
│   ├── public/index.php (endpoints padronizados)
│   └── routes/web.php (mesmas rotas)
├── flight/
│   ├── benchmarks/unified-api-benchmark.php
│   ├── public/index.php (endpoints padronizados)
│   └── routes/api.php (mesmas rotas)
└── symfony/
    ├── benchmarks/unified-api-benchmark.php
    ├── public/index.php (endpoints padronizados)
    └── config/routes.yaml (mesmas rotas)
```

### 3. **Docker Integration**
```yaml
# docker-compose.yml
services:
  pivotphp-core:
    ports: ["8001:8001"]
    command: php -S 0.0.0.0:8001 -t /app/public
    
  slim4:
    ports: ["8002:8002"]
    command: php -S 0.0.0.0:8002 -t /app/public
    
  lumen:
    ports: ["8003:8003"]
    command: php -S 0.0.0.0:8003 -t /app/public
    
  flight:
    ports: ["8004:8004"]
    command: php -S 0.0.0.0:8004 -t /app/public
    
  symfony:
    ports: ["8005:8005"]
    command: php -S 0.0.0.0:8005 -t /app/public
```

---

## 🛠️ **Implementação Cross-Framework**

### Fase 1: Endpoints Padronizados
Cada framework implementa os **mesmos endpoints**:

```php
// Todos os frameworks devem implementar:
GET /                    # Health check
GET /api/health          # Health status
GET /api/users           # List users (JSON)
GET /api/users/{id}      # Get user by ID
POST /api/users          # Create user (JSON)
PUT /api/users/{id}      # Update user (JSON)
DELETE /api/users/{id}   # Delete user
```

### Fase 2: Benchmark Unificado
Mesmo arquivo de benchmark para todos:

```php
class UnifiedApiBenchmark 
{
    private string $baseUrl;
    private array $results = [];
    
    public function __construct(string $baseUrl, string $frameworkName) 
    {
        $this->baseUrl = $baseUrl;
        $this->frameworkName = $frameworkName;
    }
    
    public function runAllTests(): array 
    {
        $endpoints = [
            ['GET', '/'],
            ['GET', '/api/health'],
            ['GET', '/api/users'],
            ['GET', '/api/users/1'],
            ['POST', '/api/users'],
            ['PUT', '/api/users/1'],
            ['DELETE', '/api/users/1']
        ];
        
        foreach ($endpoints as [$method, $path]) {
            $this->results[] = $this->testEndpoint($method, $path);
        }
        
        return $this->results;
    }
    
    public function testEndpoint(string $method, string $path): array 
    {
        // Mesmo código de benchmark para todos os frameworks
        $latencies = [];
        $errors = 0;
        
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->makeRequest($method, $path);
            $latencies[] = $result['latency_ms'];
            if (!$result['success']) $errors++;
        }
        
        return [
            'framework' => $this->frameworkName,
            'method' => $method,
            'endpoint' => $path,
            'requests_per_second' => 1000 / $totalTime,
            'avg_latency_ms' => array_sum($latencies) / count($latencies),
            'p95_latency_ms' => $this->calculatePercentile($latencies, 95),
            'p99_latency_ms' => $this->calculatePercentile($latencies, 99),
            'error_rate_percent' => ($errors / 1000) * 100,
            'memory_usage_mb' => memory_get_peak_usage() / 1024 / 1024
        ];
    }
}
```

### Fase 3: Execução Automatizada
```bash
# Script para executar todos os frameworks
#!/bin/bash
echo "🚀 Cross-Framework API Benchmark"

# Start all servers
docker-compose up -d

# Test each framework
php scripts/unified-benchmark.php --framework=pivotphp-core --url=http://localhost:8001
php scripts/unified-benchmark.php --framework=slim4 --url=http://localhost:8002
php scripts/unified-benchmark.php --framework=lumen --url=http://localhost:8003
php scripts/unified-benchmark.php --framework=flight --url=http://localhost:8004
php scripts/unified-benchmark.php --framework=symfony --url=http://localhost:8005

# Generate comparison report
php scripts/generate-cross-framework-report.php
```

---

## 📊 **Resultado Esperado**

### Relatório Cross-Framework
```
🔥 Cross-Framework API Performance Comparison
============================================

Endpoint: GET /api/users
┌─────────────┬─────────────┬──────────────┬─────────────┬─────────────┐
│ Framework   │ Req/sec     │ Avg Latency  │ P95 Latency │ Error Rate  │
├─────────────┼─────────────┼──────────────┼─────────────┼─────────────┤
│ PivotPHP    │ 1,250       │ 2.1ms        │ 4.2ms       │ 0.0%        │
│ Slim 4      │ 890         │ 3.8ms        │ 7.1ms       │ 0.0%        │
│ Lumen       │ 720         │ 4.9ms        │ 9.2ms       │ 0.1%        │
│ Flight      │ 650         │ 5.4ms        │ 11.8ms      │ 0.0%        │
│ Symfony     │ 420         │ 8.1ms        │ 15.3ms      │ 0.2%        │
└─────────────┴─────────────┴──────────────┴─────────────┴─────────────┘

Endpoint: POST /api/users
┌─────────────┬─────────────┬──────────────┬─────────────┬─────────────┐
│ Framework   │ Req/sec     │ Avg Latency  │ P95 Latency │ Error Rate  │
├─────────────┼─────────────┼──────────────┼─────────────┼─────────────┤
│ PivotPHP    │ 1,100       │ 2.4ms        │ 4.8ms       │ 0.0%        │
│ Slim 4      │ 780         │ 4.2ms        │ 8.1ms       │ 0.0%        │
│ Lumen       │ 620         │ 5.5ms        │ 10.8ms      │ 0.1%        │
│ Flight      │ 580         │ 6.1ms        │ 13.2ms      │ 0.0%        │
│ Symfony     │ 350         │ 9.8ms        │ 18.7ms      │ 0.3%        │
└─────────────┴─────────────┴──────────────┴─────────────┴─────────────┘

📈 Overall Performance Summary:
- PivotPHP: 1,175 avg req/sec, 2.25ms avg latency
- Slim 4: 835 avg req/sec, 4.0ms avg latency
- Lumen: 670 avg req/sec, 5.2ms avg latency
- Flight: 615 avg req/sec, 5.75ms avg latency
- Symfony: 385 avg req/sec, 8.95ms avg latency
```

---

## 🚀 **Vantagens do Benchmark Unificado**

### 1. **Comparação Direta**
- Mesmos endpoints testados
- Mesmas métricas coletadas
- Mesmas condições de teste
- Mesma metodologia aplicada

### 2. **Métricas Relevantes**
- **Req/sec**: Throughput real de API
- **Latência**: Tempo de resposta real
- **Percentis**: P95, P99 para SLA
- **Error Rate**: Confiabilidade da API

### 3. **Automatização**
- Execução via Docker
- Scripts automatizados
- Relatórios padronizados
- CI/CD integration

### 4. **Aplicabilidade**
- Dados para capacity planning
- Comparação objetiva entre frameworks
- Métricas para SLA definition
- Base para decisões arquiteturais

---

## 📋 **Plano de Implementação**

### Semana 1: Endpoints Padronizados
- [ ] Criar endpoints padronizados para todos os frameworks
- [ ] Implementar mesmas rotas em cada framework
- [ ] Testar endpoints individualmente
- [ ] Configurar portas Docker

### Semana 2: Benchmark Unificado
- [ ] Criar `unified-api-benchmark.php`
- [ ] Implementar para todos os frameworks
- [ ] Testar execução cross-framework
- [ ] Validar métricas consistentes

### Semana 3: Automação e Relatórios
- [ ] Script de execução automatizada
- [ ] Geração de relatórios comparativos
- [ ] Integração com Docker Compose
- [ ] Documentação completa

---

## 🎯 **Resposta à Pergunta**

**"Este benchmark é aplicável aos testes que temos hoje para as apis em cross-framework?"**

### ❌ **Atualmente: NÃO**
- Frameworks têm benchmarks diferentes
- Métricas inconsistentes
- Endpoints não padronizados
- Impossível comparar diretamente

### ✅ **Com Implementação: SIM**
- Benchmark unificado para todos os frameworks
- Mesmas métricas (req/sec, latência)
- Mesmos endpoints testados
- Comparação direta e objetiva

### 🚀 **Solução Proposta**
Implementar o **benchmark cross-framework unificado** que permitirá:
1. Comparação direta entre frameworks
2. Métricas relevantes (req/sec, latência)
3. Automação via Docker
4. Relatórios padronizados

O benchmark que criei é o **primeiro passo** para um sistema unificado de benchmarks cross-framework aplicável a todos os frameworks do projeto.

---

*A implementação completa do benchmark cross-framework unificado resolverá definitivamente a questão da comparabilidade entre frameworks.*