<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use DateTimeInterface;

/**
 * Comment entity para benchmarks da Fase 2
 */
#[Entity(repository: "PivotPHP\Benchmark\Phase2\ORM\Repositories\CommentRepository")]
#[Table(name: "comments")]
class Comment
{
    #[Column(type: "primary")]
    public ?int $id = null;

    #[Column(type: "integer")]
    public int $post_id;

    #[Column(type: "integer")]
    public int $user_id;

    #[Column(type: "text")]
    public string $content;

    #[Column(type: "datetime")]
    public DateTimeInterface $created_at;

    #[BelongsTo(target: Post::class, innerKey: "post_id", outerKey: "id")]
    public ?Post $post = null;

    #[BelongsTo(target: User::class, innerKey: "user_id", outerKey: "id")]
    public ?User $user = null;

    public function __construct(int $post_id, int $user_id, string $content)
    {
        $this->post_id = $post_id;
        $this->user_id = $user_id;
        $this->content = $content;
        $this->created_at = new \DateTime();
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user' => $this->user?->toArray(),
            'post' => $this->post ? [
                'id' => $this->post->id,
                'title' => $this->post->title
            ] : null
        ];
    }
}