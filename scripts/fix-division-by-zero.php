#!/usr/bin/env php
<?php

/**
 * Fix division by zero errors in benchmark scripts
 */

function fixBenchmarkFile($filePath) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }
    
    $content = file_get_contents($filePath);
    
    // Fix the division by zero issue
    $oldPattern = '$opsPerSecond = round($iterations / $duration);';
    $newPattern = '$opsPerSecond = $duration > 0 ? round($iterations / $duration) : 0;';
    
    if (strpos($content, $oldPattern) !== false) {
        $content = str_replace($oldPattern, $newPattern, $content);
        file_put_contents($filePath, $content);
        echo "✅ Fixed: $filePath\n";
        return true;
    } else {
        echo "⚠️ Pattern not found in: $filePath\n";
        return false;
    }
}

$benchmarkFiles = [
    'frameworks/pivotphp-core/benchmarks/rest-api.php',
    'frameworks/pivotphp-core/benchmarks/json-performance.php',
    'frameworks/pivotphp-core/benchmarks/api-middleware.php',
    'frameworks/slim4/benchmarks/http-performance.php',
    'frameworks/slim4/benchmarks/rest-api.php',
    'frameworks/slim4/benchmarks/json-performance.php',
    'frameworks/slim4/benchmarks/api-middleware.php'
];

echo "🔧 Fixing division by zero errors in benchmark scripts...\n\n";

$fixedCount = 0;
foreach ($benchmarkFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (fixBenchmarkFile($fullPath)) {
        $fixedCount++;
    }
}

echo "\n✅ Fixed $fixedCount benchmark files\n";
echo "🚀 Ready to run comparative analysis!\n";