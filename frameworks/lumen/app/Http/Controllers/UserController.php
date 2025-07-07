<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class UserController extends BaseController
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
    
    public function index(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);
        $offset = ($page - 1) * $limit;
        
        $paginatedUsers = array_slice($this->users, $offset, $limit);
        
        return response()->json([
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
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json(['data' => $this->users[$id]]);
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email'
        ]);
        
        $id = count($this->users) + 1;
        $user = [
            'id' => $id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->users[$id] = $user;
        
        return response()->json(['data' => $user], 201);
    }
    
    public function update(Request $request, $id)
    {
        $id = (int) $id;
        
        if (!isset($this->users[$id])) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        if ($request->has('name')) {
            $this->users[$id]['name'] = $request->input('name');
        }
        
        if ($request->has('email')) {
            $this->users[$id]['email'] = $request->input('email');
        }
        
        return response()->json(['data' => $this->users[$id]]);
    }
    
    public function destroy($id)
    {
        $id = (int) $id;
        
        if (!isset($this->users[$id])) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        unset($this->users[$id]);
        
        return response()->json(['message' => 'User deleted successfully'], 204);
    }
    
    public function posts($id)
    {
        $userId = (int) $id;
        
        if (!isset($this->users[$userId])) {
            return response()->json(['error' => 'User not found'], 404);
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
        
        return response()->json(['data' => $posts]);
    }
}