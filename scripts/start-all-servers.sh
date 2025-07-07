#!/bin/bash

# Start all framework servers for benchmarking

echo "=== Starting Framework Servers for Benchmarking ==="
echo "Date: $(date)"
echo ""

# Kill any existing servers
echo "Stopping any existing servers..."
pkill -f "php -S localhost:800" 2>/dev/null
sleep 2

# Create logs directory
mkdir -p logs

# Start PivotPHP Core
echo "Starting PivotPHP Core on port 8001..."
cd frameworks/pivotphp-core
php -S localhost:8001 -t public > ../../logs/pivotphp-core.log 2>&1 &
cd ../..

# Start PivotPHP ORM
echo "Starting PivotPHP ORM on port 8002..."
cd frameworks/pivotphp-orm
php -S localhost:8002 -t public > ../../logs/pivotphp-orm.log 2>&1 &
cd ../..

# Start Slim 4
echo "Starting Slim 4 on port 8003..."
cd frameworks/slim4
php -S localhost:8003 -t public > ../../logs/slim4.log 2>&1 &
cd ../..

# Start Lumen
echo "Starting Lumen on port 8004..."
cd frameworks/lumen
php -S localhost:8004 -t public > ../../logs/lumen.log 2>&1 &
cd ../..

# Start Flight
echo "Starting Flight on port 8005..."
cd frameworks/flight
php -S localhost:8005 -t public > ../../logs/flight.log 2>&1 &
cd ../..

# Wait for servers to start
echo ""
echo "Waiting for servers to start..."
sleep 5

# Check if servers are running
echo ""
echo "Checking server status..."
for port in 8001 8002 8003 8004 8005; do
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:$port/api/status | grep -q "200\|204"; then
        echo "  ✓ Server on port $port is running"
    else
        echo "  ✗ Server on port $port is NOT responding"
    fi
done

echo ""
echo "All servers started. Logs available in ./logs/"
echo "To stop all servers, run: pkill -f 'php -S localhost:800'"