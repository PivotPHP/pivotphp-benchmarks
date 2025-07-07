<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
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
    
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $offset = ($page - 1) * $limit;
        
        $paginatedUsers = array_slice($this->users, $offset, $limit);
        
        return $this->json([
            'data' => array_values($paginatedUsers),
            'meta' => [
                'total' => count($this->users),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($this->users) / $limit)
            ]
        ]);
    }
    
    public function show(int $id): JsonResponse
    {
        if (!isset($this->users[$id])) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        return $this->json(['data' => $this->users[$id]]);
    }
    
    public function store(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        
        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            return $this->json(['error' => 'Name and email are required'], 422);
        }
        
        $id = count($this->users) + 1;
        $user = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users[$id] = $user;
        
        return $this->json(['data' => $user], 201);
    }
    
    public function update(Request $request, int $id): JsonResponse
    {
        if (!isset($this->users[$id])) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        $data = json_decode($request->getContent(), true) ?? [];
        
        if (isset($data['name'])) {
            $this->users[$id]['name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $this->users[$id]['email'] = $data['email'];
        }
        
        return $this->json(['data' => $this->users[$id]]);
    }
    
    public function destroy(int $id): Response
    {
        if (!isset($this->users[$id])) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        unset($this->users[$id]);
        
        return new Response('', 204);
    }
    
    public function posts(int $id): JsonResponse
    {
        if (!isset($this->users[$id])) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        // Generate mock posts
        $posts = [];
        for ($i = 1; $i <= 10; $i++) {
            $posts[] = [
                'id' => ($id * 10) + $i,
                'user_id' => $id,
                'title' => "Post {$i} by User {$id}",
                'content' => "Content of post {$i}",
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} hours"))
            ];
        }
        
        return $this->json(['data' => $posts]);
    }
}