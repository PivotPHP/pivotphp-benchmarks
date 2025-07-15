<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Repositories;

use Cycle\ORM\Select\Repository;
use PivotPHP\Benchmark\Phase2\ORM\Models\Comment;

/**
 * Repository customizado para Comment
 */
class CommentRepository extends Repository
{
    /**
     * Busca comentários por post com user eager loading
     */
    public function findByPostWithUser(int $postId): array
    {
        return $this->select()
            ->where('post_id', $postId)
            ->load('user')
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    /**
     * Comentários recentes com joins otimizados
     */
    public function findRecentWithPostAndUser(int $limit = 100): array
    {
        return $this->select()
            ->load('post.user')
            ->load('user')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    /**
     * Operação batch create
     */
    public function createBatch(array $commentsData): array
    {
        $comments = [];
        
        foreach ($commentsData as $commentData) {
            $comment = new Comment(
                $commentData['post_id'],
                $commentData['user_id'],
                $commentData['content']
            );
            $comments[] = $comment;
        }

        return $comments;
    }
}