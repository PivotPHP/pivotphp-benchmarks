<?php

declare(strict_types=1);

namespace Bench\Controllers;

use Bench\Support\Cache;
use PDO;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class CandidateController
{
    private const CACHE_KEY = 'candidates';

    public function __construct(private PDO $pdo, private Cache $cache)
    {
    }

    public function index(Request $req, Response $res): Response
    {
        $cached = $this->cache->get(self::CACHE_KEY);

        if ($cached !== null) {
            $res->header('Content-Type', 'application/json');

            return $res->send($cached);
        }

        $candidates = $this->pdo->query('SELECT id, name, position FROM candidates ORDER BY id')->fetchAll();

        $payload = json_encode($candidates, JSON_UNESCAPED_UNICODE);

        $this->cache->set(self::CACHE_KEY, $payload);

        return $res->json($candidates);
    }

    public function show(Request $req, Response $res): Response
    {
        $stmt = $this->pdo->prepare('SELECT id, name, position FROM candidates WHERE id = ?');
        $stmt->execute([$req->param('id')]);

        $candidate = $stmt->fetch();

        if ($candidate === false) {
            return $res->status(404)->json(['error' => 'Candidato não encontrado']);
        }

        return $res->json($candidate);
    }
}
