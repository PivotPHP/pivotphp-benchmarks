#!/usr/bin/env php
<?php

/**
 * Realistic API Benchmark Suite
 * Simulates real-world API scenarios with random data
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class RealisticAPIBenchmark
{
    private Application $app;
    private array $sampleData = [];
    private array $results = [];
    
    public function __construct()
    {
        $this->app = new Application(__DIR__ . '/../');
        $this->setupRoutes();
        $this->generateSampleData();
    }
    
    private function setupRoutes(): void
    {
        // E-commerce API routes
        $this->app->get('/api/products', function(Request $req, Response $res) {
            $limit = (int)($req->getQueryParam('limit') ?? 20);
            $offset = (int)($req->getQueryParam('offset') ?? 0);
            $category = $req->getQueryParam('category');
            
            $products = $this->getRandomProducts($limit, $offset, $category);
            return $res->json([
                'data' => $products,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => count($this->sampleData['products'])
                ]
            ]);
        });
        
        $this->app->get('/api/products/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            $product = $this->getRandomProduct($id);
            
            if (!$product) {
                return $res->json(['error' => 'Product not found'], 404);
            }
            
            return $res->json($product);
        });
        
        $this->app->post('/api/products', function(Request $req, Response $res) {
            $data = $req->getParsedBody();
            
            // Simulate validation
            if (!isset($data['name']) || !isset($data['price'])) {
                return $res->json(['error' => 'Missing required fields'], 400);
            }
            
            $product = $this->createRandomProduct($data);
            return $res->json($product, 201);
        });
        
        $this->app->put('/api/products/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            $data = $req->getParsedBody();
            
            $product = $this->updateRandomProduct($id, $data);
            return $res->json($product);
        });
        
        $this->app->delete('/api/products/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            
            // Simulate deletion
            return $res->json(['message' => 'Product deleted successfully']);
        });
        
        // User management routes
        $this->app->get('/api/users', function(Request $req, Response $res) {
            $users = $this->getRandomUsers(10);
            return $res->json($users);
        });
        
        $this->app->post('/api/users', function(Request $req, Response $res) {
            $data = $req->getParsedBody();
            $user = $this->createRandomUser($data);
            return $res->json($user, 201);
        });
        
        // Analytics routes
        $this->app->get('/api/analytics/sales', function(Request $req, Response $res) {
            $analytics = $this->getRandomAnalytics('sales');
            return $res->json($analytics);
        });
        
        $this->app->get('/api/analytics/users', function(Request $req, Response $res) {
            $analytics = $this->getRandomAnalytics('users');
            return $res->json($analytics);
        });
        
        // Search routes
        $this->app->get('/api/search', function(Request $req, Response $res) {
            $query = $req->getQueryParam('q', '');
            $results = $this->performRandomSearch($query);
            return $res->json($results);
        });
    }
    
    private function generateSampleData(): void
    {
        $this->sampleData = [
            'products' => $this->generateRandomProducts(1000),
            'users' => $this->generateRandomUsers(500),
            'categories' => ['electronics', 'clothing', 'books', 'home', 'sports', 'toys'],
            'brands' => ['Apple', 'Samsung', 'Nike', 'Adidas', 'Sony', 'Microsoft', 'Google'],
            'countries' => ['US', 'UK', 'DE', 'FR', 'BR', 'JP', 'CA', 'AU', 'IN', 'CN']
        ];
    }
    
    private function generateRandomProducts(int $count): array
    {
        $products = [];
        $categories = $this->sampleData['categories'] ?? ['electronics', 'clothing', 'books'];
        $brands = $this->sampleData['brands'] ?? ['Apple', 'Samsung', 'Nike'];
        
        for ($i = 1; $i <= $count; $i++) {
            $products[] = [
                'id' => $i,
                'name' => $this->generateRandomProductName(),
                'description' => $this->generateRandomDescription(),
                'price' => round(rand(999, 999999) / 100, 2),
                'category' => $categories[array_rand($categories)],
                'brand' => $brands[array_rand($brands)],
                'sku' => strtoupper(substr(md5(uniqid()), 0, 8)),
                'in_stock' => rand(0, 100),
                'rating' => round(rand(100, 500) / 100, 1),
                'reviews_count' => rand(0, 1000),
                'tags' => $this->generateRandomTags(),
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                'updated_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30)),
                'images' => $this->generateRandomImages(),
                'specifications' => $this->generateRandomSpecs(),
                'shipping' => [
                    'weight' => round(rand(100, 5000) / 100, 2),
                    'dimensions' => [
                        'width' => rand(5, 50),
                        'height' => rand(5, 50),
                        'depth' => rand(5, 50)
                    ],
                    'free_shipping' => rand(0, 1) === 1
                ]
            ];
        }
        
        return $products;
    }
    
    private function generateRandomUsers(int $count): array
    {
        $users = [];
        $countries = $this->sampleData['countries'] ?? ['US', 'UK', 'DE'];
        
        for ($i = 1; $i <= $count; $i++) {
            $users[] = [
                'id' => $i,
                'name' => $this->generateRandomName(),
                'email' => $this->generateRandomEmail(),
                'phone' => $this->generateRandomPhone(),
                'country' => $countries[array_rand($countries)],
                'city' => $this->generateRandomCity(),
                'address' => $this->generateRandomAddress(),
                'age' => rand(18, 80),
                'registration_date' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365 * 5)),
                'last_login' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 30)),
                'orders_count' => rand(0, 50),
                'total_spent' => round(rand(0, 500000) / 100, 2),
                'preferences' => $this->generateRandomPreferences(),
                'is_active' => rand(0, 1) === 1,
                'is_verified' => rand(0, 1) === 1
            ];
        }
        
        return $users;
    }
    
    private function generateRandomProductName(): string
    {
        $adjectives = ['Smart', 'Pro', 'Ultra', 'Premium', 'Deluxe', 'Essential', 'Advanced'];
        $nouns = ['Phone', 'Laptop', 'Tablet', 'Watch', 'Camera', 'Speaker', 'Headphones'];
        
        return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' ' . rand(1, 20);
    }
    
    private function generateRandomDescription(): string
    {
        $templates = [
            'High-quality product with excellent features and modern design.',
            'Professional grade equipment for demanding users.',
            'Innovative technology meets sleek design in this premium product.',
            'Perfect for both professional and personal use.',
            'State-of-the-art features in a compact and stylish package.'
        ];
        
        return $templates[array_rand($templates)];
    }
    
    private function generateRandomTags(): array
    {
        $allTags = ['new', 'bestseller', 'sale', 'featured', 'premium', 'eco-friendly', 'limited'];
        $count = rand(0, 3);
        return array_slice($allTags, 0, $count);
    }
    
    private function generateRandomImages(): array
    {
        $count = rand(1, 5);
        $images = [];
        
        for ($i = 0; $i < $count; $i++) {
            $images[] = [
                'url' => "https://images.example.com/product_" . rand(1000, 9999) . ".jpg",
                'alt' => "Product image " . ($i + 1),
                'is_primary' => $i === 0
            ];
        }
        
        return $images;
    }
    
    private function generateRandomSpecs(): array
    {
        $specs = [
            'Color' => ['Black', 'White', 'Silver', 'Gold', 'Blue'][array_rand(['Black', 'White', 'Silver', 'Gold', 'Blue'])],
            'Material' => ['Plastic', 'Metal', 'Glass', 'Fabric', 'Leather'][array_rand(['Plastic', 'Metal', 'Glass', 'Fabric', 'Leather'])],
            'Warranty' => rand(1, 5) . ' years',
            'Model' => 'Model-' . rand(1000, 9999)
        ];
        
        return $specs;
    }
    
    private function generateRandomName(): string
    {
        $firstNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Lisa', 'Robert', 'Emma'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
    
    private function generateRandomEmail(): string
    {
        $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com'];
        $username = strtolower(substr(md5(uniqid()), 0, 8));
        
        return $username . '@' . $domains[array_rand($domains)];
    }
    
    private function generateRandomPhone(): string
    {
        return '+' . rand(1, 99) . ' ' . rand(100, 999) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999);
    }
    
    private function generateRandomCity(): string
    {
        $cities = ['New York', 'London', 'Paris', 'Tokyo', 'Sydney', 'Berlin', 'Toronto', 'Mumbai'];
        return $cities[array_rand($cities)];
    }
    
    private function generateRandomAddress(): string
    {
        return rand(1, 9999) . ' ' . ['Main St', 'Oak Ave', 'First St', 'Second Ave'][array_rand(['Main St', 'Oak Ave', 'First St', 'Second Ave'])];
    }
    
    private function generateRandomPreferences(): array
    {
        return [
            'newsletter' => rand(0, 1) === 1,
            'sms_notifications' => rand(0, 1) === 1,
            'preferred_language' => ['en', 'es', 'fr', 'de'][array_rand(['en', 'es', 'fr', 'de'])],
            'currency' => ['USD', 'EUR', 'GBP', 'JPY'][array_rand(['USD', 'EUR', 'GBP', 'JPY'])]
        ];
    }
    
    private function getRandomProducts(int $limit, int $offset, ?string $category): array
    {
        $products = $this->sampleData['products'];
        
        if ($category) {
            $products = array_filter($products, fn($p) => $p['category'] === $category);
        }
        
        return array_slice($products, $offset, $limit);
    }
    
    private function getRandomProduct(string $id): ?array
    {
        $products = $this->sampleData['products'];
        $product = array_filter($products, fn($p) => $p['id'] == $id);
        
        return $product ? array_values($product)[0] : null;
    }
    
    private function createRandomProduct(array $data): array
    {
        return array_merge([
            'id' => rand(10000, 99999),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], $data);
    }
    
    private function updateRandomProduct(string $id, array $data): array
    {
        $product = $this->getRandomProduct($id);
        
        if (!$product) {
            return $this->createRandomProduct($data);
        }
        
        return array_merge($product, $data, ['updated_at' => date('Y-m-d H:i:s')]);
    }
    
    private function getRandomUsers(int $limit): array
    {
        return array_slice($this->sampleData['users'], 0, $limit);
    }
    
    private function createRandomUser(array $data): array
    {
        return array_merge([
            'id' => rand(10000, 99999),
            'registration_date' => date('Y-m-d H:i:s'),
            'is_active' => true,
            'orders_count' => 0,
            'total_spent' => 0.00
        ], $data);
    }
    
    private function getRandomAnalytics(string $type): array
    {
        switch ($type) {
            case 'sales':
                return [
                    'total_revenue' => round(rand(10000, 1000000) / 100, 2),
                    'total_orders' => rand(100, 10000),
                    'average_order_value' => round(rand(2000, 15000) / 100, 2),
                    'top_products' => array_slice($this->sampleData['products'], 0, 5),
                    'monthly_data' => $this->generateMonthlyData()
                ];
            case 'users':
                return [
                    'total_users' => count($this->sampleData['users']),
                    'active_users' => rand(50, 400),
                    'new_users_today' => rand(0, 50),
                    'user_retention' => round(rand(6000, 9000) / 100, 1),
                    'demographics' => $this->generateDemographics()
                ];
            default:
                return [];
        }
    }
    
    private function generateMonthlyData(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-{$i} months"));
            $data[] = [
                'month' => $date,
                'revenue' => round(rand(5000, 50000) / 100, 2),
                'orders' => rand(50, 500)
            ];
        }
        return $data;
    }
    
    private function generateDemographics(): array
    {
        return [
            'age_groups' => [
                '18-25' => rand(10, 30),
                '26-35' => rand(20, 40),
                '36-45' => rand(15, 35),
                '46-55' => rand(10, 25),
                '56+' => rand(5, 20)
            ],
            'countries' => [
                'US' => rand(30, 50),
                'UK' => rand(10, 25),
                'DE' => rand(8, 20),
                'FR' => rand(5, 15),
                'Others' => rand(10, 30)
            ]
        ];
    }
    
    private function performRandomSearch(string $query): array
    {
        $results = [];
        
        if (strlen($query) >= 2) {
            $matchingProducts = array_filter(
                $this->sampleData['products'],
                fn($p) => stripos($p['name'], $query) !== false || stripos($p['description'], $query) !== false
            );
            
            $results = array_slice($matchingProducts, 0, 10);
        }
        
        return [
            'query' => $query,
            'results' => $results,
            'total_found' => count($results),
            'search_time' => round(rand(1, 50) / 1000, 3)
        ];
    }
    
    public function runBenchmark(): array
    {
        echo "🚀 Realistic API Benchmark Suite\n";
        echo "===============================\n\n";
        
        $scenarios = [
            'GET /api/products' => ['GET', '/api/products?limit=20&offset=0'],
            'GET /api/products/{id}' => ['GET', '/api/products/123'],
            'POST /api/products' => ['POST', '/api/products', $this->generateSampleProductData()],
            'PUT /api/products/{id}' => ['PUT', '/api/products/123', $this->generateSampleProductData()],
            'DELETE /api/products/{id}' => ['DELETE', '/api/products/123'],
            'GET /api/users' => ['GET', '/api/users'],
            'POST /api/users' => ['POST', '/api/users', $this->generateSampleUserData()],
            'GET /api/analytics/sales' => ['GET', '/api/analytics/sales'],
            'GET /api/analytics/users' => ['GET', '/api/analytics/users'],
            'GET /api/search' => ['GET', '/api/search?q=smartphone']
        ];
        
        $results = [];
        
        foreach ($scenarios as $name => $scenario) {
            echo "📊 Testing: {$name}\n";
            
            $method = $scenario[0];
            $uri = $scenario[1];
            $data = $scenario[2] ?? null;
            
            $result = $this->benchmarkScenario($method, $uri, $data);
            $results[$name] = $result;
            
            echo "  Performance: {$result['ops_per_sec']} ops/sec\n";
            echo "  Memory: {$result['memory_mb']} MB\n";
            echo "  Response time: {$result['avg_response_time']}ms\n\n";
        }
        
        return $results;
    }
    
    private function benchmarkScenario(string $method, string $uri, ?array $data = null, int $iterations = 1000): array
    {
        // Warmup
        for ($i = 0; $i < 50; $i++) {
            $this->simulateRequest($method, $uri, $data);
        }
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $responseTimes = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $requestStart = microtime(true);
            $this->simulateRequest($method, $uri, $data);
            $requestEnd = microtime(true);
            
            $responseTimes[] = ($requestEnd - $requestStart) * 1000; // Convert to milliseconds
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage();
        
        $duration = $endTime - $startTime;
        $opsPerSec = $iterations / $duration;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024;
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        
        return [
            'ops_per_sec' => round($opsPerSec, 2),
            'duration' => round($duration, 4),
            'memory_mb' => round($memoryUsed, 2),
            'avg_response_time' => round($avgResponseTime, 2),
            'min_response_time' => round(min($responseTimes), 2),
            'max_response_time' => round(max($responseTimes), 2),
            'iterations' => $iterations
        ];
    }
    
    private function simulateRequest(string $method, string $uri, ?array $data = null): void
    {
        $request = $this->createRequest($method, $uri, $data);
        $response = $this->app->handle($request);
        
        // Simulate response processing
        $response->getBody()->getContents();
    }
    
    private function createRequest(string $method, string $uri, ?array $data = null): Request
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
            $request->setHeader('Content-Type', 'application/json');
        }
        
        return $request;
    }
    
    private function generateSampleProductData(): array
    {
        return [
            'name' => $this->generateRandomProductName(),
            'description' => $this->generateRandomDescription(),
            'price' => round(rand(999, 99999) / 100, 2),
            'category' => $this->sampleData['categories'][array_rand($this->sampleData['categories'])],
            'brand' => $this->sampleData['brands'][array_rand($this->sampleData['brands'])],
            'in_stock' => rand(0, 100)
        ];
    }
    
    private function generateSampleUserData(): array
    {
        return [
            'name' => $this->generateRandomName(),
            'email' => $this->generateRandomEmail(),
            'phone' => $this->generateRandomPhone(),
            'country' => $this->sampleData['countries'][array_rand($this->sampleData['countries'])],
            'city' => $this->generateRandomCity(),
            'age' => rand(18, 80)
        ];
    }
}

// Execute benchmark
if (php_sapi_name() === 'cli') {
    $benchmark = new RealisticAPIBenchmark();
    $results = $benchmark->runBenchmark();
    
    // Calculate overall performance
    $totalOps = array_sum(array_column($results, 'ops_per_sec'));
    $avgMemory = array_sum(array_column($results, 'memory_mb')) / count($results);
    $avgResponseTime = array_sum(array_column($results, 'avg_response_time')) / count($results);
    
    echo "🏆 Overall Performance Summary:\n";
    echo "==============================\n";
    echo "Total Operations/sec: " . number_format($totalOps, 2) . "\n";
    echo "Average Memory: " . number_format($avgMemory, 2) . " MB\n";
    echo "Average Response Time: " . number_format($avgResponseTime, 2) . " ms\n";
    echo "Scenarios Tested: " . count($results) . "\n";
    
    // Save results
    $timestamp = date('Y-m-d-H-i-s');
    $filename = "/tmp/realistic-api-benchmark-{$timestamp}.json";
    file_put_contents($filename, json_encode([
        'timestamp' => $timestamp,
        'framework' => 'PivotPHP Core v1.1.1',
        'benchmark_type' => 'realistic_api_scenarios',
        'overall_performance' => [
            'total_ops_per_sec' => $totalOps,
            'avg_memory_mb' => $avgMemory,
            'avg_response_time_ms' => $avgResponseTime
        ],
        'detailed_results' => $results
    ], JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to: {$filename}\n";
}