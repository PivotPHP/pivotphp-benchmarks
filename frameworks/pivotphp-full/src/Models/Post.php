<?php

namespace App\Models;

use PivotPHP\CycleORM\ORM\Model;

/**
 * Post Model for PivotPHP Full Stack
 * Demonstrates ORM relationships
 */
class Post extends Model
{
    protected string $table = 'posts';

    protected array $fillable = [
        'title',
        'content',
        'excerpt',
        'status',
        'published_at',
        'user_id',
        'meta'
    ];

    protected array $casts = [
        'meta' => 'json',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at !== null && 
               $this->published_at <= now();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'published_at' => $this->published_at?->format('c'),
            'user_id' => $this->user_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->format('c'),
            'updated_at' => $this->updated_at?->format('c'),
            'is_published' => $this->isPublished()
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}