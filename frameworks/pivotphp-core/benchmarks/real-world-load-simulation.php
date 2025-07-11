#!/usr/bin/env php
<?php

/**
 * Real-World Load Simulation Benchmark
 * Simulates realistic traffic patterns and user behavior
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class RealWorldLoadSimulation
{
    private Application $app;
    private array $trafficPatterns = [];
    private array $userBehaviors = [];
    private array $results = [];
    
    public function __construct()
    {
        $this->app = new Application(__DIR__ . '/../');
        $this->setupRealisticRoutes();
        $this->defineTrafficPatterns();
        $this->defineUserBehaviors();
    }
    
    private function setupRealisticRoutes(): void
    {
        // Home page and landing pages
        $this->app->get('/', function(Request $req, Response $res) {
            return $res->json(['page' => 'home', 'load_time' => microtime(true)]);
        });
        
        $this->app->get('/about', function(Request $req, Response $res) {
            return $res->json(['page' => 'about', 'content' => $this->generatePageContent()]);
        });
        
        // Product catalog (high traffic)
        $this->app->get('/products', function(Request $req, Response $res) {
            $page = (int)($req->getQueryParam('page') ?? 1);
            $limit = (int)($req->getQueryParam('limit') ?? 24);
            $category = $req->getQueryParam('category');
            $sort = $req->getQueryParam('sort', 'name');
            
            $products = $this->generateProductListing($page, $limit, $category, $sort);
            return $res->json($products);
        });
        
        $this->app->get('/products/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            $product = $this->generateProductDetails($id);
            return $res->json($product);
        });
        
        // Search functionality (CPU intensive)
        $this->app->get('/search', function(Request $req, Response $res) {
            $query = $req->getQueryParam('q', '');
            $filters = $req->getQueryParam('filters', []);
            
            $results = $this->performComplexSearch($query, $filters);
            return $res->json($results);
        });
        
        // User authentication
        $this->app->post('/auth/login', function(Request $req, Response $res) {
            $credentials = $req->getParsedBody();
            $result = $this->simulateAuthentication($credentials);
            return $res->json($result);
        });
        
        $this->app->post('/auth/register', function(Request $req, Response $res) {
            $userData = $req->getParsedBody();
            $result = $this->simulateRegistration($userData);
            return $res->json($result, 201);
        });
        
        // Shopping cart operations
        $this->app->get('/cart', function(Request $req, Response $res) {
            $cart = $this->generateUserCart();
            return $res->json($cart);
        });
        
        $this->app->post('/cart/add', function(Request $req, Response $res) {
            $item = $req->getParsedBody();
            $result = $this->simulateAddToCart($item);
            return $res->json($result);
        });
        
        $this->app->delete('/cart/remove/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            $result = $this->simulateRemoveFromCart($id);
            return $res->json($result);
        });
        
        // Checkout process (complex operations)
        $this->app->post('/checkout', function(Request $req, Response $res) {
            $orderData = $req->getParsedBody();
            $result = $this->simulateCheckout($orderData);
            return $res->json($result);
        });
        
        // User profile and orders
        $this->app->get('/profile', function(Request $req, Response $res) {
            $profile = $this->generateUserProfile();
            return $res->json($profile);
        });
        
        $this->app->get('/orders', function(Request $req, Response $res) {
            $orders = $this->generateUserOrders();
            return $res->json($orders);
        });
        
        $this->app->get('/orders/{id}', function(Request $req, Response $res) {
            $id = $req->getRouteParam('id');
            $order = $this->generateOrderDetails($id);
            return $res->json($order);
        });
        
        // Analytics and reporting (heavy JSON)
        $this->app->get('/admin/analytics', function(Request $req, Response $res) {
            $analytics = $this->generateHeavyAnalytics();
            return $res->json($analytics);
        });
        
        // File upload simulation
        $this->app->post('/upload', function(Request $req, Response $res) {
            $result = $this->simulateFileUpload();
            return $res->json($result);
        });
    }
    
    private function defineTrafficPatterns(): void
    {
        $this->trafficPatterns = [
            'peak_hours' => [
                'name' => 'Peak Hours (9-11 AM, 7-9 PM)',
                'traffic_multiplier' => 3.0,
                'duration_minutes' => 120,
                'concurrent_users' => 500,
                'requests_per_user' => 15
            ],
            'normal_hours' => [
                'name' => 'Normal Hours',
                'traffic_multiplier' => 1.0,
                'duration_minutes' => 480,
                'concurrent_users' => 150,
                'requests_per_user' => 8
            ],
            'low_traffic' => [
                'name' => 'Low Traffic (Late Night)',
                'traffic_multiplier' => 0.3,
                'duration_minutes' => 360,
                'concurrent_users' => 50,
                'requests_per_user' => 5
            ],
            'flash_sale' => [
                'name' => 'Flash Sale Event',
                'traffic_multiplier' => 10.0,
                'duration_minutes' => 30,
                'concurrent_users' => 2000,
                'requests_per_user' => 25
            ]
        ];
    }
    
    private function defineUserBehaviors(): void
    {
        $this->userBehaviors = [
            'browser' => [
                'name' => 'Product Browser',
                'probability' => 0.4,
                'actions' => [
                    ['GET', '/', 1.0],
                    ['GET', '/products', 0.8],
                    ['GET', '/products/{id}', 0.6],
                    ['GET', '/search', 0.3],
                    ['POST', '/cart/add', 0.2]
                ]
            ],
            'buyer' => [
                'name' => 'Active Buyer',
                'probability' => 0.2,
                'actions' => [
                    ['GET', '/', 1.0],
                    ['GET', '/products', 0.9],
                    ['GET', '/products/{id}', 0.8],
                    ['POST', '/cart/add', 0.7],
                    ['GET', '/cart', 0.6],
                    ['POST', '/checkout', 0.4]
                ]
            ],
            'registered_user' => [
                'name' => 'Registered User',
                'probability' => 0.25,
                'actions' => [
                    ['POST', '/auth/login', 1.0],
                    ['GET', '/profile', 0.8],
                    ['GET', '/orders', 0.6],
                    ['GET', '/products', 0.7],
                    ['POST', '/cart/add', 0.3]
                ]
            ],
            'searcher' => [
                'name' => 'Product Searcher',
                'probability' => 0.15,
                'actions' => [
                    ['GET', '/', 1.0],
                    ['GET', '/search', 0.9],
                    ['GET', '/products/{id}', 0.5],
                    ['POST', '/cart/add', 0.1]
                ]
            ]
        ];
    }
    
    public function simulateTrafficPattern(string $patternName, int $testDuration = 60): array
    {
        if (!isset($this->trafficPatterns[$patternName])) {
            throw new InvalidArgumentException("Unknown traffic pattern: {$patternName}");
        }
        
        $pattern = $this->trafficPatterns[$patternName];
        echo "🚀 Simulating: {$pattern['name']}\n";
        echo "Duration: {$testDuration} seconds\n";
        echo "Concurrent Users: {$pattern['concurrent_users']}\n";
        echo "Requests per User: {$pattern['requests_per_user']}\n\n";
        
        $startTime = microtime(true);
        $requests = [];
        $errors = 0;
        $totalRequests = 0;
        
        // Simulate concurrent users
        for ($second = 0; $second < $testDuration; $second++) {
            $currentUsers = $this->calculateCurrentUsers($pattern, $second, $testDuration);
            $requestsThisSecond = [];
            
            for ($user = 0; $user < $currentUsers; $user++) {
                $behavior = $this->selectUserBehavior();
                $userRequests = $this->generateUserRequests($behavior, $pattern['requests_per_user']);
                
                foreach ($userRequests as $request) {
                    $requestStart = microtime(true);
                    
                    try {
                        $response = $this->executeRequest($request);
                        $requestEnd = microtime(true);
                        
                        $requestsThisSecond[] = [
                            'method' => $request['method'],
                            'uri' => $request['uri'],
                            'duration' => $requestEnd - $requestStart,
                            'status' => 200,
                            'user_type' => $behavior['name']
                        ];
                        
                        $totalRequests++;
                    } catch (Exception $e) {
                        $errors++;
                        $totalRequests++;
                    }
                }
            }
            
            $requests[] = [
                'second' => $second,
                'users' => $currentUsers,
                'requests' => $requestsThisSecond,
                'requests_count' => count($requestsThisSecond)
            ];
            
            // Simulate real-time delay
            if ($second < $testDuration - 1) {
                usleep(100000); // 100ms delay between seconds
            }
        }
        
        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;
        
        return [
            'pattern' => $pattern['name'],
            'duration' => $totalDuration,
            'total_requests' => $totalRequests,
            'errors' => $errors,
            'success_rate' => (($totalRequests - $errors) / max($totalRequests, 1)) * 100,
            'avg_requests_per_second' => $totalRequests / $totalDuration,
            'peak_concurrent_users' => $pattern['concurrent_users'],
            'detailed_timeline' => $requests
        ];
    }
    
    private function calculateCurrentUsers(array $pattern, int $currentSecond, int $totalDuration): int
    {
        $baseUsers = $pattern['concurrent_users'];
        $multiplier = $pattern['traffic_multiplier'];
        
        // Simulate traffic curve (normal distribution-like)
        $midPoint = $totalDuration / 2;
        $variance = $totalDuration / 4;
        
        $curve = exp(-pow($currentSecond - $midPoint, 2) / (2 * pow($variance, 2)));
        $users = (int)($baseUsers * $curve * $multiplier);
        
        return max(1, min($users, $baseUsers * 2));
    }
    
    private function selectUserBehavior(): array
    {
        $random = mt_rand() / mt_getrandmax();
        $cumulative = 0;
        
        foreach ($this->userBehaviors as $behavior) {
            $cumulative += $behavior['probability'];
            if ($random <= $cumulative) {
                return $behavior;
            }
        }
        
        return $this->userBehaviors['browser']; // Default fallback
    }
    
    private function generateUserRequests(array $behavior, int $maxRequests): array
    {
        $requests = [];
        $requestCount = rand(1, $maxRequests);
        
        foreach ($behavior['actions'] as $action) {
            if (mt_rand() / mt_getrandmax() <= $action[2]) { // Check probability
                $requests[] = [
                    'method' => $action[0],
                    'uri' => $this->generateDynamicUri($action[1]),
                    'data' => $this->generateRequestData($action[0], $action[1])
                ];
            }
            
            if (count($requests) >= $requestCount) {
                break;
            }
        }
        
        return $requests;
    }
    
    private function generateDynamicUri(string $template): string
    {
        $uri = $template;
        
        // Replace dynamic segments
        if (strpos($uri, '{id}') !== false) {
            $uri = str_replace('{id}', rand(1, 1000), $uri);
        }
        
        // Add query parameters for certain endpoints
        if ($uri === '/products') {
            $params = [];
            if (rand(0, 1)) $params['page'] = rand(1, 10);
            if (rand(0, 1)) $params['limit'] = [12, 24, 48][array_rand([12, 24, 48])];
            if (rand(0, 1)) $params['category'] = ['electronics', 'clothing', 'books'][array_rand(['electronics', 'clothing', 'books'])];
            
            if ($params) {
                $uri .= '?' . http_build_query($params);
            }
        }
        
        if ($uri === '/search') {
            $queries = ['smartphone', 'laptop', 'headphones', 'camera', 'watch'];
            $uri .= '?q=' . urlencode($queries[array_rand($queries)]);
        }
        
        return $uri;
    }
    
    private function generateRequestData(string $method, string $uri): ?array
    {
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return null;
        }
        
        switch ($uri) {
            case '/auth/login':
                return [
                    'email' => 'user' . rand(1, 1000) . '@example.com',
                    'password' => 'password123'
                ];
            
            case '/auth/register':
                return [
                    'name' => 'User ' . rand(1, 1000),
                    'email' => 'newuser' . rand(1, 1000) . '@example.com',
                    'password' => 'password123'
                ];
            
            case '/cart/add':
                return [
                    'product_id' => rand(1, 1000),
                    'quantity' => rand(1, 5),
                    'price' => round(rand(999, 99999) / 100, 2)
                ];
            
            case '/checkout':
                return [
                    'payment_method' => 'credit_card',
                    'shipping_address' => [
                        'street' => rand(1, 999) . ' Main St',
                        'city' => 'Sample City',
                        'zip' => rand(10000, 99999)
                    ],
                    'total' => round(rand(2000, 50000) / 100, 2)
                ];
            
            default:
                return ['data' => 'sample'];
        }
    }
    
    private function executeRequest(array $request): array
    {
        $httpRequest = $this->createHttpRequest($request['method'], $request['uri'], $request['data']);
        $response = $this->app->handle($httpRequest);
        
        return [
            'status' => 200,
            'body' => $response->getBody()->getContents()
        ];
    }
    
    private function createHttpRequest(string $method, string $uri, ?array $data = null): Request
    {
        $request = new Request();
        $request->setMethod($method);
        $request->setUri($uri);
        
        if ($data) {
            $request->setParsedBody($data);
            $request->setHeader('Content-Type', 'application/json');
        }
        
        return $request;
    }
    
    // Mock data generators
    private function generatePageContent(): array
    {
        return [
            'title' => 'About Us',
            'content' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 50),
            'images' => array_fill(0, 5, 'https://example.com/image.jpg'),
            'load_time' => microtime(true)
        ];
    }
    
    private function generateProductListing(int $page, int $limit, ?string $category, string $sort): array
    {
        $products = [];
        
        for ($i = 0; $i < $limit; $i++) {
            $id = ($page - 1) * $limit + $i + 1;
            $products[] = [
                'id' => $id,
                'name' => 'Product ' . $id,
                'price' => round(rand(999, 99999) / 100, 2),
                'category' => $category ?: ['electronics', 'clothing', 'books'][array_rand(['electronics', 'clothing', 'books'])],
                'image' => 'https://example.com/product' . $id . '.jpg',
                'rating' => round(rand(100, 500) / 100, 1),
                'in_stock' => rand(0, 100) > 10
            ];
        }
        
        return [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 10000,
                'pages' => ceil(10000 / $limit)
            ],
            'filters' => [
                'category' => $category,
                'sort' => $sort
            ]
        ];
    }
    
    private function generateProductDetails(string $id): array
    {
        return [
            'id' => $id,
            'name' => 'Product ' . $id,
            'description' => str_repeat('Detailed product description. ', 20),
            'price' => round(rand(999, 99999) / 100, 2),
            'images' => array_fill(0, 8, 'https://example.com/product' . $id . '.jpg'),
            'specifications' => [
                'weight' => rand(100, 5000) . 'g',
                'dimensions' => rand(10, 50) . 'x' . rand(10, 50) . 'x' . rand(10, 50) . 'cm',
                'color' => ['Black', 'White', 'Silver'][array_rand(['Black', 'White', 'Silver'])],
                'material' => ['Plastic', 'Metal', 'Glass'][array_rand(['Plastic', 'Metal', 'Glass'])]
            ],
            'reviews' => array_fill(0, 20, [
                'rating' => rand(1, 5),
                'comment' => 'Great product! ' . str_repeat('Very satisfied. ', 10),
                'author' => 'User ' . rand(1, 1000),
                'date' => date('Y-m-d', time() - rand(0, 86400 * 365))
            ]),
            'related_products' => array_fill(0, 8, [
                'id' => rand(1, 1000),
                'name' => 'Related Product ' . rand(1, 100),
                'price' => round(rand(999, 99999) / 100, 2)
            ])
        ];
    }
    
    private function performComplexSearch(string $query, array $filters): array
    {
        // Simulate complex search with multiple criteria
        $results = [];
        $count = strlen($query) >= 3 ? rand(10, 100) : 0;
        
        for ($i = 0; $i < min($count, 50); $i++) {
            $results[] = [
                'id' => rand(1, 10000),
                'name' => 'Search Result ' . $i . ' for "' . $query . '"',
                'price' => round(rand(999, 99999) / 100, 2),
                'relevance' => round(rand(50, 100) / 100, 2),
                'category' => ['electronics', 'clothing', 'books'][array_rand(['electronics', 'clothing', 'books'])]
            ];
        }
        
        return [
            'query' => $query,
            'results' => $results,
            'total_found' => $count,
            'search_time' => round(rand(10, 500) / 1000, 3),
            'filters_applied' => $filters,
            'suggestions' => ['smartphone', 'laptop', 'tablet', 'headphones']
        ];
    }
    
    private function simulateAuthentication(array $credentials): array
    {
        // Simulate authentication delay
        usleep(rand(50000, 200000)); // 50-200ms delay
        
        return [
            'success' => true,
            'token' => 'jwt_token_' . bin2hex(random_bytes(16)),
            'user' => [
                'id' => rand(1, 10000),
                'name' => 'User ' . rand(1, 1000),
                'email' => $credentials['email'] ?? 'user@example.com'
            ]
        ];
    }
    
    private function simulateRegistration(array $userData): array
    {
        // Simulate registration processing
        usleep(rand(100000, 300000)); // 100-300ms delay
        
        return [
            'success' => true,
            'user_id' => rand(10000, 99999),
            'message' => 'Registration successful'
        ];
    }
    
    private function generateUserCart(): array
    {
        $itemCount = rand(0, 10);
        $items = [];
        
        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = [
                'id' => rand(1, 1000),
                'name' => 'Product ' . rand(1, 100),
                'price' => round(rand(999, 9999) / 100, 2),
                'quantity' => rand(1, 5),
                'image' => 'https://example.com/product.jpg'
            ];
        }
        
        return [
            'items' => $items,
            'total' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items)),
            'item_count' => array_sum(array_column($items, 'quantity'))
        ];
    }
    
    private function simulateAddToCart(array $item): array
    {
        return [
            'success' => true,
            'message' => 'Item added to cart',
            'cart_total' => round(rand(5000, 50000) / 100, 2)
        ];
    }
    
    private function simulateRemoveFromCart(string $id): array
    {
        return [
            'success' => true,
            'message' => 'Item removed from cart'
        ];
    }
    
    private function simulateCheckout(array $orderData): array
    {
        // Simulate payment processing delay
        usleep(rand(500000, 1500000)); // 500-1500ms delay
        
        return [
            'success' => true,
            'order_id' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'total' => $orderData['total'] ?? 0,
            'estimated_delivery' => date('Y-m-d', time() + 86400 * rand(3, 10))
        ];
    }
    
    private function generateUserProfile(): array
    {
        return [
            'id' => rand(1, 10000),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'phone' => '+1 555 ' . rand(100, 999) . ' ' . rand(1000, 9999),
            'address' => [
                'street' => rand(1, 999) . ' Main St',
                'city' => 'Sample City',
                'state' => 'CA',
                'zip' => rand(10000, 99999)
            ],
            'preferences' => [
                'newsletter' => rand(0, 1) === 1,
                'notifications' => rand(0, 1) === 1,
                'theme' => ['light', 'dark'][array_rand(['light', 'dark'])]
            ],
            'statistics' => [
                'orders_count' => rand(0, 50),
                'total_spent' => round(rand(0, 500000) / 100, 2),
                'member_since' => date('Y-m-d', time() - rand(86400 * 30, 86400 * 365 * 3))
            ]
        ];
    }
    
    private function generateUserOrders(): array
    {
        $orderCount = rand(0, 20);
        $orders = [];
        
        for ($i = 0; $i < $orderCount; $i++) {
            $orders[] = [
                'id' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                'date' => date('Y-m-d', time() - rand(0, 86400 * 365)),
                'total' => round(rand(1000, 50000) / 100, 2),
                'status' => ['pending', 'processing', 'shipped', 'delivered'][array_rand(['pending', 'processing', 'shipped', 'delivered'])],
                'items_count' => rand(1, 10)
            ];
        }
        
        return $orders;
    }
    
    private function generateOrderDetails(string $id): array
    {
        $itemCount = rand(1, 10);
        $items = [];
        
        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = [
                'id' => rand(1, 1000),
                'name' => 'Product ' . rand(1, 100),
                'price' => round(rand(999, 9999) / 100, 2),
                'quantity' => rand(1, 5)
            ];
        }
        
        return [
            'id' => $id,
            'date' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
            'items' => $items,
            'subtotal' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items)),
            'tax' => round(rand(500, 5000) / 100, 2),
            'shipping' => round(rand(0, 1500) / 100, 2),
            'total' => round(rand(2000, 50000) / 100, 2),
            'status' => ['pending', 'processing', 'shipped', 'delivered'][array_rand(['pending', 'processing', 'shipped', 'delivered'])],
            'tracking_number' => 'TRK-' . strtoupper(substr(md5(uniqid()), 0, 10))
        ];
    }
    
    private function generateHeavyAnalytics(): array
    {
        return [
            'sales_data' => array_fill(0, 365, [
                'date' => date('Y-m-d', time() - rand(0, 86400 * 365)),
                'revenue' => round(rand(1000, 100000) / 100, 2),
                'orders' => rand(10, 500),
                'customers' => rand(5, 200)
            ]),
            'product_performance' => array_fill(0, 100, [
                'id' => rand(1, 1000),
                'name' => 'Product ' . rand(1, 100),
                'sales' => rand(0, 1000),
                'revenue' => round(rand(1000, 50000) / 100, 2),
                'views' => rand(100, 10000)
            ]),
            'user_analytics' => [
                'total_users' => rand(10000, 100000),
                'active_users' => rand(1000, 10000),
                'new_users_today' => rand(10, 100),
                'user_retention' => round(rand(60, 90), 1),
                'demographics' => [
                    'age_groups' => [
                        '18-25' => rand(1000, 5000),
                        '26-35' => rand(2000, 8000),
                        '36-45' => rand(1500, 6000),
                        '46-55' => rand(1000, 4000),
                        '56+' => rand(500, 2000)
                    ],
                    'locations' => array_fill(0, 50, [
                        'country' => 'Country ' . rand(1, 50),
                        'users' => rand(100, 5000)
                    ])
                ]
            ]
        ];
    }
    
    private function simulateFileUpload(): array
    {
        // Simulate file processing delay
        usleep(rand(200000, 800000)); // 200-800ms delay
        
        return [
            'success' => true,
            'file_id' => 'FILE-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'filename' => 'upload_' . time() . '.jpg',
            'size' => rand(100000, 5000000), // 100KB to 5MB
            'url' => 'https://example.com/uploads/file.jpg'
        ];
    }
    
    public function runAllSimulations(): array
    {
        echo "🌐 Real-World Load Simulation Suite\n";
        echo "==================================\n\n";
        
        $results = [];
        
        foreach ($this->trafficPatterns as $patternName => $pattern) {
            echo "Starting simulation: {$pattern['name']}\n";
            $result = $this->simulateTrafficPattern($patternName, 30); // 30 second test
            $results[$patternName] = $result;
            
            echo "✅ Completed: {$result['avg_requests_per_second']} req/sec\n";
            echo "   Success rate: {$result['success_rate']}%\n\n";
        }
        
        return $results;
    }
}

// Execute simulations
if (php_sapi_name() === 'cli') {
    $simulation = new RealWorldLoadSimulation();
    $results = $simulation->runAllSimulations();
    
    // Calculate overall statistics
    $totalRequests = array_sum(array_column($results, 'total_requests'));
    $totalErrors = array_sum(array_column($results, 'errors'));
    $avgRequestsPerSec = array_sum(array_column($results, 'avg_requests_per_second')) / count($results);
    $overallSuccessRate = (($totalRequests - $totalErrors) / max($totalRequests, 1)) * 100;
    
    echo "🏆 Overall Simulation Results:\n";
    echo "=============================\n";
    echo "Total Requests: " . number_format($totalRequests) . "\n";
    echo "Total Errors: " . number_format($totalErrors) . "\n";
    echo "Average Requests/sec: " . number_format($avgRequestsPerSec, 2) . "\n";
    echo "Overall Success Rate: " . number_format($overallSuccessRate, 1) . "%\n";
    echo "Traffic Patterns Tested: " . count($results) . "\n";
    
    // Save results
    $timestamp = date('Y-m-d-H-i-s');
    $filename = "/tmp/real-world-load-simulation-{$timestamp}.json";
    file_put_contents($filename, json_encode([
        'timestamp' => $timestamp,
        'framework' => 'PivotPHP Core v1.1.1',
        'simulation_type' => 'real_world_load_patterns',
        'summary' => [
            'total_requests' => $totalRequests,
            'total_errors' => $totalErrors,
            'avg_requests_per_sec' => $avgRequestsPerSec,
            'overall_success_rate' => $overallSuccessRate
        ],
        'detailed_results' => $results
    ], JSON_PRETTY_PRINT));
    
    echo "\n💾 Results saved to: {$filename}\n";
}