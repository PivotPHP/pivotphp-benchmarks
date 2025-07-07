<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    private array $users = [];
    
    public function __construct()
    {
        // Generate mock data
        for ($i = 1; $i <= 1000; $i++) {
            $this->users[$i] = [
                'id' => $i,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days"))
            ];
        }
    }
    
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $paginatedUsers = array_slice($this->users, $offset, $limit);
        
        $response->getBody()->write(json_encode([
            'data' => array_values($paginatedUsers),
            'meta' => [
                'total' => count($this->users),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($this->users) / $limit)
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        if (!isset($this->users[$id])) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $response->getBody()->write(json_encode(['data' => $this->users[$id]]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            $response->getBody()->write(json_encode(['error' => 'Name and email are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
        
        $id = count($this->users) + 1;
        $user = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users[$id] = $user;
        
        $response->getBody()->write(json_encode(['data' => $user]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
    
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        if (!isset($this->users[$id])) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        if (isset($data['name'])) {
            $this->users[$id]['name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $this->users[$id]['email'] = $data['email'];
        }
        
        $response->getBody()->write(json_encode(['data' => $this->users[$id]]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        if (!isset($this->users[$id])) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        unset($this->users[$id]);
        
        return $response->withStatus(204);
    }
    
    public function posts(Request $request, Response $response, array $args): Response
    {
        $userId = (int) $args['id'];
        
        if (!isset($this->users[$userId])) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Generate mock posts
        $posts = [];
        for ($i = 1; $i <= 10; $i++) {
            $posts[] = [
                'id' => ($userId * 10) + $i,
                'user_id' => $userId,
                'title' => "Post {$i} by User {$userId}",
                'content' => "Content of post {$i}",
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours"))
            ];
        }
        
        $response->getBody()->write(json_encode(['data' => $posts]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}