<?php

declare(strict_types=1);

namespace Bench\Controllers;

use Bench\Support\Cache;
use PDO;
use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

class ProductController
{
    private const DASHBOARD_CACHE_KEY = 'dashboard:summary';

    public function __construct(private PDO $pdo, private Cache $cache)
    {
    }

    /**
     * GET /api/products — lista paginada e filtrada por category_id.
     */
    public function index(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $page = max(1, (int) ($req->query('page') ?? 1));
        $limit = min(100, max(1, (int) ($req->query('limit') ?? 20)));
        $offset = ($page - 1) * $limit;
        $categoryId = $req->query('category_id');

        if ($categoryId !== null && $categoryId !== '') {
            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $countStmt->execute([$categoryId]);
            $total = (int) $countStmt->fetchColumn();

            $stmt = $this->pdo->prepare(
                'SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, c.name AS category_name
                 FROM products p
                 JOIN categories c ON c.id = p.category_id
                 WHERE p.category_id = ?
                 ORDER BY p.id ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bindValue(1, (int) $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $total = (int) $this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

            $stmt = $this->pdo->prepare(
                'SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, c.name AS category_name
                 FROM products p
                 JOIN categories c ON c.id = p.category_id
                 ORDER BY p.id ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
        }

        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['id'] = (int) $item['id'];
            $item['category_id'] = (int) $item['category_id'];
            $item['price'] = (float) $item['price'];
            $item['stock'] = (int) $item['stock'];
        }
        unset($item);

        return $res->json([
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
     * GET /api/products/{id} — detalhe de um produto.
     */
    public function show(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $id = $req->param('id');
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.name, p.description, p.price, p.stock, p.category_id, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product === false) {
            return $res->status(404)->json(['error' => 'Produto não encontrado']);
        }

        $product['id'] = (int) $product['id'];
        $product['category_id'] = (int) $product['category_id'];
        $product['price'] = (float) $product['price'];
        $product['stock'] = (int) $product['stock'];

        return $res->json($product);
    }

    /**
     * POST /api/products — cadastra um novo produto.
     */
    public function store(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $name = $req->input('name');
        $price = $req->input('price');
        $categoryId = $req->input('category_id');
        $description = $req->input('description') ?? '';
        $stock = (int) ($req->input('stock') ?? 0);

        if ($name === null || $name === '' || $price === null || $categoryId === null) {
            return $res->status(422)->json(['error' => 'name, price e category_id são obrigatórios']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, description, price, stock, category_id)
             VALUES (?, ?, ?, ?, ?)
             RETURNING id, name, description, price, stock, category_id, created_at'
        );
        $stmt->execute([$name, $description, $price, $stock, $categoryId]);
        $created = $stmt->fetch();

        if ($created) {
            $created['id'] = (int) $created['id'];
            $created['category_id'] = (int) $created['category_id'];
            $created['price'] = (float) $created['price'];
            $created['stock'] = (int) $created['stock'];
        }

        // Invalida cache do dashboard
        $this->cache->del(self::DASHBOARD_CACHE_KEY);

        return $res->status(201)->json($created);
    }

    /**
     * PATCH /api/products/{id} — atualização parcial.
     */
    public function update(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $id = (int) $req->param('id');
        $checkStmt = $this->pdo->prepare('SELECT id FROM products WHERE id = ?');
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            return $res->status(404)->json(['error' => 'Produto não encontrado']);
        }

        $fields = [];
        $values = [];

        if ($req->input('name') !== null) {
            $fields[] = 'name = ?';
            $values[] = $req->input('name');
        }
        if ($req->input('price') !== null) {
            $fields[] = 'price = ?';
            $values[] = $req->input('price');
        }
        if ($req->input('stock') !== null) {
            $fields[] = 'stock = ?';
            $values[] = (int) $req->input('stock');
        }
        if ($req->input('description') !== null) {
            $fields[] = 'description = ?';
            $values[] = $req->input('description');
        }

        if (empty($fields)) {
            return $res->status(422)->json(['error' => 'Nenhum campo fornecido para atualização']);
        }

        $fields[] = 'updated_at = now()';
        $values[] = $id;

        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        $this->cache->del(self::DASHBOARD_CACHE_KEY);

        return $this->show($req, $res);
    }

    /**
     * DELETE /api/products/{id} — remove um produto.
     */
    public function destroy(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $id = $req->param('id');
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $res->status(404)->json(['error' => 'Produto não encontrado']);
        }

        $this->cache->del(self::DASHBOARD_CACHE_KEY);

        return $res->status(204)->send('');
    }

    /**
     * GET /api/dashboard — agregação consolidada com cache Redis.
     */
    public function dashboard(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $cached = $this->cache->get(self::DASHBOARD_CACHE_KEY);
        if ($cached !== null) {
            $res->header('Content-Type', 'application/json');
            return $res->send($cached);
        }

        $metrics = $this->pdo->query(
            'SELECT COUNT(*) AS total_products,
                    COALESCE(AVG(price), 0) AS avg_price,
                    COALESCE(SUM(stock), 0) AS total_stock
             FROM products'
        )->fetch();

        $byCategory = $this->pdo->query(
            'SELECT c.id, c.name, COUNT(p.id) AS products_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id, c.name
             ORDER BY products_count DESC'
        )->fetchAll();

        $payload = json_encode([
            'total_products' => (int) ($metrics['total_products'] ?? 0),
            'avg_price' => round((float) ($metrics['avg_price'] ?? 0), 2),
            'total_stock' => (int) ($metrics['total_stock'] ?? 0),
            'categories' => $byCategory,
        ], JSON_UNESCAPED_UNICODE);

        $this->cache->set(self::DASHBOARD_CACHE_KEY, (string) $payload);

        return $res->json(json_decode((string) $payload, true));
    }
}
