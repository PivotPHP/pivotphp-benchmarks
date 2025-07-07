#!/usr/bin/env php
<?php

/**
 * Symfony JSON API Benchmark
 * 
 * Tests the performance of JSON API responses in Symfony
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use HelixBenchmark\BenchmarkBase;

class SymfonyJsonApiBenchmark extends BenchmarkBase
{
    private HttpKernel $kernel;
    private RouteCollection $routes;
    private array $sampleData;
    private UrlMatcher $matcher;
    
    protected function getName(): string
    {
        return 'comparative/symfony/json-api';
    }
    
    protected function setup(): void
    {
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
        
        // Setup routes
        $this->routes = new RouteCollection();
        
        $this->routes->add('api_users', new Route('/api/users', [
            '_controller' => [$this, 'getUsersAction']
        ], [], [], '', [], ['GET']));
        
        $this->routes->add('api_posts', new Route('/api/posts', [
            '_controller' => [$this, 'getPostsAction']
        ], [], [], '', [], ['GET']));
        
        $this->routes->add('api_create_user', new Route('/api/users', [
            '_controller' => [$this, 'createUserAction']
        ], [], [], '', [], ['POST']));
        
        // Create kernel
        $dispatcher = new EventDispatcher();
        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();
        
        $this->kernel = new HttpKernel(
            $dispatcher,
            $controllerResolver,
            null,
            $argumentResolver
        );
        
        // Create matcher
        $context = new RequestContext();
        $this->matcher = new UrlMatcher($this->routes, $context);
    }
    
    public function getUsersAction(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $users = array_slice($this->sampleData['users'], $offset, $limit);
        
        return new JsonResponse([
            'data' => $users,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($this->sampleData['users'])
            ]
        ]);
    }
    
    public function getPostsAction(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $posts = array_slice($this->sampleData['posts'], $offset, $limit);
        
        return new JsonResponse([
            'data' => $posts,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($this->sampleData['posts'])
            ]
        ]);
    }
    
    public function createUserAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $newUser = [
            'id' => count($this->sampleData['users']) + 1,
            'name' => $data['name'] ?? 'New User',
            'email' => $data['email'] ?? 'new@example.com',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return new JsonResponse(['data' => $newUser], 201);
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
                $request = Request::create('/api/users', 'POST', [], [], [], [], json_encode([
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ]));
                break;
        }
        
        // Match route
        $this->matcher->getContext()->fromRequest($request);
        $parameters = $this->matcher->match($request->getPathInfo());
        $request->attributes->add($parameters);
        
        // Handle request
        $response = $this->kernel->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'symfony';
        $results['endpoints'] = 3;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SymfonyJsonApiBenchmark($options);
$benchmark->run();