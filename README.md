# PivotPHP Benchmarks 🧬⚡

> **Private Repository** - Isolated benchmark environment for accurate performance comparison

## 📋 Overview

This repository provides a comprehensive performance benchmarking suite for the PivotPHP ecosystem, featuring:
- **Revolutionary JSON Performance** - PivotPHP Core v1.1.1 achieves 10.2M+ ops/sec JSON throughput
- **Isolated framework environments** - Each framework runs in its own Docker container with separate dependencies
- **Fair comparison** - All frameworks implement identical REST API endpoints
- **Controlled testing conditions** - Consistent resource allocation (2 CPUs, 1GB RAM)
- **Automated execution and reporting** - Docker orchestration with result aggregation

## 🔥 Performance Highlights

### PivotPHP Core v1.1.1 - Revolutionary JSON Edition
- **Small JSON**: 7.75M ops/sec (569,545% improvement over v1.1.0)
- **Medium JSON**: 59K ops/sec (6,867% improvement over v1.1.0)
- **Large JSON**: 4.2K ops/sec (2,008% improvement over v1.1.0)
- **Total Throughput**: 10.2M+ ops/sec
- **Memory Efficiency**: 33% better than competitors

## 🏗️ New Architecture

### Framework Isolation
```
frameworks/
├── pivotphp-core/   # PivotPHP without ORM
├── pivotphp-orm/    # PivotPHP with Cycle ORM
├── slim4/           # Slim 4 Framework
├── lumen/           # Laravel Lumen
├── flight/          # Flight Framework
└── symfony/         # Symfony (micro configuration)
```

Each framework has:
- ✅ Own `composer.json` with isolated dependencies
- ✅ Dedicated Dockerfile with optimized PHP configuration
- ✅ Complete REST API implementation
- ✅ Identical benchmark scripts
- ✅ No dependency conflicts

## 🚀 Quick Start

### Run All Benchmarks
```bash
# Build all containers
make build

# Run complete benchmark suite
make benchmark-all

# Generate performance report
make report
```

### Run Specific Benchmarks
```bash
# PivotPHP Core vs ORM comparison
make benchmark-pivotphp

# Only PivotPHP Core (no ORM)
make benchmark-core

# Only PivotPHP with Cycle ORM
make benchmark-orm

# Comparative frameworks
make benchmark-comparative
```

### Framework Access
```bash
# Shell access
make shell-pivotphp-core
make shell-pivotphp-orm
make shell-slim4
make shell-lumen
make shell-flight
make shell-symfony

# Database access
make db-shell
make redis-cli
```

## 📊 Benchmark Types

### 🔥 Core Infrastructure Benchmarks

### 1. **Routing Performance** (`routing.php`)
- Route matching and dispatching
- 100+ routes with parameters
- Wildcard and regex patterns

### 2. **Middleware Pipeline** (`middleware.php`)
- 10 middleware layers
- Authentication middleware
- Request/response manipulation

### 3. **JSON API Serialization** (`json-api.php`)
- Small, medium, and large payloads
- Request parsing
- Response serialization

### 4. **Concurrency Testing** (`concurrency.php`)
- Multi-process concurrent execution
- Application creation under load
- Route registration performance
- Container resolution speed

### 5. **Database Operations** (`database.php`)
- Connection management
- Query execution (SELECT, INSERT, UPDATE, DELETE)
- Transaction handling
- Connection pooling simulation

### 6. **Memory Usage Analysis** (`memory.php`)
- Large object creation
- String concatenation performance
- Array operations
- Garbage collection impact

### 7. **Security Middleware** (`security.php`)
- Input sanitization
- CSRF protection
- Rate limiting
- XSS prevention
- JWT token validation

### 🌐 API-Focused Benchmarks

### 8. **HTTP Request/Response** (`http-performance.php`)
- Request parsing and validation
- Response creation and headers
- Content negotiation
- Large payload processing
- Status code handling

### 9. **REST API Endpoints** (`rest-api.php`)
- GET /users (list with pagination)
- GET /users/:id (single resource)
- POST /users (create resource)
- PUT /users/:id (update resource)
- PATCH /users/:id (partial update)
- DELETE /users/:id (delete resource)
- Complex query operations

### 10. **JSON Performance** (`json-performance.php`) - ⚡ REVOLUTIONARY v1.1.1
- **Small JSON**: 7.75M ops/sec encoding performance
- **Medium JSON**: 59K ops/sec with complex objects
- **Large JSON**: 4.2K ops/sec with nested structures
- **Total Throughput**: 10.2M+ ops/sec (Revolutionary Performance)
- Object pooling and buffer reuse optimization
- Memory-efficient JSON processing
- Unprecedented performance improvements
- Schema validation
- Error handling

### 11. **API Middleware Stack** (`api-middleware.php`)
- CORS middleware
- Rate limiting middleware
- Authentication middleware
- Validation middleware
- Logging middleware
- Compression middleware
- Complete middleware stack

### 12. **ORM Performance** (pivotphp-orm only)
- **Query Performance** (`orm-queries.php`)
  - Simple selects
  - Pagination
  - Joins and relations
  - Complex queries
- **Entity Hydration** (`orm-hydration.php`)
  - Single entity
  - Collections (10, 50, 100 entities)
  - Nested relations

## 📈 Key Metrics

Each benchmark measures:
- **Operations per second** (primary metric)
- **Memory usage** (MB)
- **Response times** (when applicable)
- **Peak memory consumption**

## 🔧 Configuration

### PHP Configuration (all containers)
```ini
opcache.enable=1
opcache.enable_cli=1
opcache.jit=1255
opcache.jit_buffer_size=128M
memory_limit=512M
```

### Resource Limits
- **CPU**: 2 cores per container
- **Memory**: 1GB per container
- **Consistent across all frameworks**

## 📊 Implementation Status

### Frameworks
- ✅ **PivotPHP Core** - Complete application without ORM
- ✅ **PivotPHP ORM** - Complete application with Cycle ORM
- ✅ **Slim 4** - PSR-7/PSR-15 microframework
- ✅ **Lumen** - Laravel's micro-framework
- ✅ **Flight** - Minimalist framework
- ✅ **Symfony** - Full framework in micro configuration

### Infrastructure Benchmarks
- ✅ **Routing** - All frameworks
- ✅ **Middleware** - All frameworks
- ✅ **JSON API** - All frameworks
- ✅ **Concurrency** - All frameworks
- ✅ **Database Operations** - All frameworks
- ✅ **Memory Analysis** - All frameworks
- ✅ **Security Middleware** - All frameworks

### API-Focused Benchmarks
- ✅ **HTTP Request/Response** - All frameworks
- ✅ **REST API Endpoints** - All frameworks
- ✅ **JSON Performance** - All frameworks
- ✅ **API Middleware Stack** - All frameworks

### ORM Benchmarks
- ✅ **ORM Queries** - PivotPHP ORM only
- ✅ **ORM Hydration** - PivotPHP ORM only

## 🎯 REST API Endpoints

All frameworks implement:
- `GET /` - Health check
- `GET /users` - List users (paginated)
- `GET /users/:id` - Get user
- `POST /users` - Create user
- `PUT /users/:id` - Update user
- `DELETE /users/:id` - Delete user
- `GET /users/:id/posts` - User's posts

## 🐳 Docker Architecture

```yaml
services:
  mysql:          # Database for ORM tests
  redis:          # Cache backend
  pivotphp-core:  # PivotPHP without ORM
  pivotphp-orm:   # PivotPHP with Cycle ORM
  slim4:          # Slim 4 Framework
  lumen:          # Laravel Lumen
  flight:         # Flight Framework
  symfony:        # Symfony Framework
  orchestrator:   # Benchmark coordinator
```

## 📝 Adding New Benchmarks

1. Create benchmark file in `frameworks/{framework}/benchmarks/`
2. Implement `BenchmarkInterface` or follow existing patterns
3. Add to `benchmarks/run.php` list
4. Ensure identical test scenarios across frameworks

## 🔒 Security Note

This is a **PRIVATE** repository to prevent gaming of benchmarks. Results are published to public documentation after review and validation.

## 🛠️ Development

### Local Testing
```bash
# Run benchmarks locally (without Docker)
cd frameworks/pivotphp-core
php benchmarks/routing.php --iterations=1000 --warmup=100
```

### Install Dependencies
```bash
make install-deps
```

### Clean Environment
```bash
make clean
```

## 📊 Sample Results

```
Framework: pivotphp-core
Benchmark: routing/match-and-dispatch
Operations/sec: 52,847
Memory used: 8.2 MB

Framework: pivotphp-orm
Benchmark: routing/match-and-dispatch
Operations/sec: 48,123 (91% of core)
Memory used: 12.4 MB
```

---

**Note**: All benchmarks are reproducible and verifiable. Each test includes source code and environment configuration.
