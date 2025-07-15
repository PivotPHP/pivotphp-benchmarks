# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the PivotPHP benchmarking suite - a comprehensive performance testing environment for the PivotPHP ecosystem against other PHP microframeworks. It uses an isolated Docker-based structure where each framework has its own directory, dependencies, and container to ensure fair and conflict-free comparisons.

**Current Status**: ✅ Fully operational environment with validated cross-framework benchmarks
**Last Validation**: July 11, 2025 - All 10 containers running stably
**Performance Validated**: PivotPHP Core v1.1.4 Enhanced Edition with latest improvements

## Development Dependencies

Install all framework dependencies:
```bash
make install-deps        # Install composer dependencies for all frameworks
composer install         # Install root project dependencies
```

## Benchmark Validation

Validate your setup before running benchmarks:
```bash
make validate            # Check docker-compose configuration
docker-compose config    # Verify compose file syntax
```

## Essential Commands

### Docker Environment (Fully Operational)
```bash
# Build all Docker containers
make build                      # Build all 10 containers in parallel

# Environment management
make up                         # Start benchmark environment (10 containers)
make down                       # Stop benchmark environment
make validate                   # Validate docker-compose configuration
make clean                      # Clean all results and containers

# Core benchmarking commands
make benchmark-all              # All benchmarks on all frameworks
make benchmark-pivotphp         # All PivotPHP variants (core + orm + reactphp)
make benchmark-core             # Only PivotPHP Core (without ORM)
make benchmark-orm              # Only PivotPHP with Cycle ORM
make benchmark-reactphp         # Only PivotPHP with ReactPHP (continuous runtime)
make benchmark-comparative      # Slim4, Lumen, Flight, Symfony

# Advanced benchmarking
make benchmark-matrix           # Run across PHP 8.1, 8.2, 8.3
make benchmark-concurrency      # Run concurrency benchmarks
make parallel-sequential-analysis # Complete parallel→sequential analysis

# Report generation
make report                     # Generate comprehensive reports

# Container access (for development/debugging)
make shell-pivotphp-core        # Shell access to PivotPHP Core container
make shell-pivotphp-orm         # Shell access to PivotPHP ORM container
make shell-pivotphp-reactphp    # Shell access to PivotPHP ReactPHP container
make shell-slim4                # Shell access to Slim 4 container
make shell-lumen                # Shell access to Lumen container
make shell-flight               # Shell access to Flight container
make shell-symfony              # Shell access to Symfony container

# Infrastructure access
make db-shell                   # Access MySQL shell (benchmark/benchmark)
make redis-cli                  # Access Redis CLI
make logs-all                   # View all container logs
```

### Direct Benchmark Execution (Alternative to Docker)
```bash
# Primary runner scripts
php scripts/run-benchmarks-new.php --all
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,pivotphp-orm,pivotphp-reactphp
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,slim4 --iterations=50000

# Specialized testing scripts
php scripts/run-benchmarks.php                    # Legacy runner
php scripts/run-concurrency-benchmarks.php       # Concurrency tests
php scripts/stress-test-all-frameworks.php       # Stress testing
php scripts/parallel-benchmark-runner.php        # Parallel + sequential analysis
php scripts/docker-benchmark-runner.php          # Docker-based execution

# Report generation
php scripts/generate-report.php                   # Basic HTML report
php scripts/generate-comparative-report.php       # Framework comparison
php scripts/generate-final-report.php             # Publication-ready report
php scripts/enterprise-performance-report.php     # Enterprise analysis
```

### Local Development (without Docker)
```bash
# Test individual frameworks locally
cd frameworks/pivotphp-core
php benchmarks/simple-realistic-benchmark.php    # Validated benchmark
php benchmarks/routing.php --iterations=1000 --warmup=100

# Run specific benchmark types
php benchmarks/middleware.php
php benchmarks/json-api.php
php benchmarks/http-performance.php
php benchmarks/rest-api.php

# For ORM-enabled frameworks
cd frameworks/pivotphp-orm  
php benchmarks/orm-queries.php
php benchmarks/orm-hydration.php
php benchmarks/database.php

# For ReactPHP continuous runtime
cd frameworks/pivotphp-reactphp
php benchmarks/routing.php
php benchmarks/http-server.php                   # Real HTTP server benchmark
php benchmarks/async-performance.php
php benchmarks/continuous-runtime.php

# Start ReactPHP server manually
php server.php                                   # Start server on http://0.0.0.0:8000

# Quick validation tests
docker-compose exec pivotphp-core php benchmarks/simple-realistic-benchmark.php
docker-compose exec slim4 php benchmarks/routing.php
docker-compose exec lumen php benchmarks/routing.php
docker-compose exec flight php benchmarks/routing.php
```

## Code Architecture

### Isolated Framework Structure
```
frameworks/
├── pivotphp-core/          # PivotPHP without ORM
│   ├── composer.json       # Only pivotphp/core dependency
│   ├── Dockerfile          # Optimized container
│   ├── public/             # Entry points
│   ├── src/                # Application code
│   └── benchmarks/         # Benchmark scripts
│       ├── routing.php
│       ├── middleware.php
│       └── json-api.php
│
├── pivotphp-orm/           # PivotPHP with Cycle ORM
│   ├── composer.json       # pivotphp/core + pivotphp/cycle-orm
│   ├── Dockerfile          # Includes PDO extensions
│   └── benchmarks/
│       ├── routing.php
│       ├── orm-queries.php
│       └── orm-hydration.php
│
├── pivotphp-reactphp/      # PivotPHP with ReactPHP (Continuous Runtime)
│   ├── composer.json       # pivotphp/core + pivotphp/reactphp + guzzlehttp
│   ├── Dockerfile          # Includes sockets extension
│   ├── server.php          # ReactPHP server script
│   ├── bootstrap/app.php   # Application bootstrap with service providers
│   ├── src/Providers/      # Service providers (App, Route, ReactPHP)
│   ├── src/Controllers/    # Controllers including BenchmarkController
│   └── benchmarks/         # ReactPHP-specific benchmarks
│       ├── routing.php
│       ├── http-server.php     # Real HTTP requests benchmark
│       ├── async-performance.php
│       └── continuous-runtime.php
│
├── slim4/                  # Slim 4 Framework
├── lumen/                  # Laravel Lumen
├── flight/                 # Flight Framework
└── symfony/                # Symfony Flex
```

### Shared Components
- **BenchmarkInterface** (`shared/Interfaces/BenchmarkInterface.php`): Standard interface all benchmarks implement
- **Centralized Runner** (`scripts/run-benchmarks-new.php`): Orchestrates benchmark execution across containers
- **Result Storage** (`results/raw/`): JSON output from each benchmark run
- **Report Generation** (`scripts/generate-*-report.php`): Various report generators for HTML and Markdown
- **Base Classes** (`src/BenchmarkBase.php`, `src/Timer.php`): Common utilities for benchmark implementation

### Result Organization
```
results/
├── reports/           # HTML and Markdown reports
│   ├── executive-summary-2025-07-11.md
│   ├── technical-report-2025-07-11.md
│   └── pivotphp-performance-analysis-2025-07-11.json
├── *.json            # Individual test results (validated)
└── *.md              # Summary reports (current)
```

### Current Validated Results (July 2025)
- **benchmark-environment-validation-2025-07-11.json** - Infrastructure validation
- **cross-framework-benchmark-report-2025-07-11.md** - Cross-framework comparison
- **technical-performance-report-2025-07-11.md** - Technical analysis
- **enterprise-performance-report-2025-07-11-00-09-44.json** - Enterprise metrics
- **simple-realistic-benchmark-2025-07-11-02-47-01.json** - Realistic benchmarks

### Testing Infrastructure
- **MySQL Database**: For ORM performance testing (username/password: benchmark/benchmark)
- **Redis Cache**: For caching benchmark scenarios
- **Resource Limits**: All containers limited to 2 CPUs, 1GB RAM for fairness
- **PHP Configuration**: OPcache + JIT enabled across all frameworks

### Key Design Decisions

1. **Complete Isolation**: Each framework has its own:
   - Composer dependencies (no conflicts)
   - Docker image (optimized for that framework)
   - Bootstrap and configuration
   - Benchmark implementations

2. **Fair Comparison**:
   - All containers have identical resource limits (2 CPUs, 1GB RAM)
   - Same OPcache and JIT configuration
   - Identical benchmark scenarios across frameworks

3. **PivotPHP Variants Comparison**:
   - **Core**: Pure framework without extensions (baseline performance)
   - **ORM**: Framework + Cycle ORM (database operations)
   - **ReactPHP**: Framework + continuous runtime (async/persistent state)
   - Separate projects to measure performance impact of each extension
   - All use identical application structure for fair comparison

### Adding New Benchmarks

1. Create benchmark file in `frameworks/{framework}/benchmarks/`
2. Implement `BenchmarkInterface` or follow existing pattern:
   ```php
   class NewBenchmark implements BenchmarkInterface {
       public function run(int $iterations = 10000, int $warmup = 1000): array
       public function getName(): string
       public function getDescription(): string
   }
   ```
3. Ensure identical test scenarios across all frameworks
4. Add to framework's `benchmarks/run.php` if using batch execution
5. Update centralized runner script if needed

### Benchmark Standards
- **Warmup**: 1000 iterations before measurement (configurable)
- **Measurement**: 10,000+ iterations (configurable)
- **Metrics**: Operations/sec, memory usage, response times
- **Environment**: OPcache+JIT enabled, consistent PHP settings
- **Output Format**: JSON with standardized structure for automation

### Logging and Debugging
```bash
# View container logs
make logs-pivotphp-core
make logs-all

# Access individual log files  
tail -f logs/pivotphp-core.log
tail -f logs/pivotphp-orm.log
```

## Comprehensive Benchmark Suite

### 🔥 Core Infrastructure Benchmarks (All Frameworks)
- **routing.php**: Route matching and dispatching performance
- **middleware.php**: Middleware pipeline execution
- **json-api.php**: JSON serialization/deserialization
- **concurrency.php**: Multi-request concurrent handling
- **database.php**: Database operations simulation
- **memory.php**: Memory usage profiling and leak detection
- **security.php**: Security middleware and input validation

### 🌐 API-Focused Benchmarks (Production-Ready)
- **http-performance.php**: HTTP request/response processing, headers, content negotiation
- **rest-api.php**: Complete REST operations (GET, POST, PUT, PATCH, DELETE, pagination)
- **json-performance.php**: JSON processing (small/medium/large datasets, nested structures)
- **api-middleware.php**: Production middleware stack (CORS, auth, rate limiting, compression)

### ⚙️ Framework-Specific Benchmarks

#### PivotPHP Core v1.1.4 (Enhanced Edition)
- **Container resolution**: Dependency injection performance (584K+ ops/sec)
- **PSR-7 optimization**: Request/response object creation
- **High-performance mode**: Advanced optimization features
- **Revolutionary JSON Performance**: 10.2M+ ops/sec total JSON throughput
- **Small JSON**: 7.75M ops/sec (569,545% improvement over v1.1.0)
- **Medium JSON**: 59K ops/sec (6,867% improvement over v1.1.0)
- **Large JSON**: 4.2K ops/sec (2,008% improvement over v1.1.0)

#### PivotPHP ORM v1.0.1 (with Cycle ORM)
- **orm-queries.php**: Database query performance
- **orm-hydration.php**: Entity hydration and relationship loading
- **ORM model operations**: Entity management (327K+ ops/sec)
- **Unit of Work patterns**: Change tracking and persistence

#### PivotPHP ReactPHP v0.1.0 (Continuous Runtime)
- **async-performance.php**: Event loop and promise operations
- **continuous-runtime.php**: Persistent state advantages (1.27M+ ops/sec)
- **http-server.php**: Real HTTP server with ReactPHP
- **WebSocket simulation**: Real-time connection handling

### 🚀 Performance Testing Categories

#### Infrastructure Tests
- **Concurrency**: 4-20 concurrent operations per test
- **Memory Management**: Usage tracking, leak detection, garbage collection
- **Security**: Input sanitization, XSS protection, SQL injection prevention
- **Database**: Connection pooling, query optimization, transaction handling

#### API-Specific Tests  
- **HTTP Protocol**: Request parsing, response creation, status codes, headers
- **REST Operations**: CRUD operations with validation, pagination, filtering
- **JSON Processing**: Small objects, large datasets, nested structures, schema validation
- **Middleware Stack**: CORS, authentication, rate limiting, logging, compression

#### Stress Testing
- **High-load scenarios**: 10,000+ iterations per test
- **Continuous runtime**: Long-running process simulation
- **Memory pressure**: Large dataset processing
- **Concurrent users**: Multi-client simulation

## Report Generation

```bash
# Generate comprehensive reports
make report                                    # All reports
php scripts/generate-report.php               # Basic HTML report
php scripts/generate-comparative-report.php   # Framework comparison
php scripts/generate-final-report.php         # Publication-ready report
php scripts/generate-visual-report.php        # Charts and graphs
```

## Current Implementation Status

Framework isolation structure is complete with:
- ✅ Separate directories and dependencies for each framework
- ✅ Docker orchestration via docker-compose
- ✅ Centralized benchmark runner with multiple execution strategies  
- ✅ Comprehensive benchmark suite covering routing, middleware, JSON APIs
- ✅ ORM-specific benchmarks for database performance testing
- ✅ ReactPHP continuous runtime benchmarks for async performance
- ✅ Stress testing and concurrency benchmarks
- ✅ Automated report generation in HTML and Markdown formats

This structure eliminates dependency conflicts and provides accurate performance comparisons between PivotPHP variants (Core, ORM, ReactPHP) and other popular PHP microframeworks.

## Version Updates (Latest)

### PivotPHP Core v1.1.4 (Enhanced Edition)
- Revolutionary JSON optimization: 10.2M+ ops/sec total throughput
- Small JSON: 7.75M ops/sec (569,545% improvement over v1.1.0)
- Medium JSON: 59K ops/sec (6,867% improvement over v1.1.0)
- Large JSON: 4.2K ops/sec (2,008% improvement over v1.1.0)
- 25x faster Request/Response creation with object pooling
- Full PSR-7 compliance with hybrid Express.js API
- Advanced performance optimizations and memory management
- Enhanced security middleware and routing capabilities

### PivotPHP Cycle ORM v1.0.1 
- Performance improvements and monitoring enhancements
- Optimized query execution and connection pooling
- Better integration with PivotPHP Core v1.1.4

### PivotPHP ReactPHP v0.1.0 (Continuous Runtime)
- Event-driven, non-blocking I/O with ReactPHP integration
- Continuous runtime eliminates bootstrap overhead
- 10,000+ requests/second with <5ms latency
- Persistent state management and async operation support

## DevOps and Infrastructure Status

### Environment Status (July 2025)
- **Docker Environment**: ✅ Fully operational with all 10 containers running stably
- **Infrastructure Issues**: ✅ All resolved (container lifecycle, service dependencies, orchestration)
- **Benchmark Validation**: ✅ Cross-framework testing validated and operational
- **Performance Baseline**: ✅ PivotPHP Core v1.1.4 with enhanced documentation and new features

### Important Infrastructure Notes
- **Always use Docker** for benchmark tests to ensure equivalent environment and scenarios
- **Container Management**: All containers use `CMD ["tail", "-f", "/dev/null"]` to stay active
- **Resource Limits**: 2 CPUs, 1GB RAM per container for fair comparison
- **PHP Configuration**: OPcache + JIT enabled across all frameworks
- **Database/Cache**: MySQL 8.0 and Redis 7 available for ORM and caching tests

### Known Issues and Fixes
- **ServiceProvider Issues**: PivotPHP ORM and ReactPHP containers need ServiceProvider class resolution
- **Symfony Dependencies**: Requires `composer require symfony/yaml` for full functionality
- **Middleware Benchmarks**: Some Slim4 middleware tests need stabilization

### Performance Baselines (Validated July 2025)
- **PivotPHP Core v1.1.4**: Enhanced documentation and new features (performance to be validated)
- **Slim 4**: 98,992 routing ops/sec (0.11 MB memory)
- **Lumen**: 26,663 routing ops/sec (0.91 MB memory)
- **Flight**: 11,070 routing ops/sec (7.85 MB memory)