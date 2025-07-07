# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the PivotPHP benchmarking suite - a private repository for performance testing the PivotPHP ecosystem against other PHP microframeworks. It uses an isolated structure where each framework has its own directory, dependencies, and Docker container to ensure fair and conflict-free comparisons.

## Essential Commands

### Docker-based Benchmarking
```bash
# Build all Docker containers
make build

# Run complete benchmark suite
make benchmark-all              # All benchmarks on all frameworks
make benchmark-pivotphp         # Both pivotphp-core and pivotphp-orm
make benchmark-core             # Only PivotPHP Core (without ORM)
make benchmark-orm              # Only PivotPHP with Cycle ORM
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
make shell-slim4                # Shell access to Slim 4 container
make db-shell                   # Access MySQL shell
make redis-cli                  # Access Redis CLI
```

### Direct Benchmark Execution
```bash
# Using the new runner script
php scripts/run-benchmarks-new.php --all
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,pivotphp-orm
php scripts/run-benchmarks-new.php --frameworks=pivotphp-core,slim4 --iterations=50000
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
├── slim4/                  # Slim 4 Framework
├── lumen/                  # Laravel Lumen
├── flight/                 # Flight Framework
└── symfony/                # Symfony Flex
```

### Shared Components
- **BenchmarkInterface** (`shared/Interfaces/BenchmarkInterface.php`): Standard interface all benchmarks implement
- **Centralized Runner** (`scripts/run-benchmarks-new.php`): Orchestrates benchmark execution across containers
- **Result Storage** (`results/raw/`): JSON output from each benchmark run

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

3. **PivotPHP Core vs ORM**:
   - Separate projects to measure ORM overhead
   - Allows testing performance impact of Cycle ORM
   - Both use identical application structure for fair comparison

### Adding New Benchmarks

1. Create benchmark file in `frameworks/{framework}/benchmarks/`
2. Implement `BenchmarkInterface` or follow existing pattern
3. Ensure identical test scenarios across all frameworks
4. Update runner script if needed

### Benchmark Standards
- **Warmup**: 1000 iterations before measurement
- **Measurement**: 10,000+ iterations
- **Metrics**: Operations/sec, memory usage, response times
- **Environment**: OPcache+JIT enabled, consistent PHP settings

## Current Implementation Status

Framework isolation structure is complete with:
- Separate directories and dependencies for each framework
- Docker orchestration via docker-compose
- Centralized benchmark runner
- Example benchmarks for routing comparison

This structure eliminates dependency conflicts and provides accurate performance comparisons between PivotPHP (with and without ORM) and other popular PHP microframeworks.
