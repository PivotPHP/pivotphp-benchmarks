#!/usr/bin/env php
<?php

/**
 * Slim 4 JSON API Performance Benchmark
 * Tests JSON processing performance critical for APIs
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

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
    $opsPerSecond = $duration > 0 ? round($iterations / $duration) : 0;
    
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
        if (strpos($arg, '--iterations=') === 0) $args['iterations'] = intval(substr($arg, 13));
        if (strpos($arg, '--warmup=') === 0) $args['warmup'] = intval(substr($arg, 9));
    }
    
    return $args;
}

$args = parseArgs($argv);

if (!$args['json']) {
    echo "===========================================\n";
    echo " Slim 4 JSON Performance Benchmark\n";
    echo "===========================================\n\n";
}

$results = [];

// Test 1: Small JSON Objects (typical API responses)
$results['small_json_objects'] = runBenchmark(
    'Small JSON Objects (< 1KB)', 
    $args['iterations'], 
    function() {
        $smallObjects = [
            // User object (Slim style)
            [
                'id' => rand(1, 10000),
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'status' => 'active',
                'created_at' => date('c'),
                'updated_at' => date('c')
            ],
            // Post object
            [
                'id' => rand(1, 10000),
                'title' => 'Sample Post Title',
                'content' => 'This is a sample post content that represents typical Slim API data.',
                'author_id' => rand(1, 1000),
                'published' => true,
                'tags' => ['php', 'slim', 'performance'],
                'created_at' => date('c')
            ],
            // API response wrapper (Slim style)
            [
                'success' => true,
                'message' => 'Operation completed successfully',
                'data' => ['count' => rand(1, 100)],
                'meta' => [
                    'timestamp' => microtime(true),
                    'version' => '4.x',
                    'request_id' => uniqid()
                ]
            ]
        ];
        
        $results = [];
        foreach ($smallObjects as $object) {
            $start = microtime(true);
            $json = json_encode($object);
            $encodeTime = microtime(true) - $start;
            
            $start = microtime(true);
            $decoded = json_decode($json, true);
            $decodeTime = microtime(true) - $start;
            
            $results[] = [
                'original_size' => strlen(serialize($object)),
                'json_size' => strlen($json),
                'encode_time' => $encodeTime,
                'decode_time' => $decodeTime,
                'total_time' => $encodeTime + $decodeTime,
                'compression_ratio' => strlen($json) / strlen(serialize($object))
            ];
        }
        
        return [
            'objects_processed' => count($smallObjects),
            'total_json_size' => array_sum(array_column($results, 'json_size')),
            'avg_encode_time' => array_sum(array_column($results, 'encode_time')) / count($results),
            'avg_decode_time' => array_sum(array_column($results, 'decode_time')) / count($results),
            'avg_compression_ratio' => array_sum(array_column($results, 'compression_ratio')) / count($results)
        ];
    }
);

// Test 2: Medium JSON Arrays (paginated lists)
$results['medium_json_arrays'] = runBenchmark(
    'Medium JSON Arrays (1-10KB)', 
    $args['iterations'] / 2, 
    function() {
        // Generate typical paginated Slim API response
        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $users[] = [
                'id' => $i + 1,
                'name' => "User " . ($i + 1),
                'email' => "user" . ($i + 1) . "@example.com",
                'profile' => [
                    'bio' => "Bio for user " . ($i + 1),
                    'location' => 'City ' . (($i % 10) + 1),
                    'website' => "https://user" . ($i + 1) . ".example.com",
                    'avatar' => "https://api.example.com/avatars/" . ($i + 1) . ".jpg",
                    'social' => [
                        'twitter' => '@user' . ($i + 1),
                        'github' => 'user' . ($i + 1),
                        'linkedin' => 'user' . ($i + 1)
                    ]
                ],
                'stats' => [
                    'posts_count' => rand(0, 100),
                    'followers_count' => rand(0, 1000),
                    'following_count' => rand(0, 500)
                ],
                'preferences' => [
                    'theme' => rand(0, 1) ? 'dark' : 'light',
                    'language' => 'en',
                    'notifications' => [
                        'email' => true,
                        'push' => rand(0, 1),
                        'sms' => false
                    ]
                ],
                'created_at' => date('c', time() - rand(0, 86400 * 365)),
                'updated_at' => date('c', time() - rand(0, 86400 * 30)),
                'last_login' => date('c', time() - rand(0, 86400 * 7))
            ];
        }
        
        // Slim-style API response
        $apiResponse = [
            'success' => true,
            'data' => $users,
            'meta' => [
                'total' => 1000,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 20,
                'from' => 1,
                'to' => 50
            ],
            'links' => [
                'first' => '/api/users?page=1',
                'last' => '/api/users?page=20',
                'prev' => null,
                'next' => '/api/users?page=2'
            ]
        ];
        
        $start = microtime(true);
        $json = json_encode($apiResponse);
        $encodeTime = microtime(true) - $start;
        
        $start = microtime(true);
        $decoded = json_decode($json, true);
        $decodeTime = microtime(true) - $start;
        
        return [
            'users_count' => count($users),
            'json_size' => strlen($json),
            'encode_time' => $encodeTime,
            'decode_time' => $decodeTime,
            'total_time' => $encodeTime + $decodeTime,
            'size_kb' => round(strlen($json) / 1024, 2),
            'decode_success' => isset($decoded['data']) && count($decoded['data']) === 50
        ];
    }
);

// Test 3: Large JSON Datasets (export/bulk operations)
$results['large_json_datasets'] = runBenchmark(
    'Large JSON Datasets (10KB+)', 
    $args['iterations'] / 10, 
    function() {
        // Generate large dataset (similar to Slim bulk export)
        $dataset = [
            'export_info' => [
                'type' => 'user_export',
                'format' => 'json',
                'generated_at' => date('c'),
                'total_records' => 500,
                'filters_applied' => ['status' => 'active'],
                'requested_by' => 'admin@example.com',
                'framework' => 'slim4'
            ],
            'records' => []
        ];
        
        for ($i = 0; $i < 500; $i++) {
            $dataset['records'][] = [
                'id' => $i + 1,
                'uuid' => uniqid() . '-' . uniqid(),
                'personal_info' => [
                    'first_name' => 'FirstName' . ($i + 1),
                    'last_name' => 'LastName' . ($i + 1),
                    'full_name' => 'FirstName' . ($i + 1) . ' LastName' . ($i + 1),
                    'email' => 'user' . ($i + 1) . '@example.com',
                    'phone' => '+1-555-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'date_of_birth' => date('Y-m-d', time() - rand(18 * 365 * 24 * 3600, 65 * 365 * 24 * 3600)),
                    'gender' => ['male', 'female', 'other'][rand(0, 2)]
                ],
                'address' => [
                    'street' => ($i + 1) . ' Main Street',
                    'city' => 'City' . (($i % 50) + 1),
                    'state' => 'State' . (($i % 10) + 1),
                    'zip_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'country' => 'USA',
                    'coordinates' => [
                        'latitude' => rand(-90000, 90000) / 1000,
                        'longitude' => rand(-180000, 180000) / 1000
                    ]
                ],
                'account_info' => [
                    'username' => 'user' . ($i + 1),
                    'status' => ['active', 'inactive', 'suspended'][rand(0, 2)],
                    'role' => ['user', 'admin', 'moderator'][rand(0, 2)],
                    'subscription' => [
                        'plan' => ['free', 'premium', 'enterprise'][rand(0, 2)],
                        'expires_at' => date('c', time() + rand(0, 365 * 24 * 3600)),
                        'auto_renew' => rand(0, 1)
                    ]
                ],
                'activity' => [
                    'last_login' => date('c', time() - rand(0, 30 * 24 * 3600)),
                    'login_count' => rand(1, 1000),
                    'posts_count' => rand(0, 500),
                    'comments_count' => rand(0, 2000),
                    'likes_received' => rand(0, 10000)
                ],
                'metadata' => [
                    'created_at' => date('c', time() - rand(0, 365 * 24 * 3600)),
                    'updated_at' => date('c', time() - rand(0, 30 * 24 * 3600)),
                    'created_by' => 'system',
                    'last_modified_by' => 'admin',
                    'tags' => array_map(fn($j) => 'tag_' . $j, range(1, rand(1, 10))),
                    'custom_fields' => array_fill_keys(
                        array_map(fn($j) => 'field_' . $j, range(1, 5)),
                        'custom_value_' . $i
                    )
                ]
            ];
        }
        
        $start = microtime(true);
        $json = json_encode($dataset);
        $encodeTime = microtime(true) - $start;
        
        $start = microtime(true);
        $decoded = json_decode($json, true);
        $decodeTime = microtime(true) - $start;
        
        return [
            'records_count' => count($dataset['records']),
            'json_size' => strlen($json),
            'encode_time' => $encodeTime,
            'decode_time' => $decodeTime,
            'total_time' => $encodeTime + $decodeTime,
            'size_mb' => round(strlen($json) / 1024 / 1024, 2),
            'decode_success' => isset($decoded['records']) && count($decoded['records']) === 500
        ];
    }
);

// Test 4: Nested JSON Structures (complex API responses)
$results['nested_json_structures'] = runBenchmark(
    'Nested JSON Structures', 
    $args['iterations'], 
    function() {
        // Generate complex nested structure (Slim style)
        $complexStructure = [
            'api_response' => [
                'status' => 'success',
                'version' => '4.x',
                'timestamp' => microtime(true),
                'request_id' => uniqid('req_')
            ],
            'data' => [
                'user' => [
                    'id' => rand(1, 10000),
                    'profile' => [
                        'personal' => [
                            'name' => [
                                'first' => 'John',
                                'middle' => 'Michael',
                                'last' => 'Doe',
                                'display' => 'John M. Doe'
                            ],
                            'contact' => [
                                'email' => [
                                    'primary' => 'john@example.com',
                                    'secondary' => 'john.doe@work.com',
                                    'verified' => true
                                ],
                                'phones' => [
                                    ['type' => 'mobile', 'number' => '+1-555-0123', 'verified' => true],
                                    ['type' => 'home', 'number' => '+1-555-0124', 'verified' => false],
                                    ['type' => 'work', 'number' => '+1-555-0125', 'verified' => true]
                                ]
                            ]
                        ],
                        'social' => [
                            'platforms' => [
                                'twitter' => [
                                    'username' => '@johndoe',
                                    'followers' => rand(100, 10000),
                                    'verified' => rand(0, 1)
                                ],
                                'linkedin' => [
                                    'profile_url' => 'https://linkedin.com/in/johndoe',
                                    'connections' => rand(50, 500)
                                ],
                                'github' => [
                                    'username' => 'johndoe',
                                    'repositories' => rand(5, 50),
                                    'stars' => rand(0, 1000)
                                ]
                            ]
                        ]
                    ],
                    'activity' => [
                        'recent_posts' => array_map(function($i) {
                            return [
                                'id' => $i,
                                'title' => "Post $i",
                                'content' => [
                                    'text' => str_repeat("Content for post $i. ", 10),
                                    'html' => "<p>" . str_repeat("Content for post $i. ", 10) . "</p>",
                                    'summary' => "Summary for post $i"
                                ],
                                'engagement' => [
                                    'likes' => rand(0, 1000),
                                    'comments' => rand(0, 100),
                                    'shares' => rand(0, 50)
                                ],
                                'metadata' => [
                                    'created_at' => date('c', time() - rand(0, 86400 * 30)),
                                    'updated_at' => date('c', time() - rand(0, 86400 * 7)),
                                    'tags' => array_map(fn($j) => "tag_$j", range(1, rand(1, 5))),
                                    'category' => [
                                        'id' => rand(1, 10),
                                        'name' => 'Category ' . rand(1, 10),
                                        'parent' => [
                                            'id' => rand(1, 5),
                                            'name' => 'Parent Category ' . rand(1, 5)
                                        ]
                                    ]
                                ]
                            ];
                        }, range(1, 5)),
                        'statistics' => [
                            'totals' => [
                                'posts' => rand(10, 1000),
                                'comments' => rand(50, 5000),
                                'likes_given' => rand(100, 10000),
                                'likes_received' => rand(100, 10000)
                            ],
                            'trends' => [
                                'daily' => array_map(fn($i) => [
                                    'date' => date('Y-m-d', time() - $i * 86400),
                                    'posts' => rand(0, 10),
                                    'engagement' => rand(0, 100)
                                ], range(0, 30)),
                                'monthly' => array_map(fn($i) => [
                                    'month' => date('Y-m', time() - $i * 30 * 86400),
                                    'posts' => rand(10, 100),
                                    'engagement' => rand(100, 1000)
                                ], range(0, 12))
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                'categories' => array_map(function($i) {
                    return [
                        'id' => $i,
                        'name' => "Category $i",
                        'subcategories' => array_map(function($j) use ($i) {
                            return [
                                'id' => $i * 10 + $j,
                                'name' => "Subcategory $j",
                                'parent_id' => $i
                            ];
                        }, range(1, rand(2, 5)))
                    ];
                }, range(1, 5))
            ]
        ];
        
        $start = microtime(true);
        $json = json_encode($complexStructure);
        $encodeTime = microtime(true) - $start;
        
        $start = microtime(true);
        $decoded = json_decode($json, true);
        $decodeTime = microtime(true) - $start;
        
        // Calculate nesting depth
        function getMaxDepth($array, $depth = 0) {
            if (!is_array($array)) return $depth;
            return empty($array) ? $depth : max(array_map(fn($v) => getMaxDepth($v, $depth + 1), $array));
        }
        
        $maxDepth = getMaxDepth($complexStructure);
        
        return [
            'max_nesting_depth' => $maxDepth,
            'json_size' => strlen($json),
            'encode_time' => $encodeTime,
            'decode_time' => $decodeTime,
            'total_time' => $encodeTime + $decodeTime,
            'size_kb' => round(strlen($json) / 1024, 2),
            'decode_success' => isset($decoded['data']['user']['profile'])
        ];
    }
);

// Test 5: JSON Schema Validation Simulation
$results['json_schema_validation'] = runBenchmark(
    'JSON Schema Validation', 
    $args['iterations'], 
    function() {
        // Define Slim API request schema
        $schema = [
            'type' => 'object',
            'required' => ['name', 'email'],
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 50],
                'email' => ['type' => 'string', 'format' => 'email'],
                'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 150],
                'profile' => [
                    'type' => 'object',
                    'properties' => [
                        'bio' => ['type' => 'string', 'maxLength' => 500],
                        'website' => ['type' => 'string', 'format' => 'uri'],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'maxItems' => 10
                        ]
                    ]
                ]
            ]
        ];
        
        // Generate test data
        $testData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'age' => rand(18, 65),
            'profile' => [
                'bio' => 'Software developer with expertise in PHP and Slim framework.',
                'website' => 'https://johndoe.dev',
                'tags' => ['php', 'slim', 'api', 'performance']
            ]
        ];
        
        // Simulate JSON schema validation (Slim style)
        $validationErrors = [];
        
        // Validate required fields
        foreach ($schema['required'] as $field) {
            if (!isset($testData[$field])) {
                $validationErrors[] = "Missing required field: $field";
            }
        }
        
        // Validate field types and constraints
        foreach ($schema['properties'] as $field => $constraints) {
            if (!isset($testData[$field])) continue;
            
            $value = $testData[$field];
            
            switch ($constraints['type']) {
                case 'string':
                    if (!is_string($value)) {
                        $validationErrors[] = "$field must be a string";
                    } else {
                        if (isset($constraints['minLength']) && strlen($value) < $constraints['minLength']) {
                            $validationErrors[] = "$field is too short";
                        }
                        if (isset($constraints['maxLength']) && strlen($value) > $constraints['maxLength']) {
                            $validationErrors[] = "$field is too long";
                        }
                        if (isset($constraints['format'])) {
                            switch ($constraints['format']) {
                                case 'email':
                                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                        $validationErrors[] = "$field must be a valid email";
                                    }
                                    break;
                                case 'uri':
                                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                        $validationErrors[] = "$field must be a valid URI";
                                    }
                                    break;
                            }
                        }
                    }
                    break;
                    
                case 'integer':
                    if (!is_int($value)) {
                        $validationErrors[] = "$field must be an integer";
                    } else {
                        if (isset($constraints['minimum']) && $value < $constraints['minimum']) {
                            $validationErrors[] = "$field is below minimum value";
                        }
                        if (isset($constraints['maximum']) && $value > $constraints['maximum']) {
                            $validationErrors[] = "$field exceeds maximum value";
                        }
                    }
                    break;
                    
                case 'array':
                    if (!is_array($value)) {
                        $validationErrors[] = "$field must be an array";
                    } else {
                        if (isset($constraints['maxItems']) && count($value) > $constraints['maxItems']) {
                            $validationErrors[] = "$field has too many items";
                        }
                    }
                    break;
            }
        }
        
        $isValid = empty($validationErrors);
        
        return [
            'validation_passed' => $isValid,
            'errors_found' => count($validationErrors),
            'fields_validated' => count($schema['properties']),
            'data_size' => strlen(json_encode($testData)),
            'schema_size' => strlen(json_encode($schema)),
            'validation_time' => microtime(true)
        ];
    }
);

// Test 6: JSON Error Handling
$results['json_error_handling'] = runBenchmark(
    'JSON Error Handling', 
    $args['iterations'], 
    function() {
        $malformedJsonStrings = [
            '{"name": "John", "age": 25,}', // Trailing comma
            '{"name": "John", "age": }', // Missing value
            '{"name": John", "age": 25}', // Missing quote
            '{name": "John", "age": 25}', // Missing opening quote
            '{"name": "John", "age": 25', // Missing closing brace
            '{"name": "John" "age": 25}', // Missing comma
            '{"name": "John", "age": "25"}extra', // Extra characters
            '', // Empty string
            'null', // null value
            'undefined' // Invalid literal
        ];
        
        $errorResults = [];
        
        foreach ($malformedJsonStrings as $jsonString) {
            $decoded = json_decode($jsonString, true);
            $error = json_last_error();
            $errorMessage = json_last_error_msg();
            
            $errorResults[] = [
                'input' => $jsonString,
                'decode_result' => $decoded,
                'error_code' => $error,
                'error_message' => $errorMessage,
                'has_error' => $error !== JSON_ERROR_NONE
            ];
        }
        
        // Test valid JSON for comparison
        $validJson = '{"name": "John", "age": 25, "active": true}';
        $validDecoded = json_decode($validJson, true);
        $validError = json_last_error();
        
        return [
            'malformed_samples' => count($malformedJsonStrings),
            'errors_detected' => count(array_filter($errorResults, fn($r) => $r['has_error'])),
            'error_detection_rate' => count(array_filter($errorResults, fn($r) => $r['has_error'])) / count($errorResults),
            'valid_json_processed' => $validError === JSON_ERROR_NONE,
            'processing_time' => microtime(true)
        ];
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'json-performance',
        'framework' => 'slim4',
        'version' => '4.x',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'json_features' => ['small_objects', 'medium_arrays', 'large_datasets', 'nested_structures', 'schema_validation', 'error_handling'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second')),
        'memory_peak' => memory_get_peak_usage(true),
        'status' => 'success'
    ]);
} else {
    // Display Summary
    echo "\n📊 Slim 4 JSON Performance Summary:\n";
    echo "==================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-35s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    
    echo "\n🏆 Total Memory Peak: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
    echo "🎯 Framework: Slim 4\n";
    echo "📄 JSON Features: Small Objects, Medium Arrays, Large Datasets, Nested Structures, Schema Validation, Error Handling\n";
    echo "⚡ Total JSON Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "🔄 JSON Status: " . (array_sum(array_column($results, 'ops_per_second')) > 5000 ? "HIGH PERFORMANCE ✅" : "NEEDS OPTIMIZATION ⚠️") . "\n";
    
    // Save results
    $reportData = [
        'benchmark' => 'json-performance',
        'framework' => 'slim4',
        'version' => '4.x',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'json_features' => ['small_objects', 'medium_arrays', 'large_datasets', 'nested_structures', 'schema_validation', 'error_handling'],
        'results' => $results,
        'total_peak_memory' => memory_get_peak_usage(true),
        'summary' => [
            'total_ops' => array_sum(array_column($results, 'ops_per_second')),
            'avg_ops' => round(array_sum(array_column($results, 'ops_per_second')) / count($results))
        ]
    ];
    
    $outputFile = dirname(__DIR__, 2) . '/results/raw/slim4-json-performance-' . date('Y-m-d-H-i-s') . '.json';
    @mkdir(dirname($outputFile), 0755, true);
    file_put_contents($outputFile, json_encode($reportData, JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to results/raw/\n";
    echo "✅ Slim 4 JSON performance benchmark completed successfully!\n";
}