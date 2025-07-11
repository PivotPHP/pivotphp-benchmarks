<?php

namespace App\Repositories;

use App\Models\User;
use PivotPHP\CycleORM\ORM\Repository;

/**
 * User Repository for PivotPHP Full Stack
 * Demonstrates ORM repository pattern
 */
class UserRepository extends Repository
{
    protected string $model = User::class;

    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->where('email', $email)->first();
    }

    public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $query = $this->query();

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->byRole($filters['role']);
        }

        // Get total count
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $users = $query->offset($offset)
                      ->limit($perPage)
                      ->orderBy('created_at', 'desc')
                      ->get();

        return [
            'data' => $users->map(fn($user) => $user->toArray())->toArray(),
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }

    public function create(array $data): User
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Set defaults
        $data['status'] = $data['status'] ?? 'active';
        $data['role'] = $data['role'] ?? 'user';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        return $this->insert($data);
    }

    public function update(int $id, array $data): ?User
    {
        $user = $this->findById($id);
        
        if (!$user) {
            return null;
        }

        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $data['updated_at'] = now();

        $this->where('id', $id)->update($data);

        // Return updated user
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return $this->where('id', $id)->delete() > 0;
    }

    public function getActiveUsers(): array
    {
        return $this->active()
                   ->orderBy('name')
                   ->get()
                   ->map(fn($user) => $user->toArray())
                   ->toArray();
    }

    public function getUsersWithPosts(): array
    {
        return $this->with('posts')
                   ->get()
                   ->map(function($user) {
                       $userData = $user->toArray();
                       $userData['posts'] = $user->posts->map(fn($post) => $post->toArray())->toArray();
                       return $userData;
                   })
                   ->toArray();
    }

    public function searchUsers(string $search, int $limit = 50): array
    {
        return $this->search($search)
                   ->limit($limit)
                   ->get()
                   ->map(fn($user) => $user->toArray())
                   ->toArray();
    }

    public function getUserStats(): array
    {
        return [
            'total_users' => $this->count(),
            'active_users' => $this->active()->count(),
            'admin_users' => $this->byRole('admin')->count(),
            'recent_users' => $this->where('created_at', '>=', now()->subDays(30))->count()
        ];
    }
}