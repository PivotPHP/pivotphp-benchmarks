# Proposta de Melhoria dos Benchmarks - Foco em API Real
## Req/sec, Latência e Throughput

**Data**: 11 de Julho de 2025  
**Objetivo**: Implementar benchmarks de API real com métricas relevantes  
**Problema**: Benchmarks atuais focam em ops/sec internas, não em req/sec de API  

---

## 🔍 Análise dos Problemas Atuais

### ❌ **Limitações dos Benchmarks Atuais**
1. **Métricas Internas**: Focam em `ops/sec` de operações internas (JSON encoding, routing interno)
2. **Falta de HTTP Real**: Poucos benchmarks fazem requisições HTTP reais via cliente
3. **Métricas Confusas**: Mistura operações internas com performance de API
4. **Falta de Throughput**: Não mede req/sec, latência, conexões simultâneas
5. **Cenários Irreais**: Benchmarks não simulam uso real de APIs

### ✅ **Benchmarks com Potencial (Existentes)**
- `http-server.php` (ReactPHP) - já faz requisições HTTP reais via Guzzle
- `rest-api.php` - simula operações REST mas mede ops/sec internas
- `http-performance.php` - mede operações internas, não HTTP real

---

## 🎯 Novos Benchmarks Propostos

### 1. **HTTP API Throughput Benchmark**
```php
// frameworks/*/benchmarks/api-throughput.php
Métricas:
- Req/sec (GET, POST, PUT, DELETE)
- Latência média/p95/p99
- Throughput simultâneo (1, 10, 50, 100 conexões)
- Tempo de resposta por endpoint
```

### 2. **REST API Load Test**
```php
// frameworks/*/benchmarks/rest-load-test.php
Cenários:
- CRUD operations em sequência
- Paginação com large datasets
- Validação de entrada
- Serialização JSON complexa
Métricas: req/sec, latência, CPU, memória
```

### 3. **Concurrent API Benchmark**
```php
// frameworks/*/benchmarks/concurrent-api.php
Testes:
- 1-100 conexões simultâneas
- Keep-alive vs new connections
- Different payload sizes
- Error rate under load
Métricas: req/sec, connection handling, resource usage
```

### 4. **Real-World API Simulation**
```php
// frameworks/*/benchmarks/real-world-api.php
Cenários:
- User authentication flow
- Data fetching with relationships
- File upload/download
- WebSocket connections (ReactPHP)
Métricas: end-to-end req/sec, user experience
```

---

## 📊 Métricas de API Relevantes

### 🔥 **Métricas Primárias**
- **Requests/sec**: Throughput real da API
- **Latência**: Tempo de resposta (média, p95, p99)
- **Concurrent Users**: Usuários simultâneos suportados
- **Connection Handling**: Conexões/sec, keep-alive efficiency

### 📈 **Métricas Secundárias**
- **CPU Usage**: Uso de CPU por req/sec
- **Memory Per Request**: Memória por requisição
- **Error Rate**: Taxa de erro sob carga
- **Resource Efficiency**: Throughput por recurso usado

### 🎯 **Métricas de Negócio**
- **Time to First Byte (TTFB)**: Responsividade
- **Response Size Efficiency**: Payload optimization
- **Cache Hit Rate**: Eficiência de cache
- **Database Queries/Request**: Eficiência de queries

---

## 🏗️ Implementação Proposta

### Fase 1: Benchmarks HTTP Reais (Prioridade Alta)
```bash
# Criar benchmarks que fazem requisições HTTP reais
1. api-throughput.php         # Throughput básico GET/POST
2. rest-load-test.php         # CRUD operations load
3. concurrent-api.php         # Conexões simultâneas
4. latency-test.php           # Latência detalhada
```

### Fase 2: Cenários Realistas (Prioridade Média)
```bash
# Simular uso real de APIs
1. real-world-api.php         # Cenários de usuário real
2. database-api.php           # APIs com banco de dados
3. auth-api.php               # APIs com autenticação
4. file-upload-api.php        # Upload de arquivos
```

### Fase 3: Benchmarks Avançados (Prioridade Baixa)
```bash
# Benchmarks especializados
1. websocket-performance.php  # WebSocket throughput
2. streaming-api.php          # Streaming responses
3. graphql-api.php           # GraphQL performance
4. microservice-chain.php    # Microservices chain
```

---

## 🛠️ Estrutura Técnica

### Arquitetura de Benchmark HTTP
```php
class ApiThroughputBenchmark {
    private GuzzleHttp\Client $client;
    private string $baseUrl;
    private $serverProcess;
    
    public function measureThroughput(int $requests, int $concurrency): array {
        return [
            'requests_per_second' => $rps,
            'avg_latency_ms' => $avgLatency,
            'p95_latency_ms' => $p95Latency,
            'p99_latency_ms' => $p99Latency,
            'error_rate' => $errorRate,
            'total_requests' => $totalRequests,
            'concurrent_connections' => $concurrency
        ];
    }
}
```

### Servidor HTTP para Benchmarks
```php
// Cada framework precisa de um servidor HTTP real
1. Built-in PHP server: php -S 0.0.0.0:8000
2. ReactPHP server: server.php
3. Swoole server: swoole-server.php
4. Docker HTTP server: via nginx/apache
```

### Cliente HTTP para Testes
```php
// Usar Guzzle para fazer requisições reais
$client = new GuzzleHttp\Client([
    'timeout' => 10,
    'http_errors' => false,
    'verify' => false,
    'allow_redirects' => false
]);

// Concurrent requests
$promises = [];
for ($i = 0; $i < $concurrency; $i++) {
    $promises[] = $client->getAsync($url);
}
$responses = Promise\Utils::settle($promises)->wait();
```

---

## 📋 Plano de Implementação

### Semana 1: Benchmarks Básicos
- [ ] Criar `api-throughput.php` para todos os frameworks
- [ ] Implementar servidor HTTP para cada framework
- [ ] Testar métricas básicas: req/sec, latência

### Semana 2: Benchmarks Avançados
- [ ] Criar `concurrent-api.php` com múltiplas conexões
- [ ] Implementar `rest-load-test.php` com CRUD
- [ ] Adicionar métricas de resource usage

### Semana 3: Relatórios e Validação
- [ ] Criar relatórios focados em req/sec
- [ ] Validar benchmarks cross-framework
- [ ] Documentar métricas de API

---

## 🎯 Exemplos de Métricas Esperadas

### Benchmarks HTTP Reais (Estimativas)
```
Framework: PivotPHP Core v1.1.1
├── GET /api/users: 15,000 req/sec
├── POST /api/users: 12,000 req/sec
├── PUT /api/users/:id: 10,000 req/sec
├── DELETE /api/users/:id: 13,000 req/sec
├── Latência média: 2.5ms
├── Latência p95: 8.2ms
├── Concurrent users: 1,000
└── Error rate: 0.01%

Framework: Slim 4
├── GET /api/users: 8,000 req/sec
├── POST /api/users: 6,500 req/sec
├── Latência média: 4.1ms
├── Latência p95: 12.8ms
└── Concurrent users: 500
```

### Relatórios de API
```markdown
# API Performance Report
## Cross-Framework Comparison

| Framework | GET req/sec | POST req/sec | Avg Latency | P95 Latency | Max Concurrent |
|-----------|-------------|--------------|-------------|-------------|----------------|
| PivotPHP  | 15,000      | 12,000       | 2.5ms       | 8.2ms       | 1,000         |
| Slim 4    | 8,000       | 6,500        | 4.1ms       | 12.8ms      | 500           |
| Lumen     | 6,500       | 5,200        | 5.2ms       | 15.3ms      | 400           |
| Flight    | 4,200       | 3,800        | 7.8ms       | 22.1ms      | 200           |
```

---

## 🚀 Benefícios da Mudança

### Para Desenvolvedores
1. **Métricas Relevantes**: req/sec é mais útil que ops/sec internas
2. **Cenários Reais**: Benchmarks simulam uso real de APIs
3. **Comparação Justa**: Todos os frameworks testados com HTTP real
4. **Tomada de Decisão**: Dados para escolher framework para APIs

### Para o Projeto
1. **Credibilidade**: Benchmarks mais realistas e relevantes
2. **Documentação**: Métricas que fazem sentido para APIs
3. **Validação**: Testes mais próximos do uso real
4. **Competitive Analysis**: Comparação real com outros frameworks

---

## 🎯 Próximos Passos

### Imediatos
1. **Criar primeiro benchmark HTTP**: `api-throughput.php`
2. **Implementar servidor para cada framework**
3. **Testar métricas básicas**: req/sec, latência
4. **Validar cross-framework**

### Médio Prazo
1. **Expandir para benchmarks completos**
2. **Criar relatórios focados em API**
3. **Documentar métricas relevantes**
4. **Automatizar testes de carga**

---

*Esta proposta visa transformar os benchmarks em ferramentas mais úteis para avaliação real de performance de APIs, focando em métricas que realmente importam para desenvolvedores e arquitetos de sistemas.*