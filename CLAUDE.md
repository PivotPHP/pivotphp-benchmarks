# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the PivotPHP benchmarking suite - a comprehensive performance testing environment for the PivotPHP ecosystem against other PHP microframeworks. It uses an isolated Docker-based structure where each framework has its own directory, dependencies, and container to ensure fair and conflict-free comparisons.

**Current Status**: 🚀 Updated for PivotPHP v1.2.0 "Reimagined Architecture"
**Last Validation**: July 21, 2025 - v1.2.0 "Simplicidade sobre Otimização Prematura" Edition
**Performance Validated**: PivotPHP Core v1.2.0 (Simplicity over Premature Optimization)
**Latest Updates**: Complete architectural reimagining with OpenAPI/Swagger integration
**Philosophy Shift**: Focus on proof of concept, rapid prototyping, and developer experience

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

### Docker Environment v1.2.0 (Reimagined Architecture)
```bash
# Using v1.2.0 specific Makefile and docker-compose
make -f Makefile-v120 build    # Build v1.2.0 containers with reimagined features
make -f Makefile-v120 up       # Start v1.2.0 benchmark environment
make -f Makefile-v120 down     # Stop v1.2.0 environment
make -f Makefile-v120 clean    # Clean v1.2.0 environment

# Core benchmarking commands (v1.2.0 Reimagined)
make -f Makefile-v120 benchmark-all           # All benchmarks on v1.2.0
make -f Makefile-v120 benchmark-core          # PivotPHP Core v1.2.0 (Simplified)
make -f Makefile-v120 benchmark-openapi       # NEW: OpenAPI documentation performance
make -f Makefile-v120 benchmark-legacy        # NEW: Legacy vs Simplified comparison
make -f Makefile-v120 benchmark-simplicity    # NEW: Simple vs Complex implementations

# Phase-specific v1.2.0 commands
make -f Makefile-v120 phase1-test        # Phase 1 core tests (simplified)
make -f Makefile-v120 phase2-test        # Phase 2 extension tests
make -f Makefile-v120 phase3-test        # Phase 3 ecosystem validation
make -f Makefile-v120 openapi-test       # NEW: OpenAPI/Swagger testing

# Container access (v1.2.0)
make -f Makefile-v120 shell-core         # Shell to v1.2.0 core container
make -f Makefile-v120 shell-orm          # Shell to v1.2.0 ORM container
make -f Makefile-v120 shell-reactphp     # Shell to v1.2.0 ReactPHP container

# Monitoring and logs
make -f Makefile-v120 status             # Check container status
make -f Makefile-v120 logs               # View all logs
make -f Makefile-v120 health             # Health checks
make -f Makefile-v120 swagger-ui         # Open Swagger UI interfaces

# Legacy v1.1.4 commands (still functional)
make -f Makefile-v114 benchmark-all      # v1.1.4 benchmarks for comparison
make benchmark-all                       # All benchmarks on all frameworks
make benchmark-comparative              # Cross-framework comparison
```

### Direct Benchmark Execution v1.2.0 (Reimagined Architecture)
```bash
# Standalone v1.2.0 benchmarks (no server required)
php simple_benchmark_v120.php                    # Core v1.2.0 benchmark (Simplified Architecture)
php benchmark_v120.php                          # Advanced v1.2.0 benchmarks
php openapi_benchmark_v120.php                  # NEW: OpenAPI documentation performance

# Phase 1 - Core Framework v1.2.0 (requires server on localhost:8080)
php phase1/benchmarks/run-simple-v120.php       # Simple v1.2.0 tests
php phase1/benchmarks/run-v120.php              # Full v1.2.0 core tests
php phase1/benchmarks/run-simplicity-test.php   # NEW: Simple vs Complex comparison

# Phase 2 - Extensions v1.2.0 (requires servers on localhost:8081-8082)
php phase2/benchmarks/run-phase2-v120.php       # Complete extension tests
php phase2/benchmarks/run-phase2-v120-test.php  # Validation tests
php phase2/benchmarks/run-legacy-comparison.php # NEW: Legacy vs Simplified performance

# Phase 3 - Ecosystem Validation v1.2.0 (comprehensive)
php phase3/benchmarks/run-phase3-v120.php       # Full ecosystem tests v1.2.0
php phase3/benchmarks/run-phase3-reimagined.php # NEW: Reimagined architecture tests
php phase3/ecosystem-validation/ecosystem-v120-benchmark.php # v1.2.0 ecosystem

# NEW: OpenAPI/Swagger specific benchmarks
php benchmarks/openapi-generation-benchmark.php  # Documentation generation speed
php benchmarks/swagger-ui-benchmark.php          # Swagger UI performance
php benchmarks/api-documentation-middleware.php  # ApiDocumentationMiddleware throughput

# Version comparison benchmarks
php benchmarks/v114-vs-v120-comparison.php      # Performance comparison
php benchmarks/simplicity-impact-analysis.php   # Simplicity vs Performance analysis

# Legacy v1.1.4 benchmarks (for comparison)
php simple_benchmark_v114.php                    # v1.1.4 baseline
php scripts/run-benchmarks-new.php --all
php scripts/stress-test-all-frameworks.php       # Cross-version stress testing

# Report generation
php scripts/generate-report-v120.php             # v1.2.0 specific reports
php scripts/generate-simplicity-report.php       # NEW: Simplicity impact report
php scripts/generate-openapi-report.php          # NEW: OpenAPI performance report
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

### Phase-Based Testing Structure (v1.2.0 Reimagined)
```
pivotphp-benchmarks/
├── phase1/                     # Core Framework Testing (Simplified)
│   ├── benchmarks/
│   │   ├── run-simple-v120.php        # 🚀 NEW - Simple v1.2.0 tests
│   │   ├── run-v120.php               # 🚀 NEW - Full v1.2.0 tests
│   │   ├── run-simplicity-test.php    # 🚀 NEW - Simple vs Complex
│   │   ├── run-simple-v114.php        # ✅ v1.1.4 for comparison
│   │   └── run-simple.php             # Legacy tests
│   ├── Dockerfile-v120            # 🚀 NEW - v1.2.0 simplified container
│   ├── Dockerfile-v114            # ✅ v1.1.4 container
│   └── composer.json              # Updated to v1.2.0
│
├── phase2/                     # Extension Testing (Reimagined)
│   ├── benchmarks/
│   │   ├── run-phase2-v120.php        # 🚀 NEW - v1.2.0 extension tests
│   │   ├── run-phase2-v120-test.php   # 🚀 NEW - v1.2.0 validation tests
│   │   ├── run-legacy-comparison.php  # 🚀 NEW - Legacy vs Simplified
│   │   ├── run-phase2-v114.php        # ✅ v1.1.4 for comparison
│   │   └── run-phase2.php             # Legacy tests
│   └── docker/
│       ├── Dockerfile-orm-v120        # ORM + v1.2.0 simplified
│       ├── Dockerfile-reactphp-v120   # ReactPHP + v1.2.0 simplified
│       ├── Dockerfile-orm             # v1.1.4 baseline
│       └── Dockerfile-reactphp        # v1.1.4 baseline
│
├── phase3/                     # Ecosystem Validation (Reimagined)
│   ├── ecosystem-validation/       # Enhanced for v1.2.0
│   │   ├── docker-compose-v120-ecosystem.yml  # 🚀 NEW
│   │   ├── ecosystem-v120-benchmark.php       # 🚀 NEW
│   │   ├── openapi-ecosystem-test.php         # 🚀 NEW - OpenAPI testing
│   │   ├── simplicity-impact-analysis.php    # 🚀 NEW - Simplicity analysis
│   │   ├── legacy-compatibility-test.php     # 🚀 NEW - Backward compatibility
│   │   └── k6-scripts/            # Enhanced stress testing
│   ├── benchmarks/
│   │   ├── run-phase3-v120.php        # 🚀 NEW - v1.2.0 ecosystem tests
│   │   ├── run-phase3-reimagined.php  # 🚀 NEW - Reimagined architecture
│   │   └── run-phase3-v114.php        # ✅ v1.1.4 for comparison
│   └── docker/
│       ├── Dockerfile-pivotphp-v120   # 🚀 NEW - v1.2.0 container
│       ├── docker-compose-v120.yml    # 🚀 NEW - Complete v1.2.0 environment
│       └── docker-compose-v114.yml    # ✅ v1.1.4 baseline
│
├── benchmarks/                 # 🚀 NEW - OpenAPI/Swagger Benchmarks
│   ├── openapi-generation-benchmark.php    # Documentation generation speed
│   ├── swagger-ui-benchmark.php            # Swagger UI performance  
│   ├── api-documentation-middleware.php    # ApiDocumentationMiddleware
│   ├── v114-vs-v120-comparison.php        # Version comparison
│   └── simplicity-impact-analysis.php     # Simplicity vs Performance
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

### Current Validated Results (July 2025 - v1.1.4)
**Infrastructure Validation:**
- **docker-ecosystem-validation-2025-07-15-15-26-50.json** - v1.1.4 infrastructure
- **ecosystem-comprehensive-report-2025-07-15-15-07-08.md** - Complete ecosystem analysis

**Phase-specific Results (v1.1.4):**
- **phase1-simple-benchmark-2025-07-14-22-57-07.json** - Core v1.1.4 (validated)
- **phase3-v114-standalone-2025-07-15-12-24-14.json** - v1.1.4 ecosystem tests
- **core-vs-reactphp-2025-07-15-12-52-04.json** - ReactPHP integration analysis

**Comprehensive Reports:**
- **RELATORIO_CONSOLIDADO_V114.md** - Executive summary v1.1.4
- **RELATORIO_TECNICO_V114.md** - Technical deep-dive v1.1.4
- **ECOSYSTEM_VALIDATION_FINAL_REPORT.md** - Complete ecosystem validation

**Legacy Results (still valid):**
- **cross-framework-benchmark-report-2025-07-11.md** - Cross-framework comparison
- **enterprise-performance-report-2025-07-11-00-09-44.json** - Enterprise metrics

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

### 🔥 Core Infrastructure Benchmarks (All Frameworks + v1.2.0)

#### Traditional Benchmarks (All Frameworks)
- **routing.php**: Route matching and dispatching performance
- **middleware.php**: Middleware pipeline execution
- **json-api.php**: JSON serialization/deserialization
- **concurrency.php**: Multi-request concurrent handling
- **database.php**: Database operations simulation
- **memory.php**: Memory usage profiling and leak detection
- **security.php**: Security middleware and input validation

#### 🚀 NEW: v1.2.0 Specific Benchmarks
- **openapi-generation.php**: OpenAPI documentation generation speed
- **swagger-ui.php**: Swagger UI rendering and interactive performance
- **api-documentation-middleware.php**: ApiDocumentationMiddleware throughput
- **simplicity-impact.php**: Simple vs Complex implementation performance
- **legacy-compatibility.php**: Backward compatibility overhead measurement
- **automatic-aliases.php**: Performance impact of 15+ automatic aliases

### 🌐 API-Focused Benchmarks (Production-Ready)
- **http-performance.php**: HTTP request/response processing, headers, content negotiation
- **rest-api.php**: Complete REST operations (GET, POST, PUT, PATCH, DELETE, pagination)
- **json-performance.php**: JSON processing (small/medium/large datasets, nested structures)
- **api-middleware.php**: Production middleware stack (CORS, auth, rate limiting, compression)

### ⚙️ Framework-Specific Benchmarks

#### PivotPHP Core v1.2.0 (Simplicidade sobre Otimização Prematura Edition)
**🚀 New Philosophy: Simplicity over Premature Optimization**

**Core Performance (Maintained from v1.1.4):**
- **Application Creation**: 44,092 ops/sec (framework throughput maintained)
- **Route Registration**: Maintained performance with simplified code
- **Array Callable Support**: Full backward compatibility maintained
- **Object Pooling**: 100% Request reuse, 99.9% Response reuse (preserved)
- **Revolutionary JSON Performance**: 10.2M+ ops/sec total throughput (preserved)

**🆕 New v1.2.0 Features & Performance:**
- **Automatic OpenAPI Documentation**: ApiDocumentationMiddleware performance testing
- **Swagger UI Integration**: Interactive documentation at `/swagger` endpoint  
- **Simplified Architecture**: PerformanceMode (simplified) vs HighPerformanceMode (legacy)
- **15+ Automatic Aliases**: Zero breaking changes, seamless migration
- **Legacy Compatibility**: Complex implementations available in `src/Legacy/`

**🔬 Architectural Simplification:**
- **Simple Classes by Default**: PerformanceMode, PoolManager (simplified defaults)
- **Legacy Namespace**: Complex implementations moved to `src/Legacy/`
- **3 Performance Profiles**: Simplified from 5+ configurations
- **Zero Configuration**: OpenAPI documentation generated automatically
- **Educational Focus**: Optimized for proof of concept and rapid prototyping

#### PivotPHP ORM v1.0.1 (with Cycle ORM)
- **orm-queries.php**: Database query performance
- **orm-hydration.php**: Entity hydration and relationship loading
- **ORM model operations**: Entity management (327K+ ops/sec)
- **Unit of Work patterns**: Change tracking and persistence

#### PivotPHP ReactPHP v0.1.0 (Continuous Runtime)
**Ecosystem Performance (Validated July 15, 2025):**
- **Core + ReactPHP**: 510,799 req/sec average, 544,927 req/sec peak
- **JSON Processing**: 464,228 req/sec (ReactPHP advantage)
- **Event Loop Operations**: 1.27M+ ops/sec continuous runtime benefits
- **HTTP Server Performance**: 10,000+ req/sec with <5ms latency

**ReactPHP-Specific Benchmarks:**
- **async-performance.php**: Event loop and promise operations
- **continuous-runtime.php**: Persistent state advantages
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

### Environment Status (July 2025 - v1.1.4)
- **Docker Environment v1.1.4**: ✅ Complete ecosystem validation completed with docker-compose-v114.yml
- **Infrastructure Issues**: ✅ All resolved with enhanced v1.1.4 containers and orchestration
- **Benchmark Validation**: ✅ Phase 3 ecosystem validation completed successfully
- **Performance Baseline**: ✅ PivotPHP Core v1.1.4 (83,077 ops/sec peak validated)
- **Ecosystem Integration**: ✅ Core + ReactPHP + Cycle ORM validated and operational

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

### Performance Baselines (Validated July 15, 2025 - v1.1.4)
**PivotPHP v1.1.4 Ecosystem Performance:**
- **Core Only**: 499,498 req/sec avg, 540,503 req/sec peak (+8,224% vs target)
- **Core + ReactPHP**: 510,799 req/sec avg, 544,927 req/sec peak (+3,305% vs target)
- **Core + Cycle ORM**: 526,177 req/sec avg, 562,465 req/sec peak (+11,592% vs target)
- **Full Ecosystem**: 369,081 req/sec avg, 497,250 req/sec peak (+2,975% vs target)

**Standalone v1.1.4 Performance:**
- **Application Creation**: 83,077 ops/sec (validated peak)
- **Route Registration**: 33,521 ops/sec
- **Array Callable**: 30,694 ops/sec (new feature)
- **Complex Applications**: 15,000+ ops/sec

**Legacy Comparative Baselines:**
- **Slim 4**: 98,992 routing ops/sec (0.11 MB memory)
- **Lumen**: 26,663 routing ops/sec (0.91 MB memory)
- **Flight**: 11,070 routing ops/sec (7.85 MB memory)

## Single Test Execution v1.2.0 (Reimagined)

### Running Individual v1.2.0 Tests
```bash
# Standalone benchmarks (no server required) - v1.2.0 Reimagined
php simple_benchmark_v120.php         # Core v1.2.0 benchmark (Simplified Architecture)
php benchmark_v120.php                # Advanced v1.2.0 benchmarks  
php openapi_benchmark_v120.php        # NEW: OpenAPI documentation performance

# Phase 1 tests (requires server on localhost:8080) - v1.2.0
php phase1/benchmarks/run-simple-v120.php --single-test=application_creation
php phase1/benchmarks/run-simple-v120.php --single-test=route_registration
php phase1/benchmarks/run-simple-v120.php --single-test=openapi_generation
php phase1/benchmarks/run-simplicity-test.php --comparison=simple-vs-complex

# Phase 2 tests (requires servers on localhost:8081-8082) - v1.2.0  
php phase2/benchmarks/run-phase2-v120-test.php --component=core
php phase2/benchmarks/run-phase2-v120-test.php --component=orm
php phase2/benchmarks/run-phase2-v120-test.php --component=reactphp
php phase2/benchmarks/run-legacy-comparison.php --mode=simple-vs-legacy

# Phase 3 ecosystem tests (comprehensive validation) - v1.2.0
php phase3/benchmarks/run-phase3-v120.php --config=core-only
php phase3/benchmarks/run-phase3-v120.php --config=core-reactphp
php phase3/benchmarks/run-phase3-v120.php --config=core-orm
php phase3/benchmarks/run-phase3-v120.php --config=full-ecosystem
php phase3/benchmarks/run-phase3-reimagined.php --focus=simplicity-impact

# NEW: v1.2.0 Specific Tests
cd phase3/ecosystem-validation
php ecosystem-v120-benchmark.php --scenario=openapi-generation
php openapi-ecosystem-test.php --scenario=swagger-ui-performance
php simplicity-impact-analysis.php --comparison=v114-vs-v120
php legacy-compatibility-test.php --mode=backward-compatibility
```

### Docker-based Single Tests v1.2.0
```bash
# Test specific v1.2.0 containers (Reimagined)
docker-compose -f docker-compose-v120.yml exec pivotphp-core-v120 php benchmarks/simple-realistic-benchmark.php
docker-compose -f docker-compose-v120.yml exec pivotphp-orm-v120 php benchmarks/orm-queries.php
docker-compose -f docker-compose-v120.yml exec pivotphp-reactphp-v120 php benchmarks/routing.php

# NEW: OpenAPI/Swagger container tests
docker-compose -f docker-compose-v120.yml exec pivotphp-core-v120 php benchmarks/openapi-generation-benchmark.php
docker-compose -f docker-compose-v120.yml exec pivotphp-core-v120 curl -s http://localhost/swagger

# Health checks for v1.2.0 containers
make -f Makefile-v120 health-core
make -f Makefile-v120 health-orm  
make -f Makefile-v120 health-reactphp
make -f Makefile-v120 swagger-ui

# Version comparison tests
docker-compose -f docker-compose-v120.yml exec pivotphp-core-v120 php benchmarks/v114-vs-v120-comparison.php
```

### Legacy Test Execution (v1.1.4 for comparison)
```bash
# Run v1.1.4 benchmarks for comparison
php simple_benchmark_v114.php         # v1.1.4 baseline
php phase1/benchmarks/run-simple-v114.php --single-test=application_creation

# Cross-version validation
php scripts/validate-versions.php --version=both
php benchmarks/v114-vs-v120-comparison.php --detailed-analysis
```