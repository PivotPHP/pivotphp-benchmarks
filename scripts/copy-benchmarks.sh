#!/bin/bash

# Script to copy and adapt benchmarks to all frameworks

FRAMEWORKS="lumen flight symfony"
BENCHMARKS="middleware json-api"

for framework in $FRAMEWORKS; do
    echo "Setting up benchmarks for $framework..."
    
    for benchmark in $BENCHMARKS; do
        if [ "$framework" = "lumen" ]; then
            # Copy from helix-core and adapt
            cp frameworks/helix-core/benchmarks/${benchmark}.php frameworks/lumen/benchmarks/${benchmark}.php
            # Update framework name
            sed -i "s/'framework' => 'helix-core'/'framework' => 'lumen'/g" frameworks/lumen/benchmarks/${benchmark}.php
            # Update namespace usage
            sed -i "s/use Helix\\\\Core\\\\Application;/use Laravel\\\\Lumen\\\\Application;/g" frameworks/lumen/benchmarks/${benchmark}.php
            sed -i "s/use Helix\\\\Http\\\\Factory\\\\ServerRequestFactory;/use Illuminate\\\\Http\\\\Request;/g" frameworks/lumen/benchmarks/${benchmark}.php
            sed -i "s/new Application()/new Application(dirname(__DIR__))/g" frameworks/lumen/benchmarks/${benchmark}.php
            
        elif [ "$framework" = "flight" ]; then
            # Copy from helix-core and adapt
            cp frameworks/helix-core/benchmarks/${benchmark}.php frameworks/flight/benchmarks/${benchmark}.php
            # Update framework name
            sed -i "s/'framework' => 'helix-core'/'framework' => 'flight'/g" frameworks/flight/benchmarks/${benchmark}.php
            # Flight specific adaptations would go here
            
        elif [ "$framework" = "symfony" ]; then
            # Copy from helix-core and adapt
            cp frameworks/helix-core/benchmarks/${benchmark}.php frameworks/symfony/benchmarks/${benchmark}.php
            # Update framework name
            sed -i "s/'framework' => 'helix-core'/'framework' => 'symfony'/g" frameworks/symfony/benchmarks/${benchmark}.php
            # Symfony specific adaptations would go here
        fi
    done
done

echo "Benchmark copying completed!"