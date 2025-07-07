#!/usr/bin/env php
<?php

/**
 * HTTP Request/Response Benchmark
 *
 * Tests the performance of PSR-7 message creation and manipulation
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Helix\Http\Psr7\Factory\ServerRequestFactory;
use Helix\Http\Psr7\Factory\ResponseFactory;
use Helix\Http\Psr7\Factory\StreamFactory;
use Helix\Http\Psr7\Factory\UriFactory;
use HelixBenchmark\BenchmarkBase;

class HttpRequestResponseBenchmark extends BenchmarkBase
{
    private ServerRequestFactory $requestFactory;
    private ResponseFactory $responseFactory;
    private StreamFactory $streamFactory;
    private UriFactory $uriFactory;
    private array $testData;

    protected function getName(): string
    {
        return 'http/request-response';
    }

    protected function setup(): void
    {
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
        $this->streamFactory = new StreamFactory();
        $this->uriFactory = new UriFactory();

        // Prepare test data
        $this->testData = [
            'users' => [],
            'meta' => [
                'total' => 1000,
                'page' => 1,
                'per_page' => 50
            ]
        ];

        // Generate test user data
        for ($i = 1; $i <= 50; $i++) {
            $this->testData['users'][] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => date('Y-m-d H:i:s'),
                'profile' => [
                    'bio' => "This is the bio for user {$i}",
                    'avatar' => "https://example.com/avatars/{$i}.jpg",
                    'location' => "City {$i}"
                ]
            ];
        }
    }

    protected function runSingleIteration(): void
    {
        // Create request with various attributes
        $uri = $this->uriFactory->createUri('https://api.example.com/v1/users?page=1&limit=50');

        $request = $this->requestFactory->createServerRequest('POST', $uri, [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'api.example.com',
            'HTTP_USER_AGENT' => 'PivotPHP Benchmark/1.0',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . base64_encode(random_bytes(32)),
            'CONTENT_TYPE' => 'application/json'
        ]);

        // Add headers
        $request = $request
            ->withHeader('X-Request-ID', uniqid('bench_', true))
            ->withHeader('X-Client-Version', '1.0.0')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9');

        // Add body
        $body = $this->streamFactory->createStream(json_encode($this->testData));
        $request = $request->withBody($body);

        // Parse request body (simulate middleware parsing)
        $parsedBody = json_decode((string) $request->getBody(), true);
        $request = $request->withParsedBody($parsedBody);

        // Create response
        $response = $this->responseFactory->createResponse(200);

        // Add response headers
        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Response-Time', (string) microtime(true))
            ->withHeader('X-Request-ID', $request->getHeaderLine('X-Request-ID'))
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');

        // Add response body
        $responseData = [
            'status' => 'success',
            'data' => $this->testData,
            'timestamp' => time()
        ];

        $responseBody = $this->streamFactory->createStream(json_encode($responseData));
        $response = $response->withBody($responseBody);

        // Simulate reading response
        $responseContent = (string) $response->getBody();
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new HttpRequestResponseBenchmark($options);
$benchmark->run();
