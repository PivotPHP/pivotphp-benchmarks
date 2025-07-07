#!/usr/bin/env php
<?php

/**
 * Large Payload Benchmark
 * 
 * Tests the performance of handling large HTTP payloads
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotBenchmark\BenchmarkBase;

class LargePayloadBenchmark extends BenchmarkBase
{
    private Application $app;
    private array $payloads = [];
    
    protected function getName(): string
    {
        return 'http/large-payload';
    }
    
    protected function setup(): void
    {
        $this->app = new Application();
        
        // Generate different sized payloads
        $this->generatePayloads();
        
        // Route for handling large JSON uploads
        $this->app->post('/api/upload/json', function($request, $response) {
            $body = (string) $request->getBody();
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $response->withStatus(400)->json(['error' => 'Invalid JSON']);
            }
            
            return $response->json([
                'received' => true,
                'size' => strlen($body),
                'items' => count($data['items'] ?? [])
            ]);
        });
        
        // Route for handling large form data
        $this->app->post('/api/upload/form', function($request, $response) {
            $parsedBody = $request->getParsedBody();
            
            return $response->json([
                'received' => true,
                'fields' => count($parsedBody ?? [])
            ]);
        });
        
        // Route for streaming large responses
        $this->app->get('/api/download/large', function($request, $response) {
            $size = $request->getQueryParams()['size'] ?? 'medium';
            $data = $this->payloads[$size] ?? $this->payloads['medium'];
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Length', (string) strlen($data))
                ->write($data);
        });
        
        // Route for handling chunked uploads
        $this->app->post('/api/upload/chunked', function($request, $response) {
            $totalSize = 0;
            $chunks = 0;
            
            // Simulate processing chunked data
            $body = $request->getBody();
            while (!$body->eof()) {
                $chunk = $body->read(8192); // 8KB chunks
                $totalSize += strlen($chunk);
                $chunks++;
            }
            
            return $response->json([
                'received' => true,
                'total_size' => $totalSize,
                'chunks' => $chunks
            ]);
        });
        
        // Route for binary data
        $this->app->post('/api/upload/binary', function($request, $response) {
            $contentType = $request->getHeaderLine('Content-Type');
            $body = (string) $request->getBody();
            
            // Simulate processing binary data
            $checksum = md5($body);
            
            return $response->json([
                'received' => true,
                'content_type' => $contentType,
                'size' => strlen($body),
                'checksum' => $checksum
            ]);
        });
    }
    
    private function generatePayloads(): void
    {
        // Small payload (1KB)
        $small = ['items' => []];
        for ($i = 0; $i < 10; $i++) {
            $small['items'][] = [
                'id' => $i,
                'name' => "Item {$i}",
                'data' => str_repeat('x', 50)
            ];
        }
        $this->payloads['small'] = json_encode($small);
        
        // Medium payload (100KB)
        $medium = ['items' => []];
        for ($i = 0; $i < 500; $i++) {
            $medium['items'][] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => str_repeat("Description {$i} ", 10),
                'metadata' => [
                    'created' => date('c'),
                    'tags' => ['tag1', 'tag2', 'tag3'],
                    'attributes' => array_fill(0, 5, str_repeat('attr', 10))
                ]
            ];
        }
        $this->payloads['medium'] = json_encode($medium);
        
        // Large payload (1MB)
        $large = ['items' => []];
        for ($i = 0; $i < 5000; $i++) {
            $large['items'][] = [
                'id' => $i,
                'name' => "Item {$i}",
                'description' => str_repeat("Description {$i} ", 20),
                'content' => base64_encode(random_bytes(100)),
                'metadata' => [
                    'created' => date('c'),
                    'modified' => date('c'),
                    'tags' => array_fill(0, 10, "tag{$i}"),
                    'attributes' => array_fill(0, 10, str_repeat('attribute', 5))
                ]
            ];
        }
        $this->payloads['large'] = json_encode($large);
        
        // Binary payload (500KB)
        $this->payloads['binary'] = random_bytes(500 * 1024);
        
        // Form data payload
        $formData = [];
        for ($i = 0; $i < 100; $i++) {
            $formData["field_{$i}"] = str_repeat("value{$i}", 50);
        }
        $this->payloads['form'] = http_build_query($formData);
    }
    
    protected function runSingleIteration(): void
    {
        $operations = [
            'upload_json_small',
            'upload_json_medium',
            'upload_json_large',
            'upload_form',
            'upload_binary',
            'download_large',
            'upload_chunked'
        ];
        
        $operation = $operations[array_rand($operations)];
        
        switch ($operation) {
            case 'upload_json_small':
                $this->uploadJson('small');
                break;
            case 'upload_json_medium':
                $this->uploadJson('medium');
                break;
            case 'upload_json_large':
                $this->uploadJson('large');
                break;
            case 'upload_form':
                $this->uploadForm();
                break;
            case 'upload_binary':
                $this->uploadBinary();
                break;
            case 'download_large':
                $this->downloadLarge();
                break;
            case 'upload_chunked':
                $this->uploadChunked();
                break;
        }
    }
    
    private function uploadJson(string $size): void
    {
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/upload/json';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // Simulate POST data
        $_POST = ['data' => $this->payloads[$size]];
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function uploadForm(): void
    {
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/upload/form';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        
        // Parse form data and set POST
        parse_str($this->payloads['form'], $parsedBody);
        $_POST = $parsedBody;
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function uploadBinary(): void
    {
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/upload/binary';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/octet-stream';
        
        // Simulate binary POST data
        $_POST = ['binary' => $this->payloads['binary']];
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
    
    private function downloadLarge(): void
    {
        $sizes = ['small', 'medium', 'large'];
        $size = $sizes[array_rand($sizes)];
        
        $path = '/api/download/large?size=' . $size;
        
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
        
        // Simulate client reading the response
        $body = (string) $response->getBody();
    }
    
    private function uploadChunked(): void
    {
        $path = '/api/upload/chunked';
        
        // Setup globals for Request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['CONTENT_TYPE'] = 'application/octet-stream';
        $_SERVER['HTTP_TRANSFER_ENCODING'] = 'chunked';
        
        // Simulate POST data
        $_POST = ['data' => $this->payloads['medium']];
        
        $request = Request::createFromGlobals();
        $response = $this->app->handle($request);
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new LargePayloadBenchmark($options);
$benchmark->run();