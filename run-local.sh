#!/bin/bash

# Run benchmarks locally without Docker
# This is useful for quick testing

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "PivotPHP Local Benchmark Runner"
echo "==============================="
echo ""

# Check if composer dependencies are installed
if [ ! -d "$SCRIPT_DIR/vendor" ]; then
    echo "Installing composer dependencies..."
    cd "$SCRIPT_DIR" && composer install --no-dev --optimize-autoloader
fi

# Default values
ITERATIONS=1000
WARMUP=100
FRAMEWORK="pivotphp-core"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --iterations)
            ITERATIONS="$2"
            shift 2
            ;;
        --warmup)
            WARMUP="$2"
            shift 2
            ;;
        --framework)
            FRAMEWORK="$2"
            shift 2
            ;;
        --all)
            FRAMEWORK="all"
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo "Configuration:"
echo "- Iterations: $ITERATIONS"
echo "- Warmup: $WARMUP"
echo "- Framework: $FRAMEWORK"
echo ""

# Function to run a single benchmark
run_benchmark() {
    local benchmark_file="$1"
    local benchmark_name=$(basename "$benchmark_file" .php)

    echo "Running: $benchmark_name"
    php "$benchmark_file" --iterations=$ITERATIONS --warmup=$WARMUP --json > "/tmp/benchmark_${benchmark_name}.json" 2>&1

    if [ $? -eq 0 ]; then
        # Parse and display results
        local result=$(cat "/tmp/benchmark_${benchmark_name}.json")
        local ops=$(echo "$result" | grep -o '"operations_per_second": *[0-9]*' | sed 's/.*: *//')
        local mem=$(echo "$result" | grep -o '"memory_peak_mb": *[0-9.]*' | sed 's/.*: *//')

        if [ -n "$ops" ] && [ -n "$mem" ]; then
            printf "  ✓ %-30s %10s ops/sec  %6s MB\n" "$benchmark_name" "$ops" "$mem"
        else
            echo "  ✗ $benchmark_name - Failed to parse results"
        fi
    else
        echo "  ✗ $benchmark_name - Execution failed"
        cat "/tmp/benchmark_${benchmark_name}.json"
    fi
}

# Run benchmarks based on selection
if [ "$FRAMEWORK" == "all" ] || [ "$FRAMEWORK" == "pivotphp-core" ]; then
    echo ""
    echo "Running PivotPHP Core Benchmarks"
    echo "--------------------------------"

    # Routing benchmarks
    echo ""
    echo "Routing:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-core/routing/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done

    # Middleware benchmarks
    echo ""
    echo "Middleware:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-core/middleware/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done

    # Container benchmarks
    echo ""
    echo "Container:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-core/container/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done

    # HTTP benchmarks
    echo ""
    echo "HTTP:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-core/http/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done
fi

if [ "$FRAMEWORK" == "all" ] || [ "$FRAMEWORK" == "pivotphp-orm" ]; then
    echo ""
    echo "Running PivotPHP ORM Benchmarks"
    echo "-------------------------------"

    # Query benchmarks
    echo ""
    echo "Queries:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-orm/queries/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done

    # Hydration benchmarks
    echo ""
    echo "Hydration:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-orm/hydration/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done

    # Transaction benchmarks
    echo ""
    echo "Transactions:"
    for file in "$SCRIPT_DIR"/benchmarks/pivotphp-orm/transactions/*.php; do
        [ -f "$file" ] && run_benchmark "$file"
    done
fi

if [ "$FRAMEWORK" == "all" ] || [ "$FRAMEWORK" == "comparative" ]; then
    echo ""
    echo "Running Comparative Benchmarks"
    echo "------------------------------"

    # Slim 4 benchmarks
    if [ -d "$SCRIPT_DIR/benchmarks/comparative/slim4" ]; then
        echo ""
        echo "Slim 4:"
        for file in "$SCRIPT_DIR"/benchmarks/comparative/slim4/*.php; do
            [ -f "$file" ] && run_benchmark "$file"
        done
    fi
fi

echo ""
echo "Benchmark run complete!"
echo ""

# Generate simple summary
echo "Summary Report"
echo "=============="
echo ""
echo "To generate detailed reports, run:"
echo "  php scripts/generate-report.php"
echo ""
echo "Results saved in: $SCRIPT_DIR/results/raw/"
