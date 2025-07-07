#!/usr/bin/env php
<?php

/**
 * Slim 4 JSON API Benchmark
 * 
 * Tests the performance of JSON API responses in Slim 4
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;
use PivotBenchmark\BenchmarkBase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Slim4JsonApiBenchmark extends BenchmarkBase
{
    private $app;
    private ServerRequestFactory $requestFactory;
    private UriFactory $uriFactory;
    private array $sampleData;
    
    protected function getName(): string
    {
        return 'comparative/slim4/json-api';
    }
    
    protected function setup(): void
    {
        $this->app = AppFactory::create();
        $this->requestFactory = new ServerRequestFactory();
        $this->uriFactory = new UriFactory();
        
        // Disable error handling for benchmarking
        $this->app->addErrorMiddleware(false, false, false);
        
        // Prepare sample data
        $this->sampleData = [
            'users' => [],
            'posts' => []
        ];
        
        for ($i = 1; $i <= 100; $i++) {
            $this->sampleData['users'][] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        for ($i = 1; $i <= 200; $i++) {
            $this->sampleData['posts'][] = [
                'id' => $i,
                'title' => "Post Title {$i}",
                'content' => str_repeat("Content for post {$i}. ", 10),
                'user_id' => rand(1, 100),
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Setup API routes
        $this->app->get('/api/users', function (Request $request, Response $response) {
            $page = (int) ($request->getQueryParams()['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $users = array_slice($this->sampleData['users'], $offset, $limit);
            
            $response->getBody()->write(json_encode([
                'data' => $users,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($this->sampleData['users'])
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        $this->app->get('/api/posts', function (Request $request, Response $response) {
            $page = (int) ($request->getQueryParams()['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $posts = array_slice($this->sampleData['posts'], $offset, $limit);
            
            $response->getBody()->write(json_encode([
                'data' => $posts,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($this->sampleData['posts'])
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        });
        
        $this->app->post('/api/users', function (Request $request, Response $response) {
            $data = json_decode((string) $request->getBody(), true);
            
            $newUser = [
                'id' => count($this->sampleData['users']) + 1,
                'name' => $data['name'] ?? 'New User',
                'email' => $data['email'] ?? 'new@example.com',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $response->getBody()->write(json_encode(['data' => $newUser]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $rand = rand(1, 3);
        
        switch ($rand) {
            case 1:
                // GET users
                $uri = $this->uriFactory->createUri('/api/users?page=' . rand(1, 5));
                $request = $this->requestFactory->createServerRequest('GET', $uri);
                break;
            case 2:
                // GET posts
                $uri = $this->uriFactory->createUri('/api/posts?page=' . rand(1, 10));
                $request = $this->requestFactory->createServerRequest('GET', $uri);
                break;
            case 3:
                // POST user
                $uri = $this->uriFactory->createUri('/api/users');
                $request = $this->requestFactory->createServerRequest('POST', $uri);
                $request->getBody()->write(json_encode([
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ]));
                break;
        }
        
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'slim4';
        $results['endpoints'] = 3;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new Slim4JsonApiBenchmark($options);
$benchmark->run();