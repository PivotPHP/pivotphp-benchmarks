#!/usr/bin/env php
<?php

/**
 * Comprehensive Realistic Benchmark Suite
 * Consolidates all important test scenarios with real-world data
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class ComprehensiveRealisticBenchmark
{
    private Application $app;
    private array $results = [];
    private array $randomData = [];
    
    public function __construct()
    {
        $this->app = new Application(__DIR__ . '/../');
        $this->initializeRandomData();
        $this->setupTestRoutes();
    }
    
    private function initializeRandomData(): void
    {
        // Initialize base data first
        $this->randomData = [
            'categories' => ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Toys', 'Beauty', 'Automotive'],
            'countries' => ['US', 'UK', 'CA', 'DE', 'FR', 'JP', 'AU', 'BR', 'IN', 'CN'],
            'cities' => ['New York', 'London', 'Toronto', 'Berlin', 'Paris', 'Tokyo', 'Sydney', 'São Paulo', 'Mumbai', 'Shanghai']
        ];
        
        // Then generate complex data that depends on base data
        $this->randomData['users'] = $this->generateUsers(1000);
        $this->randomData['products'] = $this->generateProducts(500);
        $this->randomData['orders'] = $this->generateOrders(200);
    }
    
    private function setupTestRoutes(): void
    {
        // Basic routes
        $this->app->get('/', function(Request $req, Response $res) {
            return $res->json(['message' => 'Welcome to API', 'timestamp' => time()]);
        });
        
        $this->app->get('/health', function(Request $req, Response $res) {
            return $res->json(['status' => 'healthy', 'timestamp' => time()]);
        });
        
        // User management
        $this->app->get('/users', function(Request $req, Response $res) {
            $limit = (int)($req->getQueryParam('limit') ?? 10);
            $offset = (int)($req->getQueryParam('offset') ?? 0);
            $users = array_slice($this->randomData['users'], $offset, $limit);
            return $res->json(['users' => $users, 'total' => count($this->randomData['users'])]);
        });
        
        $this->app->get('/users/{id}', function(Request $req, Response $res) {
            $id = (int)$req->getRouteParam('id');
            $user = $this->findUserById($id);
            return $user ? $res->json($user) : $res->json(['error' => 'User not found'], 404);
        });
        
        $this->app->post('/users', function(Request $req, Response $res) {
            $data = $req->getParsedBody();
            $user = $this->createUser($data);
            return $res->json($user, 201);
        });
        
        // Product catalog
        $this->app->get('/products', function(Request $req, Response $res) {
            $limit = (int)($req->getQueryParam('limit') ?? 20);
            $category = $req->getQueryParam('category');
            $products = $this->filterProducts($limit, $category);
            return $res->json($products);
        });
        
        $this->app->get('/products/{id}', function(Request $req, Response $res) {
            $id = (int)$req->getRouteParam('id');
            $product = $this->findProductById($id);
            return $product ? $res->json($product) : $res->json(['error' => 'Product not found'], 404);
        });
        
        // Search functionality
        $this->app->get('/search', function(Request $req, Response $res) {
            $query = $req->getQueryParam('q', '');
            $results = $this->performSearch($query);
            return $res->json($results);
        });
        
        // Order management
        $this->app->get('/orders', function(Request $req, Response $res) {
            $userId = $req->getQueryParam('user_id');
            $orders = $this->getOrdersForUser($userId);
            return $res->json($orders);
        });
        
        $this->app->post('/orders', function(Request $req, Response $res) {
            $data = $req->getParsedBody();
            $order = $this->createOrder($data);
            return $res->json($order, 201);
        });
        
        // Analytics endpoint
        $this->app->get('/analytics', function(Request $req, Response $res) {
            $analytics = $this->generateAnalytics();
            return $res->json($analytics);
        });
        
        // File upload simulation
        $this->app->post('/upload', function(Request $req, Response $res) {
            $result = $this->simulateFileUpload();
            return $res->json($result);
        });
    }
    
    private function generateUsers(int $count): array
    {
        $users = [];
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Chris', 'Lisa', 'Daniel', 'Amy'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Wilson', 'Moore'];
        
        for ($i = 1; $i <= $count; $i++) {
            $users[] = [
                'id' => $i,
                'name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
                'email' => 'user' . $i . '@example.com',
                'age' => rand(18, 80),
                'city' => $this->randomData['cities'][array_rand($this->randomData['cities'])],
                'country' => $this->randomData['countries'][array_rand($this->randomData['countries'])],
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                'is_active' => rand(0, 1) === 1,
                'total_orders' => rand(0, 50),
                'total_spent' => round(rand(0, 500000) / 100, 2)
            ];
        }
        
        return $users;
    }
    
    private function generateProducts(int $count): array
    {
        $products = [];
        $adjectives = ['Premium', 'Deluxe', 'Professional', 'Advanced', 'Classic', 'Modern', 'Eco-Friendly'];
        $nouns = ['Laptop', 'Phone', 'Tablet', 'Camera', 'Headphones', 'Speaker', 'Watch', 'Keyboard'];
        
        for ($i = 1; $i <= $count; $i++) {
            $products[] = [
                'id' => $i,
                'name' => $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' ' . rand(1, 50),
                'description' => 'High-quality product with excellent features and modern design. Perfect for professional and personal use.',
                'price' => round(rand(999, 199999) / 100, 2),
                'category' => $this->randomData['categories'][array_rand($this->randomData['categories'])],
                'sku' => 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'stock' => rand(0, 100),
                'rating' => round(rand(100, 500) / 100, 1),
                'reviews_count' => rand(0, 1000),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                'is_featured' => rand(0, 10) === 0,
                'specifications' => [
                    'weight' => rand(100, 5000) . 'g',
                    'color' => ['Black', 'White', 'Silver', 'Gold'][array_rand(['Black', 'White', 'Silver', 'Gold'])],
                    'warranty' => rand(1, 5) . ' years'
                ]
            ];
        }
        
        return $products;
    }
    
    private function generateOrders(int $count): array
    {
        $orders = [];
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        for ($i = 1; $i <= $count; $i++) {
            $orders[] = [
                'id' => $i,
                'user_id' => rand(1, 100),
                'total' => round(rand(1000, 100000) / 100, 2),
                'status' => $statuses[array_rand($statuses)],
                'items_count' => rand(1, 10),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                'shipping_address' => [
                    'street' => rand(1, 9999) . ' Main St',
                    'city' => $this->randomData['cities'][array_rand($this->randomData['cities'])],
                    'country' => $this->randomData['countries'][array_rand($this->randomData['countries'])],
                    'zip' => rand(10000, 99999)
                ]
            ];
        }
        
        return $orders;
    }
    
    private function findUserById(int $id): ?array
    {
        foreach ($this->randomData['users'] as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
    
    private function createUser(array $data): array
    {
        return [
            'id' => rand(10000, 99999),
            'name' => $data['name'] ?? 'New User',
            'email' => $data['email'] ?? 'newuser@example.com',
            'age' => $data['age'] ?? 25,
            'city' => $data['city'] ?? $this->randomData['cities'][array_rand($this->randomData['cities'])],
            'country' => $data['country'] ?? $this->randomData['countries'][array_rand($this->randomData['countries'])],
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => true,
            'total_orders' => 0,
            'total_spent' => 0.00
        ];
    }
    
    private function filterProducts(int $limit, ?string $category): array
    {
        $products = $this->randomData['products'];
        
        if ($category) {
            $products = array_filter($products, fn($p) => $p['category'] === $category);
        }
        
        return [
            'products' => array_slice($products, 0, $limit),
            'total' => count($products),
            'categories' => array_unique(array_column($this->randomData['products'], 'category'))
        ];
    }
    
    private function findProductById(int $id): ?array
    {
        foreach ($this->randomData['products'] as $product) {
            if ($product['id'] === $id) {
                return $product;
            }
        }
        return null;
    }
    
    private function performSearch(string $query): array
    {
        $results = [];
        
        if (strlen($query) >= 2) {
            foreach ($this->randomData['products'] as $product) {
                if (stripos($product['name'], $query) !== false || 
                    stripos($product['description'], $query) !== false) {
                    $results[] = $product;
                }
            }
        }
        
        return [
            'query' => $query,
            'results' => array_slice($results, 0, 20),
            'total_found' => count($results),
            'search_time' => round(rand(1, 100) / 1000, 3)
        ];
    }
    
    private function getOrdersForUser(?string $userId): array
    {
        if (!$userId) {
            return array_slice($this->randomData['orders'], 0, 10);
        }
        
        $userOrders = array_filter($this->randomData['orders'], fn($o) => $o['user_id'] == $userId);
        return array_values($userOrders);
    }
    
    private function createOrder(array $data): array
    {
        return [
            'id' => rand(10000, 99999),
            'user_id' => $data['user_id'] ?? rand(1, 100),
            'total' => $data['total'] ?? round(rand(1000, 50000) / 100, 2),
            'status' => 'pending',
            'items_count' => $data['items_count'] ?? rand(1, 5),
            'created_at' => date('Y-m-d H:i:s'),
            'shipping_address' => $data['shipping_address'] ?? [
                'street' => rand(1, 9999) . ' Main St',
                'city' => $this->randomData['cities'][array_rand($this->randomData['cities'])],
                'country' => $this->randomData['countries'][array_rand($this->randomData['countries'])],
                'zip' => rand(10000, 99999)
            ]
        ];
    }
    
    private function generateAnalytics(): array
    {
        return [
            'users' => [
                'total' => count($this->randomData['users']),
                'active' => rand(500, 800),
                'new_today' => rand(10, 50),
                'countries' => array_count_values(array_column($this->randomData['users'], 'country'))
            ],
            'products' => [
                'total' => count($this->randomData['products']),
                'categories' => array_count_values(array_column($this->randomData['products'], 'category')),
                'featured' => count(array_filter($this->randomData['products'], fn($p) => $p['is_featured']))
            ],
            'orders' => [
                'total' => count($this->randomData['orders']),
                'pending' => count(array_filter($this->randomData['orders'], fn($o) => $o['status'] === 'pending')),
                'total_revenue' => array_sum(array_column($this->randomData['orders'], 'total')),
                'average_order_value' => round(array_sum(array_column($this->randomData['orders'], 'total')) / count($this->randomData['orders']), 2)
            ],
            'monthly_sales' => array_map(fn($i) => [
                'month' => date('Y-m', strtotime("-{$i} months")),
                'revenue' => round(rand(10000, 100000) / 100, 2),
                'orders' => rand(50, 500)
            ], range(0, 11))
        ];
    }
    
    private function simulateFileUpload(): array
    {
        // Simulate processing time
        usleep(rand(100000, 500000)); // 100-500ms
        
        return [
            'success' => true,
            'file_id' => 'FILE_' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'filename' => 'upload_' . time() . '.jpg',
            'size' => rand(100000, 5000000),
            'url' => 'https://example.com/uploads/' . time() . '.jpg',
            'processed_at' => date('Y-m-d H:i:s')
        ];
    }
    
    public function runBenchmarkSuite(): array
    {
        echo "🚀 Comprehensive Realistic Benchmark Suite\n";
        echo "=========================================\n\n";
        
        $testScenarios = [
            'Basic Routes' => [
                ['GET', '/'],
                ['GET', '/health']
            ],
            'User Management' => [
                ['GET', '/users?limit=10&offset=0'],
                ['GET', '/users/123'],
                ['POST', '/users', ['name' => 'Test User', 'email' => 'test@example.com', 'age' => 30]]
            ],
            'Product Catalog' => [
                ['GET', '/products?limit=20'],
                ['GET', '/products?category=Electronics'],
                ['GET', '/products/456']
            ],
            'Search & Discovery' => [
                ['GET', '/search?q=laptop'],
                ['GET', '/search?q=phone'],
                ['GET', '/search?q=']
            ],
            'Order Processing' => [
                ['GET', '/orders?user_id=123'],
                ['POST', '/orders', ['user_id' => 123, 'total' => 99.99, 'items_count' => 2]]
            ],
            'Analytics & Reporting' => [
                ['GET', '/analytics']
            ],
            'File Operations' => [
                ['POST', '/upload', ['filename' => 'test.jpg']]
            ]
        ];
        
        $allResults = [];
        
        foreach ($testScenarios as $scenarioName => $requests) {
            echo "📊 Testing: {$scenarioName}\n";
            
            $scenarioResults = [];
            foreach ($requests as $request) {
                $method = $request[0];
                $uri = $request[1];
                $data = $request[2] ?? null;
                
                $result = $this->benchmarkRequest($method, $uri, $data);
                $scenarioResults[] = $result;
                
                echo "  {$method} {$uri}: {$result['ops_per_sec']} ops/sec\n";
            }
            
            $allResults[$scenarioName] = $scenarioResults;
            echo "\n";
        }
        
        return $allResults;
    }
    
    private function benchmarkRequest(string $method, string $uri, ?array $data = null, int $iterations = 500): array
    {
        // Warmup
        for ($i = 0; $i < 25; $i++) {
            $this->executeRequest($method, $uri, $data);
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $responseTimes = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $requestStart = microtime(true);
            $this->executeRequest($method, $uri, $data);
            $requestEnd = microtime(true);
            
            $responseTimes[] = ($requestEnd - $requestStart) * 1000; // ms
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage();
        
        $duration = $endTime - $startTime;
        $opsPerSec = $iterations / $duration;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        
        return [
            'method' => $method,
            'uri' => $uri,
            'ops_per_sec' => round($opsPerSec, 2),
            'duration' => round($duration, 4),
            'memory_mb' => round($memoryUsed, 2),
            'avg_response_time' => round(array_sum($responseTimes) / count($responseTimes), 2),
            'min_response_time' => round(min($responseTimes), 2),
            'max_response_time' => round(max($responseTimes), 2),
            'iterations' => $iterations
        ];
    }
    
    private function executeRequest(string $method, string $uri, ?array $data = null): void
    {
        // Create Request with required parameters (method, path, pathCallable)
        $request = new Request($method, $uri, '');
        
        // Set additional properties if needed
        if (method_exists($request, 'setMethod')) {
            $request->setMethod($method);
        }
        if (method_exists($request, 'setUri')) {
            $request->setUri($uri);
        }
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $request->setParsedBody($data);
        }
        
        $response = $this->app->handle($request);
        $response->getBody()->getContents(); // Force response processing
    }
    
    public function generateSummaryReport(array $results): array
    {
        $allRequests = [];
        
        foreach ($results as $scenarioName => $scenarioResults) {
            foreach ($scenarioResults as $result) {
                $allRequests[] = $result;
            }
        }
        
        $totalOps = array_sum(array_column($allRequests, 'ops_per_sec'));
        $avgMemory = array_sum(array_column($allRequests, 'memory_mb')) / count($allRequests);
        $avgResponseTime = array_sum(array_column($allRequests, 'avg_response_time')) / count($allRequests);
        $totalRequests = array_sum(array_column($allRequests, 'iterations'));
        
        return [
            'summary' => [
                'total_ops_per_sec' => round($totalOps, 2),
                'avg_memory_mb' => round($avgMemory, 2),
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'total_requests_tested' => $totalRequests,
                'scenarios_tested' => count($results),
                'endpoints_tested' => count($allRequests)
            ],
            'detailed_results' => $results
        ];
    }
}

// Execute comprehensive benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new ComprehensiveRealisticBenchmark();
    $results = $benchmark->runBenchmarkSuite();
    $summary = $benchmark->generateSummaryReport($results);
    
    echo "🏆 Comprehensive Benchmark Summary:\n";
    echo "==================================\n";
    echo "Total Operations/sec: " . number_format($summary['summary']['total_ops_per_sec'], 2) . "\n";
    echo "Average Memory Usage: " . number_format($summary['summary']['avg_memory_mb'], 2) . " MB\n";
    echo "Average Response Time: " . number_format($summary['summary']['avg_response_time_ms'], 2) . " ms\n";
    echo "Total Requests Tested: " . number_format($summary['summary']['total_requests_tested']) . "\n";
    echo "Scenarios Tested: " . $summary['summary']['scenarios_tested'] . "\n";
    echo "Endpoints Tested: " . $summary['summary']['endpoints_tested'] . "\n";
    
    // Save comprehensive results
    $timestamp = date('Y-m-d-H-i-s');
    $filename = "/tmp/comprehensive-realistic-benchmark-{$timestamp}.json";
    file_put_contents($filename, json_encode(array_merge($summary, [
        'timestamp' => $timestamp,
        'framework' => 'PivotPHP Core v1.1.1',
        'benchmark_type' => 'comprehensive_realistic_scenarios'
    ]), JSON_PRETTY_PRINT));
    
    echo "\n💾 Comprehensive results saved to: {$filename}\n";
    echo "✅ Realistic benchmark suite completed successfully!\n";
}