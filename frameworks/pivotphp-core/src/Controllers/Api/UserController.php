<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Request;

class UserController
{
    // Mock data for testing without database
    private static array $users = [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'avatar' => null],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'avatar' => null],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'avatar' => null],
    ];

    public function index(Request $request, Response $response): void
    {
        $response->json([
            'data' => self::$users,
            'total' => count(self::$users),
        ]);
    }

    public function show(Request $request, Response $response): void
    {
        $id = (int) $request->getAttribute('route')->getArguments()['id'] ?? 0;
        
        $user = array_filter(self::$users, fn($u) => $u['id'] === $id);
        $user = reset($user);
        
        if (!$user) {
            $response->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        $response->json(['data' => $user]);
    }

    public function store(Request $request, Response $response): void
    {
        $data = $request->getBody();
        
        // Validation
        if (empty($data['name']) || empty($data['email'])) {
            $response->status(422)->json([
                'error' => 'Validation failed',
                'messages' => [
                    'name' => empty($data['name']) ? 'Name is required' : null,
                    'email' => empty($data['email']) ? 'Email is required' : null,
                ],
            ]);
            return;
        }
        
        // Check if email already exists
        $existing = array_filter(self::$users, fn($u) => $u['email'] === $data['email']);
        
        if (!empty($existing)) {
            $response->status(409)->json([
                'error' => 'Email already exists',
            ]);
            return;
        }
        
        // Create user
        $newId = max(array_column(self::$users, 'id')) + 1;
        $user = [
            'id' => $newId,
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => $data['avatar'] ?? null,
        ];
        
        self::$users[] = $user;
        
        $response->status(201)->json([
            'message' => 'User created successfully',
            'data' => $user,
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $id = (int) $request->getAttribute('route')->getArguments()['id'] ?? 0;
        $data = $request->getBody();
        
        $userIndex = array_search($id, array_column(self::$users, 'id'));
        
        if ($userIndex === false) {
            $response->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        $user = &self::$users[$userIndex];
        
        // Update fields
        if (!empty($data['name'])) {
            $user['name'] = $data['name'];
        }
        
        if (!empty($data['email'])) {
            // Check if new email already exists
            $existing = array_filter(self::$users, fn($u) => $u['email'] === $data['email'] && $u['id'] !== $id);
            if (!empty($existing)) {
                $response->status(409)->json([
                    'error' => 'Email already exists',
                ]);
                return;
            }
            $user['email'] = $data['email'];
        }
        
        if (isset($data['avatar'])) {
            $user['avatar'] = $data['avatar'];
        }
        
        $response->json([
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request, Response $response): void
    {
        $id = (int) $request->getAttribute('route')->getArguments()['id'] ?? 0;
        
        $userIndex = array_search($id, array_column(self::$users, 'id'));
        
        if ($userIndex === false) {
            $response->status(404)->json(['error' => 'User not found']);
            return;
        }
        
        array_splice(self::$users, $userIndex, 1);
        
        $response->json([
            'message' => 'User deleted successfully',
        ]);
    }
}