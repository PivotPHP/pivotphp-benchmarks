<?php

namespace App\Controllers;

use Flight;

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
    
    public function index()
    {
        $request = Flight::request();
        $page = (int) ($request->query->page ?? 1);
        $limit = (int) ($request->query->limit ?? 20);
        $offset = ($page - 1) * $limit;
        
        $paginatedUsers = array_slice($this->users, $offset, $limit);
        
        Flight::json([
            'data' => array_values($paginatedUsers),
            'meta' => [
                'total' => count($this->users),
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(count($this->users) / $limit)
            ]
        ]);
    }
    
    public function show($id)
    {
        $id = (int) $id;
        
        if (!isset($this->users[$id])) {
            Flight::json(['error' => 'User not found'], 404);
            return;
        }
        
        Flight::json(['data' => $this->users[$id]]);
    }
    
    public function store()
    {
        $request = Flight::request();
        $data = json_decode($request->getBody(), true) ?? [];
        
        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            Flight::json(['error' => 'Name and email are required'], 422);
            return;
        }
        
        $id = count($this->users) + 1;
        $user = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users[$id] = $user;
        
        Flight::json(['data' => $user], 201);
    }
    
    public function update($id)
    {
        $id = (int) $id;
        $request = Flight::request();
        $data = json_decode($request->getBody(), true) ?? [];
        
        if (!isset($this->users[$id])) {
            Flight::json(['error' => 'User not found'], 404);
            return;
        }
        
        if (isset($data['name'])) {
            $this->users[$id]['name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $this->users[$id]['email'] = $data['email'];
        }
        
        Flight::json(['data' => $this->users[$id]]);
    }
    
    public function destroy($id)
    {
        $id = (int) $id;
        
        if (!isset($this->users[$id])) {
            Flight::json(['error' => 'User not found'], 404);
            return;
        }
        
        unset($this->users[$id]);
        
        Flight::response()->status(204)->send();
    }
    
    public function posts($id)
    {
        $userId = (int) $id;
        
        if (!isset($this->users[$userId])) {
            Flight::json(['error' => 'User not found'], 404);
            return;
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
        
        Flight::json(['data' => $posts]);
    }
}