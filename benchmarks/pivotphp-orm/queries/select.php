#!/usr/bin/env php
<?php

/**
 * Select Query Benchmark
 * 
 * Tests the performance of SELECT queries with Cycle ORM
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Factory;
use Cycle\ORM\SchemaInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use PivotBenchmark\BenchmarkBase;

class User
{
    public int $id;
    public string $name;
    public string $email;
    public ?string $bio;
    public \DateTimeInterface $createdAt;
}

class Post
{
    public int $id;
    public int $userId;
    public string $title;
    public string $content;
    public int $views;
    public \DateTimeInterface $publishedAt;
}

class SelectQueryBenchmark extends BenchmarkBase
{
    private ORM $orm;
    private DatabaseManager $dbal;
    
    protected function getName(): string
    {
        return 'orm/queries/select';
    }
    
    protected function setup(): void
    {
        // Setup in-memory SQLite database
        $this->dbal = new DatabaseManager(
            new DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'sqlite']
                ],
                'connections' => [
                    'sqlite' => [
                        'driver' => SQLiteDriver::class,
                        'connection' => 'sqlite::memory:',
                        'username' => '',
                        'password' => ''
                    ]
                ]
            ])
        );
        
        $db = $this->dbal->database('default');
        
        // Create tables
        $db->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                bio TEXT,
                created_at DATETIME NOT NULL
            )
        ");
        
        $db->execute("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                views INTEGER DEFAULT 0,
                published_at DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
        
        $db->execute("CREATE INDEX idx_posts_user_id ON posts(user_id)");
        $db->execute("CREATE INDEX idx_posts_published_at ON posts(published_at)");
        
        // Insert test data
        $stmt = $db->prepare("INSERT INTO users (name, email, bio, created_at) VALUES (?, ?, ?, ?)");
        for ($i = 1; $i <= 100; $i++) {
            $stmt->execute([
                "User {$i}",
                "user{$i}@example.com",
                $i % 2 === 0 ? "Bio for user {$i}" : null,
                date('Y-m-d H:i:s', time() - rand(0, 86400 * 365))
            ]);
        }
        
        $stmt = $db->prepare("INSERT INTO posts (user_id, title, content, views, published_at) VALUES (?, ?, ?, ?, ?)");
        for ($i = 1; $i <= 1000; $i++) {
            $stmt->execute([
                rand(1, 100),
                "Post Title {$i}",
                str_repeat("Content for post {$i}. ", 20),
                rand(0, 10000),
                date('Y-m-d H:i:s', time() - rand(0, 86400 * 30))
            ]);
        }
        
        // Setup ORM
        $factory = new Factory($this->dbal);
        
        $schema = new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'users',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'name' => 'name',
                    'email' => 'email',
                    'bio' => 'bio',
                    'createdAt' => 'created_at'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'createdAt' => 'datetime'
                ]
            ],
            Post::class => [
                SchemaInterface::ROLE => 'post',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'posts',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'userId' => 'user_id',
                    'title' => 'title',
                    'content' => 'content',
                    'views' => 'views',
                    'publishedAt' => 'published_at'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'userId' => 'int',
                    'views' => 'int',
                    'publishedAt' => 'datetime'
                ]
            ]
        ]);
        
        $this->orm = new ORM($factory, $schema);
    }
    
    protected function runSingleIteration(): void
    {
        // Simple select by ID
        $userRepo = $this->orm->getRepository(User::class);
        $user = $userRepo->findByPK(rand(1, 100));
        
        // Select with conditions
        $postRepo = $this->orm->getRepository(Post::class);
        $posts = $postRepo->select()
            ->where('views', '>', 5000)
            ->limit(10)
            ->fetchAll();
        
        // Select with ordering and pagination
        $recentPosts = $postRepo->select()
            ->orderBy('published_at', 'DESC')
            ->limit(20)
            ->offset(rand(0, 50))
            ->fetchAll();
        
        // Select with multiple conditions
        $popularPosts = $postRepo->select()
            ->where('views', '>', 1000)
            ->where('published_at', '>', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->orderBy('views', 'DESC')
            ->limit(5)
            ->fetchAll();
        
        // Count query
        $totalUsers = $userRepo->select()->count();
        $totalPosts = $postRepo->select()->count();
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SelectQueryBenchmark($options);
$benchmark->run();