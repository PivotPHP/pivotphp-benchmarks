#!/usr/bin/env php
<?php

/**
 * Flight JSON API Benchmark
 * 
 * Tests the performance of JSON API responses in Flight
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use HelixBenchmark\BenchmarkBase;

class FlightJsonApiBenchmark extends BenchmarkBase
{
    private array $sampleData;
    
    protected function getName(): string
    {
        return 'comparative/flight/json-api';
    }
    
    protected function setup(): void
    {
        // Disable Flight's default error handling
        Flight::set('flight.handle_errors', false);
        Flight::set('flight.log_errors', false);
        
        // Clear any existing routes
        Flight::router()->reset();
        
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
        Flight::route('GET /api/users', function () use (&$sampleData) {
            $page = (int) (Flight::request()->query['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $users = array_slice($sampleData['users'], $offset, $limit);
            
            Flight::json([
                'data' => $users,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($sampleData['users'])
                ]
            ]);
        });
        
        Flight::route('GET /api/posts', function () use (&$sampleData) {
            $page = (int) (Flight::request()->query['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $posts = array_slice($sampleData['posts'], $offset, $limit);
            
            Flight::json([
                'data' => $posts,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($sampleData['posts'])
                ]
            ]);
        });
        
        Flight::route('POST /api/users', function () use (&$sampleData) {
            $data = json_decode(Flight::request()->getBody(), true);
            
            $newUser = [
                'id' => count($sampleData['users']) + 1,
                'name' => $data['name'] ?? 'New User',
                'email' => $data['email'] ?? 'new@example.com',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            Flight::response()->status(201);
            Flight::json(['data' => $newUser]);
        });
    }
    
    protected function runSingleIteration(): void
    {
        $rand = rand(1, 3);
        
        // Reset Flight's state for each iteration
        Flight::response()->clear();
        
        switch ($rand) {
            case 1:
                // GET users
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = '/api/users?page=' . rand(1, 5);
                $_GET['page'] = rand(1, 5);
                break;
            case 2:
                // GET posts
                $_SERVER['REQUEST_METHOD'] = 'GET';
                $_SERVER['REQUEST_URI'] = '/api/posts?page=' . rand(1, 10);
                $_GET['page'] = rand(1, 10);
                break;
            case 3:
                // POST user
                $_SERVER['REQUEST_METHOD'] = 'POST';
                $_SERVER['REQUEST_URI'] = '/api/users';
                // Simulate request body
                $input = json_encode([
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ]);
                stream_wrapper_unregister("php");
                stream_wrapper_register("php", "MockPhpStream");
                file_put_contents('php://input', $input);
                break;
        }
        
        // Capture output since Flight writes directly
        ob_start();
        Flight::start();
        ob_end_clean();
        
        // Clean up
        $_GET = [];
    }
    
    protected function teardown(): void
    {
        // Clear routes after benchmark
        Flight::router()->reset();
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'flight';
        $results['endpoints'] = 3;
        return $results;
    }
}

// Mock PHP stream for simulating request body
class MockPhpStream
{
    protected $content = '';
    protected $position = 0;
    
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        return true;
    }
    
    public function stream_write($data)
    {
        $this->content = $data;
        return strlen($data);
    }
    
    public function stream_read($count)
    {
        $ret = substr($this->content, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof()
    {
        return $this->position >= strlen($this->content);
    }
    
    public function stream_stat()
    {
        return [];
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new FlightJsonApiBenchmark($options);
$benchmark->run();