<?php

declare(strict_types=1);

/**
 * PivotPHP Ecosystem Validation - Helper Functions
 * 
 * @version 1.1.4
 * @date July 15, 2025
 */

if (!function_exists('ecosystem_log')) {
    /**
     * Log ecosystem validation messages
     */
    function ecosystem_log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] {$message}\n";
        
        echo $formatted;
        
        // Also log to file if available
        $logFile = __DIR__ . '/../logs/ecosystem-validation.log';
        if (is_dir(dirname($logFile))) {
            file_put_contents($logFile, $formatted, FILE_APPEND | LOCK_EX);
        }
    }
}

if (!function_exists('format_performance_number')) {
    /**
     * Format performance numbers for display
     */
    function format_performance_number(float $number, string $unit = 'req/sec'): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M ' . $unit;
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K ' . $unit;
        } else {
            return number_format($number, 2) . ' ' . $unit;
        }
    }
}

if (!function_exists('calculate_percentage_difference')) {
    /**
     * Calculate percentage difference between two values
     */
    function calculate_percentage_difference(float $value1, float $value2): float
    {
        if ($value2 == 0) {
            return 0.0;
        }
        
        return (($value1 - $value2) / $value2) * 100;
    }
}

if (!function_exists('ecosystem_create_mock_app')) {
    /**
     * Create a mock application for testing
     */
    function ecosystem_create_mock_app(): object
    {
        return new class {
            private array $routes = [];
            
            public function get(string $path, callable $handler): void
            {
                $this->routes['GET'][$path] = $handler;
            }
            
            public function post(string $path, callable $handler): void
            {
                $this->routes['POST'][$path] = $handler;
            }
            
            public function register($provider): void
            {
                // Mock service provider registration
            }
            
            public function run(): void
            {
                // Mock application run
            }
            
            public function getRoutes(): array
            {
                return $this->routes;
            }
        };
    }
}

if (!function_exists('ecosystem_mock_response')) {
    /**
     * Create a mock response for testing
     */
    function ecosystem_mock_response(): object
    {
        return new class {
            public function json(array $data): array
            {
                return $data;
            }
            
            public function status(int $code): object
            {
                return $this;
            }
            
            public function send(string $content = ''): string
            {
                return $content;
            }
        };
    }
}

if (!function_exists('ecosystem_mock_request')) {
    /**
     * Create a mock request for testing
     */
    function ecosystem_mock_request(array $params = [], array $body = []): object
    {
        return new class($params, $body) {
            private array $params;
            private array $body;
            
            public function __construct(array $params, array $body)
            {
                $this->params = $params;
                $this->body = $body;
            }
            
            public function param(string $key, $default = null)
            {
                return $this->params[$key] ?? $default;
            }
            
            public function body(): array
            {
                return $this->body;
            }
        };
    }
}

if (!function_exists('ecosystem_benchmark_operation')) {
    /**
     * Benchmark a specific operation
     */
    function ecosystem_benchmark_operation(callable $operation, int $iterations = 1000): array
    {
        $times = [];
        $memoryUsage = [];
        $successCount = 0;
        
        // Warmup
        for ($i = 0; $i < min(100, $iterations / 10); $i++) {
            try {
                $operation();
            } catch (Exception $e) {
                // Ignore warmup errors
            }
        }
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $iterationStart = microtime(true);
            $memoryStart = memory_get_usage(true);
            
            try {
                $operation();
                $successCount++;
            } catch (Exception $e) {
                // Count as failure
            }
            
            $times[] = microtime(true) - $iterationStart;
            $memoryUsage[] = memory_get_usage(true) - $memoryStart;
        }
        
        $totalTime = microtime(true) - $startTime;
        $avgTime = array_sum($times) / count($times);
        $avgMemory = array_sum($memoryUsage) / count($memoryUsage);
        
        return [
            'req_sec' => round($iterations / $totalTime, 2),
            'avg_response_time_ms' => round($avgTime * 1000, 4),
            'avg_memory_kb' => round($avgMemory / 1024, 2),
            'success_rate' => round(($successCount / $iterations) * 100, 2),
            'total_time_sec' => round($totalTime, 4),
            'min_time_ms' => round(min($times) * 1000, 4),
            'max_time_ms' => round(max($times) * 1000, 4),
            'iterations' => $iterations
        ];
    }
}

if (!function_exists('ecosystem_validate_performance_target')) {
    /**
     * Validate if performance meets target
     */
    function ecosystem_validate_performance_target(float $actual, float $target, float $tolerance = 0.1): array
    {
        $difference = abs($actual - $target);
        $percentageDiff = ($difference / $target) * 100;
        $withinTolerance = $percentageDiff <= ($tolerance * 100);
        
        return [
            'actual' => $actual,
            'target' => $target,
            'difference' => $difference,
            'percentage_diff' => round($percentageDiff, 2),
            'meets_target' => $actual >= $target,
            'within_tolerance' => $withinTolerance,
            'status' => $withinTolerance ? 'PASS' : ($actual >= $target ? 'EXCEED' : 'FAIL')
        ];
    }
}

if (!function_exists('ecosystem_generate_test_data')) {
    /**
     * Generate test data for benchmarking
     */
    function ecosystem_generate_test_data(string $type = 'simple'): array
    {
        switch ($type) {
            case 'simple':
                return [
                    'id' => rand(1, 1000),
                    'name' => 'Test Item ' . rand(1, 100),
                    'timestamp' => time()
                ];
                
            case 'medium':
                return [
                    'user' => [
                        'id' => rand(1, 1000),
                        'name' => 'Test User ' . rand(1, 100),
                        'email' => 'user' . rand(1, 100) . '@test.com',
                        'created_at' => date('Y-m-d H:i:s')
                    ],
                    'metadata' => [
                        'version' => '1.1.4',
                        'environment' => 'test',
                        'items' => array_fill(0, 10, ['key' => 'value' . rand(1, 100)])
                    ]
                ];
                
            case 'complex':
                return [
                    'workflow' => [
                        'id' => uniqid('wf_'),
                        'steps' => array_fill(0, 20, [
                            'id' => rand(1, 100),
                            'name' => 'Step ' . rand(1, 20),
                            'data' => array_fill(0, 5, ['item' => rand(1, 1000)])
                        ]),
                        'metadata' => [
                            'created_at' => date('c'),
                            'version' => '1.1.4',
                            'environment' => 'ecosystem-test'
                        ]
                    ]
                ];
                
            default:
                return ['test' => true];
        }
    }
}

if (!function_exists('ecosystem_format_bytes')) {
    /**
     * Format bytes to human readable format
     */
    function ecosystem_format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('ecosystem_calculate_percentile')) {
    /**
     * Calculate percentile from array of values
     */
    function ecosystem_calculate_percentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ceil(count($values) * $percentile / 100) - 1;
        return $values[$index] ?? 0;
    }
}

if (!function_exists('ecosystem_check_docker_available')) {
    /**
     * Check if Docker is available
     */
    function ecosystem_check_docker_available(): bool
    {
        $result = shell_exec('docker --version 2>&1');
        return $result && strpos($result, 'Docker version') !== false;
    }
}

if (!function_exists('ecosystem_health_check_url')) {
    /**
     * Perform health check on URL
     */
    function ecosystem_health_check_url(string $url, int $timeout = 10): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'PivotPHP Ecosystem Validation'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'url' => $url,
            'healthy' => $httpCode === 200 && !$error,
            'http_code' => $httpCode,
            'response_time' => $responseTime,
            'error' => $error ?: null,
            'response' => $response
        ];
    }
}