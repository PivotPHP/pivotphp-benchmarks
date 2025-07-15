<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Relation\HasMany;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use DateTimeInterface;

/**
 * Post entity para benchmarks da Fase 2
 */
#[Entity(repository: "PivotPHP\Benchmark\Phase2\ORM\Repositories\PostRepository")]
#[Table(name: "posts")]
class Post
{
    #[Column(type: "primary")]
    public ?int $id = null;

    #[Column(type: "integer")]
    public int $user_id;

    #[Column(type: "string", length: 255)]
    public string $title;

    #[Column(type: "text")]
    public string $content;

    #[Column(type: "string", length: 50, default: "published")]
    public string $status = "published";

    #[Column(type: "datetime")]
    public DateTimeInterface $created_at;

    #[Column(type: "datetime")]
    public DateTimeInterface $updated_at;

    #[BelongsTo(target: User::class, innerKey: "user_id", outerKey: "id")]
    public ?User $user = null;

    #[HasMany(target: Comment::class, innerKey: "id", outerKey: "post_id")]
    public array $comments = [];

    #[ManyToMany(target: Tag::class, through: "post_tags", innerKey: "id", outerKey: "id", throughInnerKey: "post_id", throughOuterKey: "tag_id")]
    public array $tags = [];

    public function __construct(int $user_id, string $title, string $content, string $status = "published")
    {
        $this->user_id = $user_id;
        $this->title = $title;
        $this->content = $content;
        $this->status = $status;
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function updateTitle(string $title): void
    {
        $this->title = $title;
        $this->updated_at = new \DateTime();
    }

    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->updated_at = new \DateTime();
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->updated_at = new \DateTime();
    }

    public function getCommentsCount(): int
    {
        return count($this->comments);
    }

    public function getTagsCount(): int
    {
        return count($this->tags);
    }

    public function addTag(Tag $tag): void
    {
        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags = array_filter($this->tags, fn($t) => $t !== $tag);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => substr($this->content, 0, 100) . '...',
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'comments_count' => $this->getCommentsCount(),
            'tags_count' => $this->getTagsCount(),
            'user' => $this->user?->toArray()
        ];
    }
}