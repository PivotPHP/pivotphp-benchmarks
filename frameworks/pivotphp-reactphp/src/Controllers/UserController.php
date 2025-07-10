<?php

namespace App\Controllers;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class UserController
{
    private array $users = [];

    public function __construct()
    {
        // Initialize with sample users for benchmarking
        $this->users = [
            1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            3 => ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com'],
        ];
    }

    public function index(Request $req, Response $res): void
    {
        $page = (int) ($req->query('page') ?? 1);
        $limit = (int) ($req->query('limit') ?? 10);
        
        $offset = ($page - 1) * $limit;
        $users = array_slice(array_values($this->users), $offset, $limit);
        
        $res->json([
            'data' => $users,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($this->users)
            ]
        ]);
    }

    public function show(Request $req, Response $res): void
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->users[$id])) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        $res->json(['data' => $this->users[$id]]);
    }

    public function store(Request $req, Response $res): void
    {
        $data = $req->json();
        
        $newId = max(array_keys($this->users)) + 1;
        $user = [
            'id' => $newId,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? 'unknown@example.com'
        ];
        
        $this->users[$newId] = $user;
        
        $res->status(201)->json(['data' => $user]);
    }

    public function update(Request $req, Response $res): void
    {
        $id = (int) $req->param('id');
        $data = $req->json();
        
        if (!isset($this->users[$id])) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        $this->users[$id] = array_merge($this->users[$id], $data);
        
        $res->json(['data' => $this->users[$id]]);
    }

    public function destroy(Request $req, Response $res): void
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->users[$id])) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        unset($this->users[$id]);
        
        $res->status(204)->send();
    }

    public function posts(Request $req, Response $res): void
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->users[$id])) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        // Mock posts data
        $posts = [
            ['id' => 1, 'title' => 'First Post', 'content' => 'Content of first post'],
            ['id' => 2, 'title' => 'Second Post', 'content' => 'Content of second post'],
        ];
        
        $res->json([
            'data' => $posts,
            'user' => $this->users[$id]
        ]);
    }
}