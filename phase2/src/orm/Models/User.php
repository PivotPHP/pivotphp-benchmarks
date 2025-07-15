<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\HasMany;
use DateTimeInterface;

/**
 * User entity para benchmarks da Fase 2
 */
#[Entity(repository: "PivotPHP\Benchmark\Phase2\ORM\Repositories\UserRepository")]
#[Table(name: "users")]
class User
{
    #[Column(type: "primary")]
    public ?int $id = null;

    #[Column(type: "string", length: 255)]
    public string $name;

    #[Column(type: "string", length: 255, unique: true)]
    public string $email;

    #[Column(type: "datetime")]
    public DateTimeInterface $created_at;

    #[Column(type: "datetime")]
    public DateTimeInterface $updated_at;

    #[HasMany(target: Post::class, innerKey: "id", outerKey: "user_id")]
    public array $posts = [];

    #[HasMany(target: Comment::class, innerKey: "id", outerKey: "user_id")]
    public array $comments = [];

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updated_at = new \DateTime();
    }

    public function updateEmail(string $email): void
    {
        $this->email = $email;
        $this->updated_at = new \DateTime();
    }

    public function getPostsCount(): int
    {
        return count($this->posts);
    }

    public function getCommentsCount(): int
    {
        return count($this->comments);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'posts_count' => $this->getPostsCount(),
            'comments_count' => $this->getCommentsCount()
        ];
    }
}