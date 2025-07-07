# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

This is the HelixPHP benchmarking suite - a private repository for performance testing the HelixPHP ecosystem against other PHP microframeworks. It uses an isolated structure where each framework has its own directory, dependencies, and Docker container to ensure fair and conflict-free comparisons.

## Essential Commands

### Docker-based Benchmarking
```bash
# Build all Docker containers
make build

# Run complete benchmark suite
make benchmark-all              # All benchmarks on all frameworks
make benchmark-helix            # Both helix-core and helix-orm
make benchmark-core             # Only HelixPHP Core (without ORM)
make benchmark-orm              # Only HelixPHP with Cycle ORM
make benchmark-comparative      # Slim4, Lumen, Flight, Symfony
make benchmark-matrix           # Run across PHP 8.1, 8.2, 8.3

# Generate performance reports
make report

# Container management
make up                         # Start benchmark environment
make down                       # Stop benchmark environment
make clean                      # Clean all results and containers

# Debug/Development access
make shell-helix-core           # Shell access to HelixPHP Core container
make shell-helix-orm            # Shell access to HelixPHP ORM container
make shell-slim4                # Shell access to Slim 4 container
make db-shell                   # Access MySQL shell
make redis-cli                  # Access Redis CLI
```

### Direct Benchmark Execution
```bash
# Using the new runner script
php scripts/run-benchmarks-new.php --all
php scripts/run-benchmarks-new.php --frameworks=helix-core,helix-orm
php scripts/run-benchmarks-new.php --frameworks=helix-core,slim4 --iterations=50000
```

## Code Architecture

### Isolated Framework Structure
```
frameworks/
├── helix-core/             # HelixPHP without ORM
│   ├── composer.json       # Only helixphp/core dependency
│   ├── Dockerfile          # Optimized container
│   ├── public/             # Entry points
│   ├── src/                # Application code
│   └── benchmarks/         # Benchmark scripts
│       ├── routing.php
│       ├── middleware.php
│       └── json-api.php
│
├── helix-orm/              # HelixPHP with Cycle ORM
│   ├── composer.json       # helixphp/core + helixphp/cycle-orm
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

3. **HelixPHP Core vs ORM**: 
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

This structure eliminates dependency conflicts and provides accurate performance comparisons between HelixPHP (with and without ORM) and other popular PHP microframeworks.