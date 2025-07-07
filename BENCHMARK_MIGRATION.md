# Benchmark Migration from PivotPHP Core

## Overview

This document describes the benchmark migration from pivotphp-core to pivotphp-benchmarks and the current benchmark structure.

## Migration Status

### ✅ Migrated Benchmarks

The following benchmarks have been successfully migrated from pivotphp-core to pivotphp-benchmarks:

1. **routing.php** - Route matching and dispatching performance
2. **middleware.php** - PSR-15 middleware pipeline execution
3. **json-api.php** - JSON API endpoint performance
4. **security.php** - CORS, XSS, and CSRF middleware (NEW)
5. **auth.php** - JWT generation/validation and authentication flow (NEW)
6. **memory.php** - Memory usage analysis and leak detection (NEW)

### 🗂️ Benchmarks Remaining in pivotphp-core

The following benchmarks remain in pivotphp-core as they focus on internal optimization analysis:

- **OptimizationBenchmark.php** - Framework optimization analysis
- **AdvancedOptimizationsBenchmark.php** - Advanced optimization techniques
- **EnhancedAdvancedOptimizationsBenchmark.php** - Enhanced optimizations
- **DatabaseBenchmark.php** - Database-specific optimizations
- **PSRPerformanceBenchmark.php** - PSR vs traditional implementation comparison

### ❌ Removed Duplicates

The following benchmarks were removed from pivotphp-core as they were duplicated:

- **SimpleBenchmark.php** - Functionality covered by individual benchmarks
- **PivotPhpBenchmark.php** - Functionality split into specific benchmarks
- **ExpressPhpBenchmark.php** - Not relevant for framework comparison

## Benchmark Structure

### pivotphp-benchmarks Structure

```
frameworks/
├── pivotphp-core/
│   └── benchmarks/
│       ├── routing.php          # Route performance
│       ├── middleware.php       # Middleware pipeline
│       ├── json-api.php        # API endpoint performance
│       ├── concurrency.php     # Concurrent request handling
│       ├── security.php        # Security middleware (CORS, XSS, CSRF)
│       ├── auth.php           # Authentication & JWT
│       ├── memory.php         # Memory usage analysis
│       └── run.php            # Benchmark runner
│
└── pivotphp-orm/
    └── benchmarks/
        ├── routing.php         # Same as core
        ├── middleware.php      # Same as core
        ├── json-api.php       # Same as core
        ├── orm-queries.php    # ORM query performance
        ├── orm-hydration.php  # Entity hydration
        └── run.php           # Benchmark runner
```

### Key Differences

1. **pivotphp-benchmarks**:
   - Focus on **real-world performance** scenarios
   - **Standardized metrics** across all frameworks
   - **Docker isolation** for fair comparison
   - **PSR-compliant** implementations
   - **Comparative analysis** between frameworks

2. **pivotphp-core benchmarks**:
   - Focus on **internal optimization** analysis
   - **Development guidance** for framework improvements
   - **Detailed profiling** of specific components
   - **Before/after comparisons** for optimizations

## Running Benchmarks

### In pivotphp-benchmarks:

```bash
# Run all PivotPHP benchmarks
make benchmark-pivotphp

# Run specific framework benchmarks
make benchmark-core     # Only core
make benchmark-orm      # Only with ORM

# Run comparative analysis
make benchmark-all      # All frameworks
make report            # Generate reports
```

### In pivotphp-core:

```bash
# Run optimization benchmarks
php benchmarks/OptimizationBenchmark.php
php benchmarks/run_all_benchmarks.php
```

## Benefits of This Structure

1. **Separation of Concerns**:
   - Comparative benchmarks in pivotphp-benchmarks
   - Internal optimization in pivotphp-core

2. **Fair Comparison**:
   - All frameworks run in identical Docker containers
   - Same resource limits (2 CPUs, 1GB RAM)
   - Standardized benchmark interface

3. **Comprehensive Coverage**:
   - Performance testing (pivotphp-benchmarks)
   - Optimization analysis (pivotphp-core)
   - Both perspectives are valuable

## Future Additions

Planned benchmarks for pivotphp-benchmarks:

1. **database.php** - When pivotphp-orm adds more database features
2. **cache.php** - Caching layer performance
3. **validation.php** - Input validation performance
4. **template.php** - Template rendering (if added to framework)