#!/usr/bin/env php
<?php

/**
 * Lumen JSON API Benchmark
 * 
 * Tests the performance of JSON API responses in Lumen
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Laravel\Lumen\Application;
use Laravel\Lumen\Http\Request;
use PivotBenchmark\BenchmarkBase;

class LumenJsonApiBenchmark extends BenchmarkBase
{
    private Application $app;
    private array $sampleData;
    
    protected function getName(): string
    {
        return 'comparative/lumen/json-api';
    }
    
    protected function setup(): void
    {
        $this->app = new Application(dirname(__DIR__, 3));
        
        // Disable debug mode
        $this->app->configure('app');
        putenv('APP_DEBUG=false');
        
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
        
        // Make sample data available to routes
        $sampleData = &$this->sampleData;
        
        // Setup API routes
        $this->app->router->get('/api/users', function (Request $request) use (&$sampleData) {
            $page = (int) $request->input('page', 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $users = array_slice($sampleData['users'], $offset, $limit);
            
            return response()->json([
                'data' => $users,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($sampleData['users'])
                ]
            ]);
        });
        
        $this->app->router->get('/api/posts', function (Request $request) use (&$sampleData) {
            $page = (int) $request->input('page', 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $posts = array_slice($sampleData['posts'], $offset, $limit);
            
            return response()->json([
                'data' => $posts,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($sampleData['posts'])
                ]
            ]);
        });
        
        $this->app->router->post('/api/users', function (Request $request) use (&$sampleData) {
            $newUser = [
                'id' => count($sampleData['users']) + 1,
                'name' => $request->input('name', 'New User'),
                'email' => $request->input('email', 'new@example.com'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return response()->json(['data' => $newUser], 201);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $rand = rand(1, 3);
        
        switch ($rand) {
            case 1:
                // GET users
                $request = Request::create('/api/users?page=' . rand(1, 5), 'GET');
                break;
            case 2:
                // GET posts
                $request = Request::create('/api/posts?page=' . rand(1, 10), 'GET');
                break;
            case 3:
                // POST user
                $request = Request::create('/api/users', 'POST', [
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ]);
                break;
        }
        
        $response = $this->app->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'lumen';
        $results['endpoints'] = 3;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new LumenJsonApiBenchmark($options);
$benchmark->run();