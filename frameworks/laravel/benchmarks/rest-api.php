#!/usr/bin/env php
<?php

/**
 * Laravel REST API Performance Benchmark
 * Tests complete REST API operations with data processing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    echo "==================================================\n";
    echo " Laravel REST API Performance Benchmark\n";
    echo "==================================================\n\n";
}

// In-memory data store for testing
$dataStore = [];
$nextId = 1;

$results = [];

// Test 1: GET /users (List with pagination)
$results['get_users_list'] = runBenchmark(
    'GET Users List (Paginated)', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $request = Request::create('/api/v1/users?page=1&limit=10&sort=name', 'GET');
        
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, min(100, (int) $request->get('limit', 10)));
        $sort = $request->get('sort', 'id');
        
        $users = array_slice($dataStore, ($page - 1) * $limit, $limit);
        
        if ($sort === 'name') {
            usort($users, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        }
        
        return response()->json([
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($dataStore),
                'pages' => ceil(count($dataStore) / $limit)
            ],
            'framework' => 'laravel'
        ]);
    }
);

// Test 2: GET /users/{id} (Single resource)
$results['get_user_single'] = runBenchmark(
    'GET Single User', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $id = rand(1, 1000);
        $request = Request::create("/api/v1/users/$id", 'GET');
        
        $user = $dataStore[$id] ?? [
            'id' => $id,
            'name' => "User $id",
            'email' => "user$id@example.com",
            'created_at' => now()->toISOString()
        ];
        
        if (!isset($dataStore[$id])) {
            return response()->json([
                'error' => 'User not found',
                'framework' => 'laravel'
            ], 404);
        }
        
        return response()->json([
            'data' => $user,
            'framework' => 'laravel'
        ]);
    }
);

// Test 3: POST /users (Create)
$results['post_user_create'] = runBenchmark(
    'POST Create User', 
    $args['iterations'], 
    function() use (&$dataStore, &$nextId) {
        $userData = [
            'name' => 'Test User ' . rand(1000, 9999),
            'email' => 'user' . rand(1000, 9999) . '@example.com',
        ];
        
        $request = Request::create('/api/v1/users', 'POST', $userData);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }
        
        $user = [
            'id' => $nextId++,
            'name' => $userData['name'],
            'email' => $userData['email'],
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];
        
        $dataStore[$user['id']] = $user;
        
        return response()->json([
            'data' => $user,
            'message' => 'User created successfully',
            'framework' => 'laravel'
        ], 201);
    }
);

// Test 4: PUT /users/{id} (Full Update)
$results['put_user_update'] = runBenchmark(
    'PUT Update User', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $id = rand(1, 100);
        $userData = [
            'name' => 'Updated User ' . rand(1000, 9999),
            'email' => 'updated' . rand(1000, 9999) . '@example.com',
        ];
        
        $request = Request::create("/api/v1/users/$id", 'PUT', $userData);
        
        if (!isset($dataStore[$id])) {
            $dataStore[$id] = [
                'id' => $id,
                'name' => 'Default User',
                'email' => 'default@example.com',
                'created_at' => now()->toISOString()
            ];
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }
        
        $dataStore[$id] = array_merge($dataStore[$id], $userData, [
            'updated_at' => now()->toISOString()
        ]);
        
        return response()->json([
            'data' => $dataStore[$id],
            'message' => 'User updated successfully',
            'framework' => 'laravel'
        ]);
    }
);

// Test 5: PATCH /users/{id} (Partial Update)
$results['patch_user_partial'] = runBenchmark(
    'PATCH Partial Update', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $id = rand(1, 100);
        $updateFields = ['name' => 'Patched User ' . rand(1000, 9999)];
        
        $request = Request::create("/api/v1/users/$id", 'PATCH', $updateFields);
        
        if (!isset($dataStore[$id])) {
            $dataStore[$id] = [
                'id' => $id,
                'name' => 'Default User',
                'email' => 'default@example.com',
                'created_at' => now()->toISOString()
            ];
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }
        
        $dataStore[$id] = array_merge($dataStore[$id], $updateFields, [
            'updated_at' => now()->toISOString()
        ]);
        
        return response()->json([
            'data' => $dataStore[$id],
            'message' => 'User updated successfully',
            'framework' => 'laravel'
        ]);
    }
);

// Test 6: DELETE /users/{id}
$results['delete_user'] = runBenchmark(
    'DELETE User', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $id = rand(1, 100);
        $request = Request::create("/api/v1/users/$id", 'DELETE');
        
        if (!isset($dataStore[$id])) {
            return response()->json([
                'error' => 'User not found',
                'framework' => 'laravel'
            ], 404);
        }
        
        unset($dataStore[$id]);
        
        return response()->json([
            'message' => 'User deleted successfully',
            'framework' => 'laravel'
        ]);
    }
);

// Test 7: Complex Query with Filtering
$results['complex_query_filtering'] = runBenchmark(
    'Complex Query with Filtering', 
    $args['iterations'], 
    function() use (&$dataStore) {
        $request = Request::create('/api/v1/users?filter[name]=Test&filter[status]=active&sort=created_at&order=desc', 'GET');
        
        $filters = $request->get('filter', []);
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        
        $filteredUsers = array_filter($dataStore, function($user) use ($filters) {
            foreach ($filters as $field => $value) {
                if ($field === 'name' && strpos($user['name'] ?? '', $value) === false) {
                    return false;
                }
                if ($field === 'status' && ($user['status'] ?? 'active') !== $value) {
                    return false;
                }
            }
            return true;
        });
        
        if ($sort === 'created_at') {
            uasort($filteredUsers, function($a, $b) use ($order) {
                $result = strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
                return $order === 'desc' ? -$result : $result;
            });
        }
        
        return response()->json([
            'data' => array_values($filteredUsers),
            'filters_applied' => $filters,
            'sort' => $sort,
            'order' => $order,
            'total_filtered' => count($filteredUsers),
            'framework' => 'laravel'
        ]);
    }
);

if ($args['json']) {
    echo json_encode([
        'benchmark' => 'rest-api',
        'framework' => 'laravel',
        'version' => '10.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'iterations' => $args['iterations'],
        'rest_operations' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        'results' => $results,
        'total_ops' => array_sum(array_column($results, 'ops_per_second'))
    ]);
} else {
    echo "\n📊 Laravel REST API Performance Summary:\n";
    echo "========================================\n";
    foreach ($results as $test => $result) {
        echo sprintf("%-30s: %8s ops/sec\n", $test, number_format($result['ops_per_second']));
    }
    echo "\n🏆 Total REST API Ops: " . number_format(array_sum(array_column($results, 'ops_per_second'))) . " ops/sec\n";
    echo "✅ Laravel REST API benchmark completed!\n";
}