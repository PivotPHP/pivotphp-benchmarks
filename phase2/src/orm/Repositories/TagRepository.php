<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Repositories;

use Cycle\ORM\Select\Repository;
use PivotPHP\Benchmark\Phase2\ORM\Models\Tag;

/**
 * Repository customizado para Tag
 */
class TagRepository extends Repository
{
    /**
     * Busca tags com contagem de posts
     */
    public function findWithPostsCount(): array
    {
        return $this->select()
            ->load('posts')
            ->fetchAll();
    }

    /**
     * Tags mais populares (com mais posts)
     */
    public function findPopular(int $limit = 20): array
    {
        return $this->select()
            ->load('posts')
            ->having('COUNT(posts.id) > 0')
            ->groupBy('id')
            ->orderBy('COUNT(posts.id)', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Busca por slug (otimizada com índice)
     */
    public function findBySlug(string $slug): ?Tag
    {
        return $this->select()
            ->where('slug', $slug)
            ->fetchOne();
    }
}