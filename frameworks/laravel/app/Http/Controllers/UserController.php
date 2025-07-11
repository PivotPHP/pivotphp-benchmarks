<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private static array $users = [];
    private static int $nextId = 1;

    public function index(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(1, min(100, (int) $request->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        $users = array_slice(self::$users, $offset, $limit);
        $total = count(self::$users);

        return response()->json([
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ],
            'framework' => 'laravel'
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = self::$users[$id] ?? null;

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'framework' => 'laravel'
            ], 404);
        }

        return response()->json([
            'data' => $user,
            'framework' => 'laravel'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }

        $userData = $validator->validated();
        $user = [
            'id' => self::$nextId++,
            'name' => $userData['name'],
            'email' => $userData['email'],
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString()
        ];

        self::$users[$user['id']] = $user;

        return response()->json([
            'data' => $user,
            'message' => 'User created successfully',
            'framework' => 'laravel'
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!isset(self::$users[$id])) {
            return response()->json([
                'error' => 'User not found',
                'framework' => 'laravel'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
                'framework' => 'laravel'
            ], 422);
        }

        $userData = $validator->validated();
        $user = self::$users[$id];

        if (isset($userData['name'])) {
            $user['name'] = $userData['name'];
        }
        if (isset($userData['email'])) {
            $user['email'] = $userData['email'];
        }
        $user['updated_at'] = now()->toISOString();

        self::$users[$id] = $user;

        return response()->json([
            'data' => $user,
            'message' => 'User updated successfully',
            'framework' => 'laravel'
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!isset(self::$users[$id])) {
            return response()->json([
                'error' => 'User not found',
                'framework' => 'laravel'
            ], 404);
        }

        unset(self::$users[$id]);

        return response()->json([
            'message' => 'User deleted successfully',
            'framework' => 'laravel'
        ]);
    }
}