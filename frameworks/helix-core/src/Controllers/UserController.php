<?php

declare(strict_types=1);

namespace App\Controllers;

use Helix\Http\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $paginatedUsers = array_slice($this->users, $offset, $limit);
        
        return Response::json([
            'data' => array_values($paginatedUsers),
            'meta' => [
                'total' => count($this->users),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($this->users) / $limit)
            ]
        ]);
    }
    
    public function show(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        if (!isset($this->users[$id])) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        return Response::json(['data' => $this->users[$id]]);
    }
    
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            return Response::json(['error' => 'Name and email are required'], 422);
        }
        
        $id = count($this->users) + 1;
        $user = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users[$id] = $user;
        
        return Response::json(['data' => $user], 201);
    }
    
    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        if (!isset($this->users[$id])) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        if (isset($data['name'])) {
            $this->users[$id]['name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $this->users[$id]['email'] = $data['email'];
        }
        
        return Response::json(['data' => $this->users[$id]]);
    }
    
    public function destroy(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        
        if (!isset($this->users[$id])) {
            return Response::json(['error' => 'User not found'], 404);
        }
        
        unset($this->users[$id]);
        
        return Response::json(['message' => 'User deleted successfully'], 204);
    }
    
    public function posts(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $userId = (int) $args['id'];
        
        if (!isset($this->users[$userId])) {
            return Response::json(['error' => 'User not found'], 404);
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
        
        return Response::json(['data' => $posts]);
    }
}