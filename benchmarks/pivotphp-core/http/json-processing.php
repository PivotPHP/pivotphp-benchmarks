#!/usr/bin/env php
<?php

/**
 * JSON Processing Benchmark
 * 
 * Tests the performance of JSON encoding/decoding with large payloads
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Http\Psr7\Factory\ResponseFactory;
use PivotPHP\Core\Http\Psr7\Factory\StreamFactory;
use PivotBenchmark\BenchmarkBase;

class JsonProcessingBenchmark extends BenchmarkBase
{
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;
    private array $smallPayload;
    private array $mediumPayload;
    private array $largePayload;
    
    protected function getName(): string
    {
        return 'http/json-processing';
    }
    
    protected function setup(): void
    {
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
        
        // Small payload (typical API response)
        $this->smallPayload = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => true,
            'roles' => ['user', 'admin'],
            'metadata' => [
                'last_login' => date('Y-m-d H:i:s'),
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0'
            ]
        ];
        
        // Medium payload (list of items)
        $this->mediumPayload = [
            'items' => [],
            'pagination' => [
                'total' => 100,
                'page' => 1,
                'per_page' => 100
            ]
        ];
        
        for ($i = 1; $i <= 100; $i++) {
            $this->mediumPayload['items'][] = [
                'id' => $i,
                'title' => "Item {$i}",
                'description' => str_repeat("Description text for item {$i}. ", 5),
                'price' => round(rand(1000, 99999) / 100, 2),
                'stock' => rand(0, 1000),
                'categories' => ['category1', 'category2', 'category3'],
                'attributes' => [
                    'color' => ['red', 'blue', 'green'][rand(0, 2)],
                    'size' => ['S', 'M', 'L', 'XL'][rand(0, 3)],
                    'weight' => rand(100, 5000) . 'g'
                ],
                'created_at' => date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Large payload (nested complex structure)
        $this->largePayload = [
            'organization' => [
                'id' => 1,
                'name' => 'Test Organization',
                'departments' => []
            ]
        ];
        
        for ($d = 1; $d <= 10; $d++) {
            $department = [
                'id' => $d,
                'name' => "Department {$d}",
                'employees' => []
            ];
            
            for ($e = 1; $e <= 50; $e++) {
                $department['employees'][] = [
                    'id' => ($d - 1) * 50 + $e,
                    'name' => "Employee {$e} of Dept {$d}",
                    'email' => "emp{$e}.dept{$d}@example.com",
                    'position' => ['Manager', 'Developer', 'Designer', 'Analyst'][rand(0, 3)],
                    'salary' => rand(40000, 150000),
                    'projects' => array_map(function($p) {
                        return [
                            'id' => $p,
                            'name' => "Project {$p}",
                            'status' => ['active', 'completed', 'pending'][rand(0, 2)]
                        ];
                    }, range(1, rand(2, 5)))
                ];
            }
            
            $this->largePayload['organization']['departments'][] = $department;
        }
    }
    
    protected function runSingleIteration(): void
    {
        // Test small payload
        $this->processPayload($this->smallPayload);
        
        // Test medium payload
        $this->processPayload($this->mediumPayload);
        
        // Test large payload
        $this->processPayload($this->largePayload);
    }
    
    private function processPayload(array $data): void
    {
        // Create response with JSON
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('Content-Type', 'application/json');
        
        // Encode to JSON
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Create stream and attach to response
        $stream = $this->streamFactory->createStream($json);
        $response = $response->withBody($stream);
        
        // Simulate reading and parsing (what a client would do)
        $responseBody = (string) $response->getBody();
        $parsed = json_decode($responseBody, true);
        
        // Verify parsing worked
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('JSON parsing error: ' . json_last_error_msg());
        }
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new JsonProcessingBenchmark($options);
$benchmark->run();