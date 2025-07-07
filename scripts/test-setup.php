#!/usr/bin/env php
<?php

/**
 * Test Setup Script
 *
 * Verifies that the benchmark environment is properly configured
 */

echo "Testing PivotPHP Benchmark Setup...\n";
echo "===================================\n\n";

$errors = [];
$warnings = [];

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $errors[] = "PHP 8.1 or higher is required";
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'json', 'mbstring', 'opcache'];
echo "\nChecking PHP extensions:\n";
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ {$ext}\n";
    } else {
        $errors[] = "Missing required extension: {$ext}";
        echo "✗ {$ext}\n";
    }
}

// Check OPcache configuration
if (extension_loaded('opcache')) {
    echo "\nOPcache Configuration:\n";
    $opcacheConfig = opcache_get_configuration();
    echo "- Enabled: " . ($opcacheConfig['directives']['opcache.enable'] ? 'Yes' : 'No') . "\n";
    echo "- CLI Enabled: " . ($opcacheConfig['directives']['opcache.enable_cli'] ? 'Yes' : 'No') . "\n";
    echo "- JIT: " . ($opcacheConfig['directives']['opcache.jit'] ?? 'Not configured') . "\n";
    echo "- Memory: " . $opcacheConfig['directives']['opcache.memory_consumption'] . " MB\n";

    if (!$opcacheConfig['directives']['opcache.enable_cli']) {
        $warnings[] = "OPcache is not enabled for CLI - benchmarks may not reflect production performance";
    }
}

// Check autoloader
echo "\nChecking autoloader:\n";
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "✓ Composer autoloader found\n";
    require_once $autoloadFile;
} else {
    $errors[] = "Composer autoloader not found. Run 'composer install' first.";
    echo "✗ Composer autoloader not found\n";
}

// Check benchmark directories
echo "\nChecking directory structure:\n";
$directories = [
    'benchmarks/helix-core/routing',
    'benchmarks/helix-core/middleware',
    'benchmarks/helix-core/container',
    'benchmarks/helix-core/http',
    'benchmarks/helix-orm/queries',
    'benchmarks/helix-orm/hydration',
    'benchmarks/helix-orm/transactions',
    'benchmarks/comparative/slim4',
    'benchmarks/comparative/lumen',
    'benchmarks/comparative/flight',
    'results/raw',
    'results/processed',
    'results/reports'
];

$baseDir = __DIR__ . '/..';
foreach ($directories as $dir) {
    $fullPath = $baseDir . '/' . $dir;
    if (is_dir($fullPath)) {
        echo "✓ {$dir}\n";
    } else {
        $warnings[] = "Directory not found: {$dir}";
        echo "✗ {$dir}\n";
    }
}

// Check benchmark classes
if (class_exists('HelixBenchmark\\Timer')) {
    echo "\n✓ Timer class available\n";
} else {
    $errors[] = "Timer class not found";
    echo "\n✗ Timer class not found\n";
}

if (class_exists('HelixBenchmark\\BenchmarkBase')) {
    echo "✓ BenchmarkBase class available\n";
} else {
    $errors[] = "BenchmarkBase class not found";
    echo "✗ BenchmarkBase class not found\n";
}

// Check database connectivity (if running in Docker)
if (getenv('MYSQL_HOST')) {
    echo "\nChecking database connection:\n";
    try {
        $pdo = new PDO(
            'mysql:host=' . getenv('MYSQL_HOST') . ';dbname=' . getenv('MYSQL_DATABASE'),
            getenv('MYSQL_USER'),
            getenv('MYSQL_PASSWORD')
        );
        echo "✓ MySQL connection successful\n";
    } catch (PDOException $e) {
        $warnings[] = "MySQL connection failed: " . $e->getMessage();
        echo "✗ MySQL connection failed\n";
    }
}

// Summary
echo "\n===================================\n";
echo "Setup Test Summary:\n";
echo "===================================\n";

if (empty($errors) && empty($warnings)) {
    echo "\n✓ All checks passed! The benchmark environment is ready.\n";
    exit(0);
} else {
    if (!empty($errors)) {
        echo "\nErrors:\n";
        foreach ($errors as $error) {
            echo "✗ {$error}\n";
        }
    }

    if (!empty($warnings)) {
        echo "\nWarnings:\n";
        foreach ($warnings as $warning) {
            echo "⚠ {$warning}\n";
        }
    }

    echo "\nPlease fix the issues above before running benchmarks.\n";
    exit(1);
}
