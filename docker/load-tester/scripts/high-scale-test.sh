#!/bin/bash

# High-Scale Concurrency Testing Script
# Tests frameworks with various load testing tools up to 50,000 concurrent requests

set -e

# Configuration
RESULTS_DIR=${RESULTS_DIR:-/results}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="$RESULTS_DIR/high-scale-$TIMESTAMP"

# Framework endpoints
declare -A FRAMEWORKS=(
    ["pivotphp-core"]="http://pivotphp-core-scale/health"
    ["slim4"]="http://slim4-scale/health"
    ["laravel"]="http://laravel-scale/health"
    ["pivotphp-full"]="http://pivotphp-full-scale/health"
)

# Concurrency levels to test
CONCURRENCY_LEVELS=(100 500 1000 2500 5000 10000 25000 50000)

# Test duration and request counts
DURATION="30s"
MAX_REQUESTS=100000

echo "🚀 HIGH-SCALE CONCURRENCY TESTING FRAMEWORK"
echo "=============================================="
echo "Timestamp: $TIMESTAMP"
echo "Results Directory: $REPORT_DIR"
echo "Frameworks: ${!FRAMEWORKS[@]}"
echo "Concurrency Levels: ${CONCURRENCY_LEVELS[@]}"
echo ""

# Create results directory
mkdir -p "$REPORT_DIR"

# Function to wait for framework to be ready
wait_for_framework() {
    local name=$1
    local url=$2
    local timeout=60
    local count=0
    
    echo "🔄 Waiting for $name to be ready..."
    while [ $count -lt $timeout ]; do
        if curl -s -f "$url" > /dev/null 2>&1; then
            echo "✅ $name is ready"
            return 0
        fi
        sleep 1
        ((count++))
    done
    
    echo "❌ Timeout waiting for $name"
    return 1
}

# Function to run Apache Bench test
run_ab_test() {
    local framework=$1
    local url=$2
    local concurrency=$3
    local requests=$4
    local output_file=$5
    
    echo "🔄 Running AB test: $framework (c=$concurrency, n=$requests)"
    
    ab -c $concurrency -n $requests -g "$output_file.dat" "$url" > "$output_file.txt" 2>&1
    
    if [ $? -eq 0 ]; then
        echo "✅ AB test completed for $framework"
        return 0
    else
        echo "❌ AB test failed for $framework"
        return 1
    fi
}

# Function to run wrk test
run_wrk_test() {
    local framework=$1
    local url=$2
    local concurrency=$3
    local duration=$4
    local output_file=$5
    
    echo "🔄 Running wrk test: $framework (c=$concurrency, d=$duration)"
    
    wrk -t$((concurrency/10)) -c$concurrency -d$duration --script=/tools/wrk-stats.lua "$url" > "$output_file.txt" 2>&1
    
    if [ $? -eq 0 ]; then
        echo "✅ wrk test completed for $framework"
        return 0
    else
        echo "❌ wrk test failed for $framework"
        return 1
    fi
}

# Function to run Vegeta test
run_vegeta_test() {
    local framework=$1
    local url=$2
    local rate=$3
    local duration=$4
    local output_file=$5
    
    echo "🔄 Running Vegeta test: $framework (rate=$rate/s, d=$duration)"
    
    echo "GET $url" | vegeta attack -rate=$rate -duration=$duration > "$output_file.bin"
    vegeta report < "$output_file.bin" > "$output_file.txt"
    vegeta plot < "$output_file.bin" > "$output_file.html"
    
    if [ $? -eq 0 ]; then
        echo "✅ Vegeta test completed for $framework"
        return 0
    else
        echo "❌ Vegeta test failed for $framework"
        return 1
    fi
}

# Function to run hey test
run_hey_test() {
    local framework=$1
    local url=$2
    local concurrency=$3
    local requests=$4
    local output_file=$5
    
    echo "🔄 Running hey test: $framework (c=$concurrency, n=$requests)"
    
    hey -c $concurrency -n $requests -o csv "$url" > "$output_file.csv" 2>&1
    hey -c $concurrency -n $requests "$url" > "$output_file.txt" 2>&1
    
    if [ $? -eq 0 ]; then
        echo "✅ hey test completed for $framework"
        return 0
    else
        echo "❌ hey test failed for $framework"
        return 1
    fi
}

# Function to monitor system resources
monitor_resources() {
    local output_file=$1
    local duration=$2
    
    echo "📊 Monitoring system resources for $duration seconds..."
    
    {
        echo "timestamp,cpu_percent,memory_percent,load_avg,network_rx,network_tx"
        for i in $(seq 1 $duration); do
            timestamp=$(date +%s)
            cpu_percent=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
            memory_info=$(free | grep Mem)
            memory_total=$(echo $memory_info | awk '{print $2}')
            memory_used=$(echo $memory_info | awk '{print $3}')
            memory_percent=$(echo "scale=2; $memory_used * 100 / $memory_total" | bc)
            load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | tr -d ',')
            
            echo "$timestamp,$cpu_percent,$memory_percent,$load_avg,0,0"
            sleep 1
        done
    } > "$output_file"
}

# Create wrk stats script
create_wrk_stats_script() {
    cat > /tools/wrk-stats.lua << 'EOF'
local cjson = require "cjson"

local stats = {}
stats.requests = 0
stats.responses = {}
stats.latencies = {}

function init(args)
    stats.start_time = os.time()
end

function request()
    stats.requests = stats.requests + 1
    return wrk.request()
end

function response(status, headers, body)
    if not stats.responses[status] then
        stats.responses[status] = 0
    end
    stats.responses[status] = stats.responses[status] + 1
end

function done(summary, latency, requests)
    print("\n--- Custom Stats ---")
    print("Total Requests: " .. stats.requests)
    print("Duration: " .. summary.duration .. " microseconds")
    print("RPS: " .. (stats.requests / (summary.duration / 1000000)))
    print("Min Latency: " .. latency.min .. " microseconds")
    print("Max Latency: " .. latency.max .. " microseconds")
    print("Mean Latency: " .. latency.mean .. " microseconds")
    print("50th Percentile: " .. latency:percentile(50) .. " microseconds")
    print("95th Percentile: " .. latency:percentile(95) .. " microseconds")
    print("99th Percentile: " .. latency:percentile(99) .. " microseconds")
    
    print("\nStatus Codes:")
    for status, count in pairs(stats.responses) do
        print("  " .. status .. ": " .. count)
    end
end
EOF
}

# Main execution
main() {
    echo "🔧 Setting up environment..."
    
    # Create wrk stats script
    create_wrk_stats_script
    
    # Wait for all frameworks to be ready
    echo "⏳ Waiting for frameworks to start..."
    for framework in "${!FRAMEWORKS[@]}"; do
        url=${FRAMEWORKS[$framework]}
        if ! wait_for_framework "$framework" "$url"; then
            echo "⚠️ Framework $framework not available, skipping..."
            unset FRAMEWORKS[$framework]
        fi
    done
    
    if [ ${#FRAMEWORKS[@]} -eq 0 ]; then
        echo "❌ No frameworks available for testing"
        exit 1
    fi
    
    echo "✅ Available frameworks: ${!FRAMEWORKS[@]}"
    echo ""
    
    # Run tests for each framework and concurrency level
    for framework in "${!FRAMEWORKS[@]}"; do
        echo "🏗️ Testing framework: $framework"
        echo "================================"
        
        url=${FRAMEWORKS[$framework]}
        framework_dir="$REPORT_DIR/$framework"
        mkdir -p "$framework_dir"
        
        for concurrency in "${CONCURRENCY_LEVELS[@]}"; do
            echo "📊 Testing concurrency level: $concurrency"
            
            level_dir="$framework_dir/c$concurrency"
            mkdir -p "$level_dir"
            
            # Calculate appropriate number of requests
            if [ $concurrency -le 1000 ]; then
                requests=$((concurrency * 20))
            elif [ $concurrency -le 10000 ]; then
                requests=$((concurrency * 10))
            else
                requests=$((concurrency * 5))
            fi
            
            # Limit maximum requests
            if [ $requests -gt $MAX_REQUESTS ]; then
                requests=$MAX_REQUESTS
            fi
            
            echo "  Requests: $requests, Concurrency: $concurrency"
            
            # Start resource monitoring in background
            monitor_resources "$level_dir/resources.csv" 35 &
            monitor_pid=$!
            
            # Run Apache Bench test
            if command -v ab > /dev/null; then
                run_ab_test "$framework" "$url" "$concurrency" "$requests" "$level_dir/ab"
            fi
            
            # Run wrk test
            if command -v wrk > /dev/null; then
                run_wrk_test "$framework" "$url" "$concurrency" "$DURATION" "$level_dir/wrk"
            fi
            
            # Run Vegeta test (rate-based)
            if command -v vegeta > /dev/null; then
                rate=$((requests / 30)) # Spread over 30 seconds
                run_vegeta_test "$framework" "$url" "$rate" "30s" "$level_dir/vegeta"
            fi
            
            # Run hey test
            if command -v hey > /dev/null; then
                run_hey_test "$framework" "$url" "$concurrency" "$requests" "$level_dir/hey"
            fi
            
            # Stop resource monitoring
            kill $monitor_pid 2>/dev/null || true
            wait $monitor_pid 2>/dev/null || true
            
            echo "  ✅ Completed concurrency level $concurrency"
            
            # Brief pause between tests
            sleep 5
        done
        
        echo "✅ Completed framework: $framework"
        echo ""
    done
    
    # Generate consolidated report
    echo "📋 Generating consolidated report..."
    generate_report
    
    echo "🎉 High-scale testing completed!"
    echo "📊 Results available in: $REPORT_DIR"
}

# Function to generate consolidated report
generate_report() {
    local report_file="$REPORT_DIR/consolidated_report.json"
    
    echo "📈 Generating consolidated analysis..."
    
    # Create JSON report structure
    cat > "$report_file" << EOF
{
    "test_suite": "High-Scale Concurrency Testing",
    "timestamp": "$TIMESTAMP",
    "frameworks_tested": [$(printf '"%s",' "${!FRAMEWORKS[@]}" | sed 's/,$//')],
    "concurrency_levels": [$(printf '%s,' "${CONCURRENCY_LEVELS[@]}" | sed 's/,$//')],
    "tools_used": ["ab", "wrk", "vegeta", "hey"],
    "results_directory": "$REPORT_DIR",
    "test_duration": "$DURATION",
    "max_requests": $MAX_REQUESTS
}
EOF
    
    echo "✅ Report generated: $report_file"
}

# Run main function
main "$@"