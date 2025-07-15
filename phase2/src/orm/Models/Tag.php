<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM\Models;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\ManyToMany;

/**
 * Tag entity para benchmarks da Fase 2
 */
#[Entity(repository: "PivotPHP\Benchmark\Phase2\ORM\Repositories\TagRepository")]
#[Table(name: "tags")]
class Tag
{
    #[Column(type: "primary")]
    public ?int $id = null;

    #[Column(type: "string", length: 100)]
    public string $name;

    #[Column(type: "string", length: 100, unique: true)]
    public string $slug;

    #[ManyToMany(target: Post::class, through: "post_tags", innerKey: "id", outerKey: "id", throughInnerKey: "tag_id", throughOuterKey: "post_id")]
    public array $posts = [];

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
    }

    public function updateSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getPostsCount(): int
    {
        return count($this->posts);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'posts_count' => $this->getPostsCount()
        ];
    }
}