<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Repositories;

use Cycle\ORM\Select\Repository;
use PivotPHP\Benchmark\Phase2\ORM\Models\Post;
use DateTimeInterface;

/**
 * Repository customizado para Post com queries otimizadas para benchmarks
 */
class PostRepository extends Repository
{
    /**
     * Query complexa com múltiplos joins
     */
    public function findComplexJoin(int $limit = 50): array
    {
        return $this->select()
            ->load('user')
            ->load('comments.user')
            ->load('tags')
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Query com agregação (contagem de comentários)
     */
    public function findWithCommentsCount(): array
    {
        return $this->select()
            ->load('user')
            ->load('comments')
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit(100)
            ->fetchAll();
    }

    /**
     * Busca por período com índice otimizado
     */
    public function findByDateRange(DateTimeInterface $start, DateTimeInterface $end): array
    {
        return $this->select()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    /**
     * Query com subquery para benchmark
     */
    public function findPopularPosts(): array
    {
        return $this->select()
            ->load('user')
            ->load('comments')
            ->having('COUNT(comments.id) > 5')
            ->groupBy('id')
            ->orderBy('COUNT(comments.id)', 'DESC')
            ->limit(20)
            ->fetchAll();
    }

    /**
     * Busca posts por usuário com lazy loading
     */
    public function findByUserLazy(int $userId, int $limit = 20): array
    {
        return $this->select()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Busca posts por usuário com eager loading
     */
    public function findByUserEager(int $userId, int $limit = 20): array
    {
        return $this->select()
            ->where('user_id', $userId)
            ->load('user')
            ->load('comments')
            ->load('tags')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Query de paginação otimizada
     */
    public function findPaginatedOptimized(int $page = 1, int $limit = 20): array
    {
        return $this->select()
            ->load('user', ['select' => ['id', 'name']])
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->fetchAll();
    }

    /**
     * Busca com filtros múltiplos
     */
    public function findWithFilters(array $filters): array
    {
        $query = $this->select();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['tag'])) {
            $query->load('tags')->where('tags.slug', $filters['tag']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('content', 'LIKE', '%' . $filters['search'] . '%');
        }

        return $query
            ->load('user')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->fetchAll();
    }

    /**
     * Operação batch para benchmark
     */
    public function createBatch(array $postsData): array
    {
        $posts = [];
        
        foreach ($postsData as $postData) {
            $post = new Post(
                $postData['user_id'],
                $postData['title'],
                $postData['content'],
                $postData['status'] ?? 'published'
            );
            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Estatísticas agregadas para benchmark
     */
    public function getPostsStats(): array
    {
        $query = $this->select()
            ->columns([
                'COUNT(*) as total_posts',
                'COUNT(DISTINCT user_id) as unique_authors'
            ])
            ->fetchOne();

        return [
            'total_posts' => $query['total_posts'] ?? 0,
            'unique_authors' => $query['unique_authors'] ?? 0,
            'query_time' => microtime(true)
        ];
    }
}