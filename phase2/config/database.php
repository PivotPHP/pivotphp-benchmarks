<?php

declare(strict_types=1);

/**
 * Configuração do banco de dados para benchmarks da Fase 2
 * 
 * Configurações otimizadas para performance benchmarks com Cycle ORM
 */

return [
    'default' => 'mysql',
    
    'databases' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'mysql',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_DATABASE') ?: 'benchmark_phase2',
            'username' => getenv('DB_USERNAME') ?: 'benchmark',
            'password' => getenv('DB_PASSWORD') ?: 'benchmark',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ]
    ],
    
    'cycle_orm' => [
        'schema' => [
            'cache' => true,
            'migrations' => true,
            'annotations' => true
        ],
        'performance' => [
            'query_cache' => true,
            'result_cache' => true,
            'metadata_cache' => true,
            'lazy_loading' => true
        ]
    ],
    
    'benchmark_tables' => [
        'users' => [
            'count' => 10000,
            'columns' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            'indexes' => ['email', 'created_at']
        ],
        'posts' => [
            'count' => 50000,
            'columns' => ['id', 'user_id', 'title', 'content', 'status', 'created_at'],
            'indexes' => ['user_id', 'status', 'created_at'],
            'foreign_keys' => ['user_id' => 'users.id']
        ],
        'comments' => [
            'count' => 100000,
            'columns' => ['id', 'post_id', 'user_id', 'content', 'created_at'],
            'indexes' => ['post_id', 'user_id', 'created_at'],
            'foreign_keys' => [
                'post_id' => 'posts.id',
                'user_id' => 'users.id'
            ]
        ],
        'tags' => [
            'count' => 1000,
            'columns' => ['id', 'name', 'slug'],
            'indexes' => ['slug']
        ],
        'post_tags' => [
            'count' => 150000,
            'columns' => ['post_id', 'tag_id'],
            'indexes' => ['post_id', 'tag_id'],
            'foreign_keys' => [
                'post_id' => 'posts.id',
                'tag_id' => 'tags.id'
            ]
        ]
    ],
    
    'test_queries' => [
        'simple_select' => 'SELECT * FROM users WHERE id = ?',
        'join_query' => '
            SELECT u.name, p.title, COUNT(c.id) as comment_count 
            FROM users u 
            LEFT JOIN posts p ON u.id = p.user_id 
            LEFT JOIN comments c ON p.id = c.post_id 
            WHERE u.created_at > ? 
            GROUP BY u.id, p.id 
            ORDER BY comment_count DESC 
            LIMIT 100
        ',
        'complex_aggregation' => '
            SELECT 
                DATE(p.created_at) as date,
                COUNT(p.id) as posts_count,
                COUNT(DISTINCT p.user_id) as unique_users,
                AVG(comment_counts.cnt) as avg_comments
            FROM posts p
            LEFT JOIN (
                SELECT post_id, COUNT(*) as cnt 
                FROM comments 
                GROUP BY post_id
            ) comment_counts ON p.id = comment_counts.post_id
            WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(p.created_at)
            ORDER BY date DESC
        ',
        'subquery_test' => '
            SELECT * FROM users 
            WHERE id IN (
                SELECT DISTINCT user_id 
                FROM posts 
                WHERE created_at > (
                    SELECT AVG(created_at) 
                    FROM posts
                )
            )
            ORDER BY created_at DESC
            LIMIT 50
        '
    ]
];