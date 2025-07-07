#!/usr/bin/env php
<?php

/**
 * Entity Hydration Benchmark
 * 
 * Tests the performance of entity hydration with different collection sizes
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

class Product
{
    public int $id;
    public string $sku;
    public string $name;
    public string $description;
    public float $price;
    public int $stock;
    public bool $active;
    public array $attributes;
    public \DateTimeInterface $createdAt;
    public \DateTimeInterface $updatedAt;
}

class Category
{
    public int $id;
    public string $name;
    public string $slug;
    public ?string $description;
    public ?int $parentId;
    public int $sortOrder;
}

class EntityHydrationBenchmark extends BenchmarkBase
{
    private ORM $orm;
    private DatabaseManager $dbal;
    private array $sizes = [10, 100, 1000];
    
    protected function getName(): string
    {
        return 'orm/hydration/entity';
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
            CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sku VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                stock INTEGER NOT NULL DEFAULT 0,
                active BOOLEAN NOT NULL DEFAULT 1,
                attributes TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ");
        
        $db->execute("
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT,
                parent_id INTEGER,
                sort_order INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (parent_id) REFERENCES categories(id)
            )
        ");
        
        $db->execute("CREATE INDEX idx_products_sku ON products(sku)");
        $db->execute("CREATE INDEX idx_products_active ON products(active)");
        $db->execute("CREATE INDEX idx_categories_slug ON categories(slug)");
        
        // Insert test data - Products
        $stmt = $db->prepare("
            INSERT INTO products (sku, name, description, price, stock, active, attributes, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        for ($i = 1; $i <= 5000; $i++) {
            $attributes = json_encode([
                'color' => ['red', 'blue', 'green', 'black', 'white'][rand(0, 4)],
                'size' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'][rand(0, 5)],
                'material' => ['cotton', 'polyester', 'wool', 'silk'][rand(0, 3)],
                'weight' => rand(100, 2000) . 'g',
                'dimensions' => [
                    'length' => rand(10, 100),
                    'width' => rand(10, 100),
                    'height' => rand(5, 50)
                ]
            ]);
            
            $stmt->execute([
                "SKU-{$i}",
                "Product {$i}",
                str_repeat("Product description {$i}. ", 10),
                round(rand(999, 99999) / 100, 2),
                rand(0, 1000),
                $i % 10 !== 0 ? 1 : 0, // 10% inactive
                $attributes,
                date('Y-m-d H:i:s', time() - rand(0, 86400 * 365)),
                date('Y-m-d H:i:s', time() - rand(0, 86400 * 30))
            ]);
        }
        
        // Insert test data - Categories
        $stmt = $db->prepare("
            INSERT INTO categories (name, slug, description, parent_id, sort_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        for ($i = 1; $i <= 100; $i++) {
            $stmt->execute([
                "Category {$i}",
                "category-{$i}",
                $i % 3 === 0 ? "Description for category {$i}" : null,
                $i > 20 ? rand(1, 20) : null, // Some have parents
                $i
            ]);
        }
        
        // Setup ORM
        $factory = new Factory($this->dbal);
        
        $schema = new Schema([
            Product::class => [
                SchemaInterface::ROLE => 'product',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'products',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'sku' => 'sku',
                    'name' => 'name',
                    'description' => 'description',
                    'price' => 'price',
                    'stock' => 'stock',
                    'active' => 'active',
                    'attributes' => 'attributes',
                    'createdAt' => 'created_at',
                    'updatedAt' => 'updated_at'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'price' => 'float',
                    'stock' => 'int',
                    'active' => 'bool',
                    'attributes' => 'json',
                    'createdAt' => 'datetime',
                    'updatedAt' => 'datetime'
                ]
            ],
            Category::class => [
                SchemaInterface::ROLE => 'category',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'categories',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'name' => 'name',
                    'slug' => 'slug',
                    'description' => 'description',
                    'parentId' => 'parent_id',
                    'sortOrder' => 'sort_order'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'parentId' => 'int',
                    'sortOrder' => 'int'
                ]
            ]
        ]);
        
        $this->orm = new ORM($factory, $schema);
    }
    
    protected function runSingleIteration(): void
    {
        $productRepo = $this->orm->getRepository(Product::class);
        $categoryRepo = $this->orm->getRepository(Category::class);
        
        // Hydrate different collection sizes
        foreach ($this->sizes as $size) {
            // Hydrate products
            $products = $productRepo->select()
                ->limit($size)
                ->offset(rand(0, 5000 - $size))
                ->fetchAll();
            
            // Access hydrated data to ensure full hydration
            foreach ($products as $product) {
                $dummy = $product->sku . $product->name . $product->price;
                $attrs = $product->attributes;
            }
            
            // Hydrate categories
            if ($size <= 100) {
                $categories = $categoryRepo->select()
                    ->limit($size)
                    ->fetchAll();
                
                foreach ($categories as $category) {
                    $dummy = $category->name . $category->slug;
                }
            }
        }
        
        // Test single entity hydration
        $product = $productRepo->findByPK(rand(1, 5000));
        $category = $categoryRepo->findByPK(rand(1, 100));
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new EntityHydrationBenchmark($options);
$benchmark->run();