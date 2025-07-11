<?php

namespace App\Controllers;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use App\Models\User;
use App\Repositories\UserRepository;

class UserController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request): Response
    {
        $page = (int) ($request->query('page') ?? 1);
        $perPage = (int) ($request->query('per_page') ?? 20);
        $search = $request->query('search');
        $status = $request->query('status');

        $filters = array_filter([
            'search' => $search,
            'status' => $status
        ]);

        $users = $this->userRepository->paginate($page, $perPage, $filters);

        $response = [
            'success' => true,
            'data' => $users['data'],
            'meta' => $users['meta'],
            'links' => $this->generatePaginationLinks($page, $users['meta'], $request)
        ];

        return new Response(json_encode($response), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ]), 404, ['Content-Type' => 'application/json']);
        }

        $response = [
            'success' => true,
            'data' => $user->toArray()
        ];

        return new Response(json_encode($response), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->body();

        // Validation
        $errors = $this->validateUserData($data);
        if (!empty($errors)) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $errors
            ]), 422, ['Content-Type' => 'application/json']);
        }

        try {
            $user = $this->userRepository->create($data);

            $response = [
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user->toArray()
            ];

            return new Response(json_encode($response), 201, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'Failed to create user',
                'details' => $e->getMessage()
            ]), 500, ['Content-Type' => 'application/json']);
        }
    }

    public function update(Request $request, int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ]), 404, ['Content-Type' => 'application/json']);
        }

        $data = $request->body();

        // Validation for update
        $errors = $this->validateUserData($data, false);
        if (!empty($errors)) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $errors
            ]), 422, ['Content-Type' => 'application/json']);
        }

        try {
            $updatedUser = $this->userRepository->update($id, $data);

            $response = [
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser->toArray()
            ];

            return new Response(json_encode($response), 200, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'Failed to update user',
                'details' => $e->getMessage()
            ]), 500, ['Content-Type' => 'application/json']);
        }
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'User not found',
                'code' => 404
            ]), 404, ['Content-Type' => 'application/json']);
        }

        try {
            $this->userRepository->delete($id);

            return new Response(json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]), 204, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return new Response(json_encode([
                'error' => true,
                'message' => 'Failed to delete user',
                'details' => $e->getMessage()
            ]), 500, ['Content-Type' => 'application/json']);
        }
    }

    private function validateUserData(array $data, bool $required = true): array
    {
        $errors = [];

        if ($required && empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (!empty($data['name']) && strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }

        if ($required && empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if ($required && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        return $errors;
    }

    private function generatePaginationLinks(int $page, array $meta, Request $request): array
    {
        $baseUrl = '/api/users';
        $queryParams = $request->query();
        
        return [
            'first' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1])),
            'last' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $meta['last_page']])),
            'prev' => $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null,
            'next' => $page < $meta['last_page'] ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null
        ];
    }
}