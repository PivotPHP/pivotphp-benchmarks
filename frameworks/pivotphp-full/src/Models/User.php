<?php

namespace App\Models;

use PivotPHP\CycleORM\ORM\Model;

/**
 * User Model for PivotPHP Full Stack
 * Demonstrates ORM capabilities
 */
class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
        'profile',
        'preferences'
    ];

    protected array $casts = [
        'profile' => 'json',
        'preferences' => 'json',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected array $hidden = [
        'password'
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->role,
            'profile' => $this->profile,
            'preferences' => $this->preferences,
            'email_verified_at' => $this->email_verified_at?->format('c'),
            'created_at' => $this->created_at?->format('c'),
            'updated_at' => $this->updated_at?->format('c')
        ];

        // Add computed attributes
        $data['full_name'] = $this->getFullNameAttribute();
        $data['is_active'] = $this->isActive();
        $data['is_admin'] = $this->isAdmin();

        return $data;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}