<?php

declare(strict_types=1);

namespace Bench\Controllers;

use Bench\Support\Cache;
use PDO;
use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

class CandidateController
{
    private const CACHE_KEY = 'candidates';

    public function __construct(private PDO $pdo, private Cache $cache)
    {
    }

    public function index(ExpressRequest $req, ExpressResponse $res): ResponseInterface
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

    public function show(ExpressRequest $req, ExpressResponse $res): ResponseInterface
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
