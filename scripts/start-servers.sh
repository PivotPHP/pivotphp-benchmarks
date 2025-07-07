#!/bin/bash

# Start built-in PHP servers for concurrency testing

echo "Starting web servers for concurrency benchmarks..."

# Define ports for each framework
declare -A PORTS=(
    ["pivotphp-core"]=8081
    ["pivotphp-orm"]=8082
    ["slim4"]=8083
    ["lumen"]=8084
    ["flight"]=8085
    ["symfony"]=8086
)

# Kill any existing servers
for port in "${PORTS[@]}"; do
    lsof -ti:$port | xargs kill -9 2>/dev/null
done

# Start servers
for framework in "${!PORTS[@]}"; do
    port=${PORTS[$framework]}
    
    if [ -f "frameworks/$framework/server.php" ]; then
        echo "Starting $framework on port $port..."
        cd frameworks/$framework
        php -S 0.0.0.0:$port server.php > /dev/null 2>&1 &
        cd - > /dev/null
    elif [ -d "frameworks/$framework/public" ]; then
        echo "Starting $framework on port $port..."
        cd frameworks/$framework/public
        php -S 0.0.0.0:$port > /dev/null 2>&1 &
        cd - > /dev/null
    fi
done

echo ""
echo "Servers started:"
for framework in "${!PORTS[@]}"; do
    echo "  $framework: http://localhost:${PORTS[$framework]}"
done

echo ""
echo "Press Ctrl+C to stop all servers"

# Wait for interrupt
trap 'echo "Stopping servers..."; for port in "${PORTS[@]}"; do lsof -ti:$port | xargs kill -9 2>/dev/null; done; exit' INT
wait