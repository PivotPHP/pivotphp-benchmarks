# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the PivotPHP benchmarking suite - a private repository for performance testing the PivotPHP ecosystem against other PHP microframeworks. It uses an isolated structure where each framework has its own directory, dependencies, and Docker container to ensure fair and conflict-free comparisons.

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

### Docker-based Benchmarking
```bash
# Build all Docker containers
make build

# Run complete benchmark suite
make benchmark-all              # All benchmarks on all frameworks
make benchmark-pivotphp         # All PivotPHP variants (core + orm + reactphp)
make benchmark-core             # Only PivotPHP Core (without ORM)
make benchmark-orm              # Only PivotPHP with Cycle ORM
make benchmark-reactphp         # Only PivotPHP with ReactPHP (continuous runtime)
make benchmark-comparative      # Slim4, Lumen, Flight, Symfony
make benchmark-matrix           # Run across PHP 8.1, 8.2, 8.3

# Generate performance reports
make report

# Container management
make up                         # Start benchmark environment
make down                       # Stop benchmark environment
make clean                      # Clean all results and containers

# Debug/Development access
make shell-pivotphp-core        # Shell access to PivotPHP Core container
make shell-pivotphp-orm         # Shell access to PivotPHP ORM container
make shell-pivotphp-reactphp    # Shell access to PivotPHP ReactPHP container
make shell-slim4                # Shell access to Slim 4 container
make db-shell                   # Access MySQL shell
make redis-cli                  # Access Redis CLI
```

### Direct Benchmark Execution
```bash
# Using the new runner script
php scripts/run-benchmarks-new.php --all
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,pivotphp-orm,pivotphp-reactphp
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,slim4 --iterations=50000

# Alternative scripts
php scripts/run-benchmarks.php          # Legacy runner
php scripts/run-all-benchmarks.php      # Comprehensive test suite
php scripts/run-concurrency-benchmarks.php  # Concurrency tests
php scripts/stress-test-all-frameworks.php  # Stress testing

# Composer shortcuts
composer benchmark                       # Run default benchmarks
composer benchmark:all                   # Run all benchmarks
composer benchmark:pivotphp             # PivotPHP only
composer benchmark:comparative           # Other frameworks
composer report                         # Generate reports
```

### Local Development (without Docker)
```bash
# Test individual frameworks locally
cd frameworks/pivotphp-core
php benchmarks/routing.php --iterations=1000 --warmup=100

# Run specific benchmark types
php benchmarks/middleware.php
php benchmarks/json-api.php

# For ORM-enabled frameworks
cd frameworks/pivotphp-orm  
php benchmarks/orm-queries.php
php benchmarks/orm-hydration.php

# For ReactPHP continuous runtime
cd frameworks/pivotphp-reactphp
php benchmarks/routing.php
php benchmarks/http-server.php          # Real HTTP server benchmark
php benchmarks/async-performance.php
php benchmarks/continuous-runtime.php

# Start ReactPHP server manually
php server.php                          # Start server on http://0.0.0.0:8000
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
├── raw/               # Raw JSON benchmark data
├── processed/         # Processed/aggregated data  
├── reports/           # HTML and Markdown reports
├── *.json            # Individual test results
└── *.md              # Summary reports
```

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

## Benchmark Types Available

### Core Benchmarks (All Frameworks)
- **routing.php**: Route matching and dispatching performance
- **middleware.php**: Middleware pipeline execution
- **json-api.php**: JSON serialization/deserialization
- **memory.php**: Memory usage profiling
- **security.php**: Security middleware overhead

### PivotPHP-Specific Benchmarks
- **concurrency.php**: In-process concurrency testing
- **auth.php**: Authentication middleware performance
- **container/resolution.php**: Dependency injection performance

### ORM-Specific Benchmarks (pivotphp-orm only)
- **orm-queries.php**: Database query performance
- **orm-hydration.php**: Entity hydration performance

### ReactPHP-Specific Benchmarks (pivotphp-reactphp only)
- **routing.php**: Route matching in continuous runtime context
- **http-server.php**: Real HTTP server performance with client requests
- **async-performance.php**: Async operations and event loop performance
- **continuous-runtime.php**: Persistent state and runtime advantages

### Stress Testing
- **stress/synchronous-load-test.php**: High-load synchronous testing
- **stress-test/unified-stress-test.php**: Cross-framework stress comparison

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

### PivotPHP Core v1.1.0 (High-Performance Edition)
- 25x faster Request/Response creation with object pooling
- Full PSR-7 compliance with hybrid Express.js API
- Advanced performance optimizations and memory management
- Enhanced security middleware and routing capabilities

### PivotPHP Cycle ORM v1.0.1 
- Performance improvements and monitoring enhancements
- Optimized query execution and connection pooling
- Better integration with PivotPHP Core v1.1.0

### PivotPHP ReactPHP v0.1.0 (Continuous Runtime)
- Event-driven, non-blocking I/O with ReactPHP integration
- Continuous runtime eliminates bootstrap overhead
- 10,000+ requests/second with <5ms latency
- Persistent state management and async operation support
