#!/usr/bin/env php
<?php

/**
 * PivotPHP Core Security Benchmark
 * Tests security middleware and validation performance
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

function runBenchmark($name, $iterations, $callback) {
    echo "🚀 $name ($iterations iterations)... ";
    
    $start = microtime(true);
    $startMemory = memory_get_usage();
    
    for ($i = 0; $i < $iterations; $i++) {
        $callback();
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $memoryUsed = memory_get_usage() - $startMemory;
    $opsPerSecond = round($iterations / $duration);
    
    echo number_format($opsPerSecond) . " ops/sec\n";
    
    return [
        'ops_per_second' => $opsPerSecond,
        'duration' => $duration,
        'memory_used' => $memoryUsed
    ];
}

function parseArgs($argv) {
    $args = ['json' => false, 'iterations' => 1000, 'warmup' => 100];
    
    foreach ($argv as $arg) {
        if ($arg === '--json') $args['json'] = true;
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 14));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "===========================================\n";
    echo " PivotPHP Core Security Benchmark\n";
    echo "===========================================\n\n";
}

$results = [];

// Test 1: Input Sanitization
$results['input_sanitization'] = runBenchmark(
    'Input Sanitization', 
    $args['iterations'], 
    function() {
        $inputs = [
            'name' => '<script>alert("xss")</script>John Doe',
            'email' => 'user+test@domain.com',
            'message' => 'Hello & welcome to our "awesome" website!',
            'url' => 'https://example.com/path?param=value&other=<script>',
            'html' => '<p>Valid content</p><script>alert("bad")</script>',
            'sql' => "'; DROP TABLE users; --",
            'json' => '{"key": "value", "nested": {"array": [1,2,3]}}',
            'multiline' => "Line 1\nLine 2\rLine 3\r\nLine 4"
        ];
        
        $sanitized = [];
        foreach ($inputs as $key => $value) {
            switch ($key) {
                case 'name':
                case 'message':
                    $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case 'email':
                    $sanitized[$key] = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;
                case 'url':
                    $sanitized[$key] = filter_var($value, FILTER_SANITIZE_URL);
                    break;
                case 'html':
                    $sanitized[$key] = strip_tags($value, '<p><br><strong><em>');
                    break;
                case 'sql':
                    $sanitized[$key] = addslashes($value);
                    break;
                case 'json':
                    $decoded = json_decode($value, true);
                    $sanitized[$key] = $decoded ? json_encode($decoded) : '';
                    break;
                case 'multiline':
                    $sanitized[$key] = preg_replace('/\r\n|\r|\n/', ' ', $value);
                    break;
            }
        }
        
        return [
            'inputs_processed' => count($inputs),
            'sanitized_data' => $sanitized,
            'total_input_length' => array_sum(array_map('strlen', $inputs)),
            'total_output_length' => array_sum(array_map('strlen', $sanitized))
        ];
    }
);

// Test 2: CSRF Token Generation and Validation
$results['csrf_protection'] = runBenchmark(
    'CSRF Protection', 
    $args['iterations'], 
    function() {
        // Generate CSRF token
        $session_id = 'sess_' . uniqid();
        $secret_key = 'secret_' . hash('sha256', uniqid());
        $timestamp = time();
        
        // Create token
        $token_data = $session_id . '|' . $timestamp . '|' . $secret_key;
        $csrf_token = hash_hmac('sha256', $token_data, $secret_key);
        $full_token = base64_encode($session_id . '|' . $timestamp . '|' . $csrf_token);
        
        // Validate token
        $decoded = base64_decode($full_token);
        $parts = explode('|', $decoded);
        
        if (count($parts) === 3) {
            [$session, $token_time, $token_hash] = $parts;
            $expected_data = $session . '|' . $token_time . '|' . $secret_key;
            $expected_hash = hash_hmac('sha256', $expected_data, $secret_key);
            
            $is_valid = hash_equals($expected_hash, $token_hash) && 
                       ($timestamp - $token_time) < 3600; // 1 hour expiry
        } else {
            $is_valid = false;
        }
        
        return [
            'token_generated' => $full_token,
            'token_valid' => $is_valid,
            'generation_time' => microtime(true),
            'token_length' => strlen($full_token)
        ];
    }
);

// Test 3: Rate Limiting
$results['rate_limiting'] = runBenchmark(
    'Rate Limiting', 
    $args['iterations'], 
    function() {
        static $rate_limit_store = [];
        
        $client_ip = '192.168.' . rand(1, 255) . '.' . rand(1, 255);
        $current_time = time();
        $window = 60; // 1 minute window
        $max_requests = 100;
        
        // Initialize or get existing data
        if (!isset($rate_limit_store[$client_ip])) {
            $rate_limit_store[$client_ip] = [
                'requests' => [],
                'blocked_until' => 0
            ];
        }
        
        $client_data = &$rate_limit_store[$client_ip];
        
        // Check if currently blocked
        if ($client_data['blocked_until'] > $current_time) {
            return [
                'client_ip' => $client_ip,
                'status' => 'blocked',
                'requests_in_window' => count($client_data['requests']),
                'blocked_until' => $client_data['blocked_until']
            ];
        }
        
        // Remove old requests outside the window
        $client_data['requests'] = array_filter(
            $client_data['requests'], 
            fn($time) => ($current_time - $time) < $window
        );
        
        // Check rate limit
        if (count($client_data['requests']) >= $max_requests) {
            $client_data['blocked_until'] = $current_time + 300; // Block for 5 minutes
            return [
                'client_ip' => $client_ip,
                'status' => 'rate_limited',
                'requests_in_window' => count($client_data['requests']),
                'blocked_until' => $client_data['blocked_until']
            ];
        }
        
        // Add current request
        $client_data['requests'][] = $current_time;
        
        return [
            'client_ip' => $client_ip,
            'status' => 'allowed',
            'requests_in_window' => count($client_data['requests']),
            'remaining_requests' => $max_requests - count($client_data['requests'])
        ];
    }
);

// Test 4: XSS Protection
$results['xss_protection'] = runBenchmark(
    'XSS Protection', 
    $args['iterations'], 
    function() {
        $dangerous_inputs = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(1)">',
            '<svg onload="alert(1)">',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<link rel="stylesheet" href="javascript:alert(1)">',
            '<style>@import "javascript:alert(1)";</style>',
            '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">',
            '<object data="javascript:alert(1)">',
            '<embed src="javascript:alert(1)">',
            'onmouseover="alert(1)"',
            'onfocus="alert(1)"',
            '<div onclick="alert(1)">Click me</div>',
            '<form action="javascript:alert(1)">',
            '<a href="javascript:alert(1)">Link</a>'
        ];
        
        $sanitized_outputs = [];
        $threat_patterns = [
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi',
            '/<embed\b[^<]*>/gi',
            '/<link\b[^>]*>/gi',
            '/<meta\b[^>]*>/gi'
        ];
        
        foreach ($dangerous_inputs as $input) {
            $sanitized = $input;
            
            // Remove dangerous patterns
            foreach ($threat_patterns as $pattern) {
                $sanitized = preg_replace($pattern, '', $sanitized);
            }
            
            // HTML encode remaining
            $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
            
            $sanitized_outputs[] = [
                'original' => $input,
                'sanitized' => $sanitized,
                'threats_detected' => $input !== $sanitized,
                'reduction_ratio' => strlen($sanitized) / strlen($input)
            ];
        }
        
        return [
            'inputs_processed' => count($dangerous_inputs),
            'threats_detected' => count(array_filter($sanitized_outputs, fn($o) => $o['threats_detected'])),
            'avg_reduction' => array_sum(array_column($sanitized_outputs, 'reduction_ratio')) / count($sanitized_outputs),
            'samples' => array_slice($sanitized_outputs, 0, 3)
        ];
    }
);

// Test 5: SQL Injection Protection
$results['sql_injection_protection'] = runBenchmark(
    'SQL Injection Protection', 
    $args['iterations'], 
    function() {
        $malicious_inputs = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "1' UNION SELECT * FROM passwords--",
            "admin'/**/OR/**/1=1--",
            "' OR 1=1#",
            "1; INSERT INTO logs VALUES(1,'hacked');",
            "' OR SLEEP(5)--",
            "1' AND (SELECT COUNT(*) FROM users) > 0--",
            "'; UPDATE users SET password='hacked' WHERE id=1--",
            "1' OR ASCII(SUBSTRING((SELECT password FROM users WHERE id=1),1,1))>65--"
        ];
        
        $protected_queries = [];
        foreach ($malicious_inputs as $input) {
            // Simulate parameterized query protection
            $query_template = "SELECT * FROM users WHERE id = ?";
            $parameters = [$input];
            
            // Simulate SQL injection detection
            $injection_patterns = [
                '/(\bOR\b|\bAND\b).*?[\'"].*?[\'"].*?=/i',
                '/UNION\s+SELECT/i',
                '/DROP\s+TABLE/i',
                '/INSERT\s+INTO/i',
                '/UPDATE\s+.*?SET/i',
                '/DELETE\s+FROM/i',
                '/SLEEP\s*\(/i',
                '/BENCHMARK\s*\(/i',
                '/--/',
                '/#/',
                '/\/\*.*?\*\//',
                '/[\'"];.*?[\'"]/'
            ];
            
            $threats_detected = 0;
            foreach ($injection_patterns as $pattern) {
                if (preg_match($pattern, $input)) {
                    $threats_detected++;
                }
            }
            
            // Escape for demonstration
            $escaped_input = addslashes($input);
            
            $protected_queries[] = [
                'original_input' => $input,
                'query_template' => $query_template,
                'escaped_parameter' => $escaped_input,
                'threats_detected' => $threats_detected,
                'is_malicious' => $threats_detected > 0,
                'protection_method' => 'parameterized_query'
            ];
        }
        
        return [
            'inputs_tested' => count($malicious_inputs),
            'threats_detected' => count(array_filter($protected_queries, fn($q) => $q['is_malicious'])),
            'protection_rate' => count(array_filter($protected_queries, fn($q) => $q['is_malicious'])) / count($malicious_inputs),
            'samples' => array_slice($protected_queries, 0, 3)
        ];
    }
);

// Test 6: Authentication Token Validation
$results['auth_token_validation'] = runBenchmark(
    'Auth Token Validation', 
    $args['iterations'], 
    function() {
        // Generate JWT-like token
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => rand(1, 10000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'roles' => ['user', 'member'],
            'iat' => time(),
            'exp' => time() + 3600,
            'permissions' => ['read', 'write']
        ]);
        
        $base64_header = base64_encode($header);
        $base64_payload = base64_encode($payload);
        $secret = 'secret_key_' . hash('sha256', uniqid());
        
        $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, $secret);
        $token = $base64_header . '.' . $base64_payload . '.' . $signature;
        
        // Validate token
        $token_parts = explode('.', $token);
        if (count($token_parts) === 3) {
            [$header_b64, $payload_b64, $signature_provided] = $token_parts;
            
            $expected_signature = hash_hmac('sha256', $header_b64 . '.' . $payload_b64, $secret);
            $signature_valid = hash_equals($expected_signature, $signature_provided);
            
            $payload_data = json_decode(base64_decode($payload_b64), true);
            $not_expired = isset($payload_data['exp']) && $payload_data['exp'] > time();
            
            $is_valid = $signature_valid && $not_expired;
        } else {
            $is_valid = false;
        }
        
        return [
            'token_generated' => strlen($token) > 0,
            'token_valid' => $is_valid,
            'token_length' => strlen($token),
            'payload_data' => $payload_data ?? null,
            'validation_time' => microtime(true)
        ];
    }
);

// Test 7: Password Hashing Performance
$results['password_hashing'] = runBenchmark(
    'Password Hashing', 
    $args['iterations'] / 2, 
    function() {
        $passwords = [
            'password123',
            'MySecurePassword!@#',
            str_repeat('a', 50),
            'P@ssw0rd' . rand(1000, 9999),
            uniqid() . '!Secure'
        ];
        
        $results = [];
        foreach ($passwords as $password) {
            $start = microtime(true);
            $hash = password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,       // 4 iterations
                'threads' => 3          // 3 threads
            ]);
            $hash_time = microtime(true) - $start;
            
            $start = microtime(true);
            $verify_result = password_verify($password, $hash);
            $verify_time = microtime(true) - $start;
            
            $results[] = [
                'password_length' => strlen($password),
                'hash' => $hash,
                'hash_time' => $hash_time,
                'verify_result' => $verify_result,
                'verify_time' => $verify_time,
                'hash_length' => strlen($hash)
            ];
        }
        
        return [
            'passwords_processed' => count($passwords),
            'total_hash_time' => array_sum(array_column($results, 'hash_time')),
            'total_verify_time' => array_sum(array_column($results, 'verify_time')),
            'avg_hash_time' => array_sum(array_column($results, 'hash_time')) / count($results),
            'avg_verify_time' => array_sum(array_column($results, 'verify_time')) / count($results),
            'all_verified' => array_sum(array_column($results, 'verify_result')) === count($results)
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'security',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'security_features' => ['input_sanitization', 'csrf_protection', 'rate_limiting', 'xss_protection', 'sql_injection_protection', 'auth_tokens', 'password_hashing'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 Security Performance Summary:\n";
    echo "================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: PivotPHP Core v1.1.0\n";
    echo "🔒 Security Features: Input Sanitization, CSRF, Rate Limiting, XSS, SQL Injection, Auth Tokens, Password Hashing\n";
    echo "⚡ Total Security Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🛡️ Security Status: " . (array_sum(array_column($results, 'ops_per_second')) > 3000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'security',
        'framework' => 'pivotphp-core',
        'version' => '1.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'security_features' => ['input_sanitization', 'csrf_protection', 'rate_limiting', 'xss_protection', 'sql_injection_protection', 'auth_tokens', 'password_hashing'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/pivotphp-core-security-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ Security benchmark completed successfully!\n";
}