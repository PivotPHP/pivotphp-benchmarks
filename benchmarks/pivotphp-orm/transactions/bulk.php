#!/usr/bin/env php
<?php

/**
 * Bulk Transaction Benchmark
 * 
 * Tests the performance of bulk operations within transactions
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Factory;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Driver\SQLite\SQLiteDriver;
use HelixBenchmark\BenchmarkBase;

class Order
{
    public ?int $id = null;
    public string $orderNumber;
    public int $customerId;
    public float $total;
    public string $status;
    public \DateTimeInterface $createdAt;
}

class OrderItem
{
    public ?int $id = null;
    public int $orderId;
    public int $productId;
    public int $quantity;
    public float $price;
    public float $subtotal;
}

class Inventory
{
    public int $id;
    public int $productId;
    public int $quantity;
    public \DateTimeInterface $lastUpdated;
}

class BulkTransactionBenchmark extends BenchmarkBase
{
    private ORM $orm;
    private DatabaseManager $dbal;
    
    protected function getName(): string
    {
        return 'orm/transactions/bulk';
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
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number VARCHAR(50) NOT NULL UNIQUE,
                customer_id INTEGER NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL
            )
        ");
        
        $db->execute("
            CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )
        ");
        
        $db->execute("
            CREATE TABLE inventory (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL UNIQUE,
                quantity INTEGER NOT NULL,
                last_updated DATETIME NOT NULL
            )
        ");
        
        $db->execute("CREATE INDEX idx_orders_customer ON orders(customer_id)");
        $db->execute("CREATE INDEX idx_orders_status ON orders(status)");
        $db->execute("CREATE INDEX idx_order_items_order ON order_items(order_id)");
        $db->execute("CREATE INDEX idx_inventory_product ON inventory(product_id)");
        
        // Insert initial inventory
        $stmt = $db->prepare("INSERT INTO inventory (product_id, quantity, last_updated) VALUES (?, ?, ?)");
        for ($i = 1; $i <= 1000; $i++) {
            $stmt->execute([
                $i,
                rand(100, 10000),
                date('Y-m-d H:i:s')
            ]);
        }
        
        // Setup ORM
        $factory = new Factory($this->dbal);
        
        $schema = new Schema([
            Order::class => [
                SchemaInterface::ROLE => 'order',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'orders',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'orderNumber' => 'order_number',
                    'customerId' => 'customer_id',
                    'total' => 'total',
                    'status' => 'status',
                    'createdAt' => 'created_at'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'customerId' => 'int',
                    'total' => 'float',
                    'createdAt' => 'datetime'
                ]
            ],
            OrderItem::class => [
                SchemaInterface::ROLE => 'order_item',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'order_items',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'orderId' => 'order_id',
                    'productId' => 'product_id',
                    'quantity' => 'quantity',
                    'price' => 'price',
                    'subtotal' => 'subtotal'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'orderId' => 'int',
                    'productId' => 'int',
                    'quantity' => 'int',
                    'price' => 'float',
                    'subtotal' => 'float'
                ]
            ],
            Inventory::class => [
                SchemaInterface::ROLE => 'inventory',
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'inventory',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => [
                    'id' => 'id',
                    'productId' => 'product_id',
                    'quantity' => 'quantity',
                    'lastUpdated' => 'last_updated'
                ],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'productId' => 'int',
                    'quantity' => 'int',
                    'lastUpdated' => 'datetime'
                ]
            ]
        ]);
        
        $this->orm = new ORM($factory, $schema);
    }
    
    protected function runSingleIteration(): void
    {
        // Simulate creating an order with multiple items in a transaction
        $transaction = new Transaction($this->orm);
        
        // Create order
        $order = new Order();
        $order->orderNumber = 'ORD-' . uniqid();
        $order->customerId = rand(1, 1000);
        $order->status = 'pending';
        $order->createdAt = new \DateTime();
        
        $itemCount = rand(3, 10);
        $orderTotal = 0;
        $orderItems = [];
        
        // Create order items
        for ($i = 0; $i < $itemCount; $i++) {
            $item = new OrderItem();
            $item->productId = rand(1, 1000);
            $item->quantity = rand(1, 5);
            $item->price = round(rand(999, 99999) / 100, 2);
            $item->subtotal = $item->price * $item->quantity;
            
            $orderTotal += $item->subtotal;
            $orderItems[] = $item;
        }
        
        $order->total = $orderTotal;
        
        // Persist order
        $transaction->persist($order);
        $transaction->run();
        
        // Now persist items with the order ID
        $transaction = new Transaction($this->orm);
        foreach ($orderItems as $item) {
            $item->orderId = $order->id;
            $transaction->persist($item);
        }
        
        // Update inventory
        $inventoryRepo = $this->orm->getRepository(Inventory::class);
        foreach ($orderItems as $item) {
            $inventory = $inventoryRepo->select()
                ->where('product_id', $item->productId)
                ->fetchOne();
            
            if ($inventory && $inventory->quantity >= $item->quantity) {
                $inventory->quantity -= $item->quantity;
                $inventory->lastUpdated = new \DateTime();
                $transaction->persist($inventory);
            }
        }
        
        // Run the transaction
        $transaction->run();
        
        // Simulate order status update in another transaction
        if (rand(0, 2) === 0) {
            $transaction = new Transaction($this->orm);
            $order->status = 'processing';
            $transaction->persist($order);
            $transaction->run();
        }
        
        // Simulate bulk update of old orders
        if (rand(0, 10) === 0) {
            $orderRepo = $this->orm->getRepository(Order::class);
            $oldOrders = $orderRepo->select()
                ->where('status', 'pending')
                ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-1 hour')))
                ->limit(5)
                ->fetchAll();
            
            if ($oldOrders) {
                $transaction = new Transaction($this->orm);
                foreach ($oldOrders as $oldOrder) {
                    $oldOrder->status = 'cancelled';
                    $transaction->persist($oldOrder);
                }
                $transaction->run();
            }
        }
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new BulkTransactionBenchmark($options);
$benchmark->run();