<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Select;

class UserRepository
{
    private Select $select;
    
    public function __construct(ORMInterface $orm)
    {
        $this->select = new Select($orm, User::class);
    }
    
    public function findById(int $id): ?User
    {
        return $this->select->wherePK($id)->fetchOne();
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->select->where('email', $email)->fetchOne();
    }
    
    public function findByIdWithPosts(int $id): ?User
    {
        return $this->select
            ->wherePK($id)
            ->load('posts')
            ->fetchOne();
    }
    
    public function paginate(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        $query = $this->select->orderBy('id', 'ASC');
        
        $total = clone $query;
        $total = $total->count();
        
        $items = $query
            ->limit($limit)
            ->offset($offset)
            ->fetchAll();
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function all(): array
    {
        return $this->select->fetchAll();
    }
}