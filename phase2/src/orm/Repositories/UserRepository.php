<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Repositories;

use Cycle\ORM\Select\Repository;
use PivotPHP\Benchmark\Phase2\ORM\Models\User;

/**
 * Repository customizado para User com métodos otimizados para benchmarks
 */
class UserRepository extends Repository
{
    /**
     * Busca usuários com paginação otimizada
     */
    public function findPaginated(int $page = 1, int $limit = 20): array
    {
        return $this->select()
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    /**
     * Busca usuários por email (com índice)
     */
    public function findByEmail(string $email): ?User
    {
        return $this->select()
            ->where('email', $email)
            ->fetchOne();
    }

    /**
     * Busca usuários com contagem de posts (query otimizada)
     */
    public function findWithPostsCount(int $limit = 100): array
    {
        return $this->select()
            ->load('posts')
            ->limit($limit)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    /**
     * Busca usuários ativos (com posts recentes)
     */
    public function findActiveUsers(\DateTimeInterface $since): array
    {
        return $this->select()
            ->load('posts', [
                'where' => ['created_at' => ['>' => $since]]
            ])
            ->having('COUNT(posts.id) > 0')
            ->groupBy('id')
            ->fetchAll();
    }

    /**
     * Query complexa com joins múltiplos para benchmark
     */
    public function findUsersWithStatsJoin(): array
    {
        return $this->select()
            ->load('posts.comments')
            ->load('comments')
            ->limit(50)
            ->fetchAll();
    }

    /**
     * Busca usuários por IDs (para batch operations)
     */
    public function findByIds(array $ids): array
    {
        return $this->select()
            ->where('id', 'IN', $ids)
            ->fetchAll();
    }

    /**
     * Query de agregação para benchmark
     */
    public function getUsersStats(): array
    {
        $query = $this->select()
            ->columns(['COUNT(*) as total_users'])
            ->fetchOne();

        return [
            'total_users' => $query['total_users'] ?? 0,
            'query_time' => microtime(true)
        ];
    }

    /**
     * Operação batch create para benchmark
     */
    public function createBatch(array $usersData): array
    {
        $users = [];
        
        foreach ($usersData as $userData) {
            $user = new User($userData['name'], $userData['email']);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Busca com lazy loading controlado
     */
    public function findWithLazyLoading(int $limit = 10): array
    {
        return $this->select()
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Busca com eager loading controlado  
     */
    public function findWithEagerLoading(int $limit = 10): array
    {
        return $this->select()
            ->load('posts')
            ->load('comments')
            ->limit($limit)
            ->fetchAll();
    }
}