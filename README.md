# HelixPHP Benchmarks 🧬⚡

> **Private Repository** - Controlled benchmark environment for accurate performance comparison

## 📋 Overview

This repository centralizes all performance benchmarks for the HelixPHP ecosystem, providing:
- Isolated benchmark environment using Docker
- Controlled testing conditions for accurate results
- Comparative analysis with other PHP microframeworks
- Automated benchmark execution and reporting

## 🎯 Project Goals

1. **Migrate existing benchmarks** from individual repositories
2. **Standardize benchmark methodology** across all tests
3. **Create comparative benchmarks** with other frameworks
4. **Generate automated performance reports** for documentation

## 📊 Migration Plan

### Phase 1: Infrastructure Setup
- [ ] Create Docker-based benchmark environment
- [ ] Set up PHP 8.1, 8.2, and 8.3 test containers
- [ ] Configure OPcache and JIT settings for optimal performance
- [ ] Implement benchmark runner with consistent hardware allocation

### Phase 2: Migrate Core Benchmarks
#### From helixphp-core:
- [ ] Router performance benchmarks
  - Route matching speed
  - Parameter extraction
  - Wildcard routes
- [ ] Middleware pipeline benchmarks
  - Single middleware execution
  - Chained middleware (5, 10, 20 middlewares)
  - Conditional middleware
- [ ] Request/Response benchmarks
  - PSR-7 message creation
  - JSON encoding/decoding
  - Large payload handling
- [ ] Container benchmarks
  - Service resolution speed
  - Dependency injection performance
  - Singleton vs transient services

### Phase 3: Migrate ORM Benchmarks
#### From helixphp-cycle-orm:
- [ ] Query builder performance
  - Simple SELECT queries
  - Complex JOIN operations
  - Bulk INSERT operations
- [ ] Entity hydration benchmarks
  - Single entity
  - Collections (100, 1000, 10000 entities)
  - Nested relations
- [ ] Transaction benchmarks
  - Single operation transactions
  - Bulk operation transactions
  - Rollback performance

### Phase 4: Comparative Framework Benchmarks
#### Implement identical operations for:
- [ ] **Slim 4**
  - Basic routing
  - Middleware execution
  - JSON API responses
  - Database operations (with Eloquent)
- [ ] **Lumen**
  - Same test suite as Slim 4
- [ ] **Flight**
  - Basic microframework operations
- [ ] **Symfony Flex** (minimal setup)
  - Routing and controllers
  - Service container
  - HTTP kernel

### Phase 5: Real-World Scenarios
- [ ] REST API endpoint benchmarks
  - GET /users (list with pagination)
  - POST /users (create with validation)
  - PUT /users/:id (update)
  - DELETE /users/:id
- [ ] Authentication flow
  - JWT token generation
  - Token validation middleware
  - Protected route access
- [ ] File upload handling
  - Small files (< 1MB)
  - Large files (10-100MB)
  - Multiple file uploads

## 🏗️ Project Structure

```
helixphp-benchmarks/
├── docker/
│   ├── php81/
│   │   └── Dockerfile
│   ├── php82/
│   │   └── Dockerfile
│   └── php83/
│       └── Dockerfile
├── benchmarks/
│   ├── helix-core/
│   │   ├── routing/
│   │   ├── middleware/
│   │   ├── container/
│   │   └── http/
│   ├── helix-orm/
│   │   ├── queries/
│   │   ├── hydration/
│   │   └── transactions/
│   └── comparative/
│       ├── slim4/
│       ├── lumen/
│       ├── flight/
│       └── symfony-flex/
├── results/
│   ├── raw/
│   ├── processed/
│   └── reports/
├── scripts/
│   ├── run-benchmarks.sh
│   ├── process-results.php
│   └── generate-report.php
├── docker-compose.yml
├── Makefile
└── README.md
```

## 🔧 Benchmark Standards

### Environment Configuration
```yaml
# docker-compose.yml
services:
  benchmark-php81:
    build: ./docker/php81
    environment:
      - PHP_MEMORY_LIMIT=512M
      - OPCACHE_ENABLE=1
      - OPCACHE_JIT=1255
      - OPCACHE_JIT_BUFFER_SIZE=128M
    cpus: 2
    mem_limit: 1g
```

### Benchmark Criteria
1. **Warm-up phase**: 1000 iterations before measurement
2. **Measurement phase**: 10,000 iterations minimum
3. **Multiple runs**: 5 runs, use median value
4. **Metrics collected**:
   - Operations per second
   - Memory usage (peak and average)
   - Response time (p50, p95, p99)
   - CPU usage percentage

### Example Benchmark Implementation
```php
<?php
// benchmarks/helix-core/routing/simple-route.php

use Helix\Core\Application;
use Helix\Benchmark\Timer;

$app = new Application();

// Setup routes
for ($i = 0; $i < 100; $i++) {
    $app->get("/route-{$i}", fn() => ['id' => $i]);
}

// Warm-up
for ($i = 0; $i < 1000; $i++) {
    $app->handle("/route-50");
}

// Benchmark
$timer = new Timer();
$timer->start();

for ($i = 0; $i < 10000; $i++) {
    $app->handle("/route-" . rand(0, 99));
}

$timer->stop();
$timer->report();
```

## 📈 Reporting Format

### JSON Output
```json
{
  "benchmark": "routing/simple-route",
  "framework": "helixphp-core",
  "version": "1.0.0",
  "php_version": "8.2.0",
  "timestamp": "2024-01-06T10:00:00Z",
  "results": {
    "operations_per_second": 52847,
    "memory_peak_mb": 8.2,
    "memory_avg_mb": 7.8,
    "response_time_ms": {
      "p50": 0.018,
      "p95": 0.025,
      "p99": 0.032
    },
    "cpu_usage_percent": 78.5
  }
}
```

### Markdown Report Template
```markdown
# Benchmark Report - [Date]

## Executive Summary
- HelixPHP: 52,847 ops/sec (baseline)
- Slim 4: 28,123 ops/sec (53% of baseline)
- Lumen: 15,234 ops/sec (29% of baseline)

## Detailed Results
[Automated charts and tables]
```

## 🚀 Usage

```bash
# Run all benchmarks
make benchmark-all

# Run specific framework benchmarks
make benchmark-helix
make benchmark-comparative

# Generate reports
make report

# Clean results
make clean
```

## 📝 TODO

- [ ] Set up GitHub Actions for automated benchmarking
- [ ] Create benchmark visualization dashboard
- [ ] Add WebSocket benchmark scenarios
- [ ] Implement memory leak detection tests
- [ ] Add concurrency stress tests
- [ ] Create benchmark regression detection

## 🔒 Security Note

This repository is **PRIVATE** to prevent gaming of benchmarks. Results are published to the public documentation site after review.

## 🤝 Contributing

Internal use only. For benchmark suggestions or issues, please contact the core team.

---

**Note**: All benchmarks must be reproducible and verifiable. Each test includes its source code and environment configuration.