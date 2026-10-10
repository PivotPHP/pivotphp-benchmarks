<?php

declare(strict_types=1);

namespace Bench\Controllers;

use Bench\Support\Cache;
use Bench\Support\CpfValidator;
use PDO;
use PivotPHP\Http\ExpressRequest;
use PivotPHP\Http\ExpressResponse;
use Psr\Http\Message\ResponseInterface;

class VoteController
{
    private const RESULTS_CACHE_KEY = 'votes:results';

    public function __construct(private PDO $pdo, private Cache $cache)
    {
    }

    /**
     * POST /api/votes — registra um voto.
     */
    public function store(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $candidateId = $req->input('candidateId');
        $cpf         = $req->input('cpf');

        if ($candidateId === null || $cpf === null) {
            return $res->status(422)->json(['error' => 'candidateId e cpf são obrigatórios']);
        }

        if (!CpfValidator::isValid($cpf)) {
            return $res->status(422)->json(['error' => 'CPF inválido']);
        }

        $candidate = $this->findCandidate((int) $candidateId);

        if ($candidate === null) {
            return $res->status(404)->json(['error' => 'Candidato não encontrado']);
        }

        // Inserção transacional com detecção de voto duplicado (UNIQUE em cpf).
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO votes (candidate_id, cpf) VALUES (?, ?) ON CONFLICT (cpf) DO NOTHING'
            );
            $stmt->execute([$candidateId, (string) $cpf]);

            if ($stmt->rowCount() === 0) {
                $this->pdo->rollBack();

                return $res->status(409)->json(['error' => 'Voto duplicado']);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Invalida o cache de resultados agregados.
        $this->cache->del(self::RESULTS_CACHE_KEY);

        return $res->status(201)->json([
            'candidateId' => (int) $candidateId,
            'voted'       => true,
        ]);
    }

    /**
     * GET /api/votes/results — resultados agregados.
     */
    public function results(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $cached = $this->cache->get(self::RESULTS_CACHE_KEY);

        if ($cached !== null) {
            $res->header('Content-Type', 'application/json');

            return $res->send($cached);
        }

        $rows = $this->aggregate();

        $payload = json_encode([
            'total'      => array_sum(array_column($rows, 'votes')),
            'candidates' => $rows,
        ], JSON_UNESCAPED_UNICODE);

        $this->cache->set(self::RESULTS_CACHE_KEY, $payload);

        return $res->json(json_decode($payload, true));
    }

    /**
     * GET /api/votes/results/{candidateId} — resultado de um candidato.
     */
    public function resultForCandidate(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id
             WHERE c.id = ?
             GROUP BY c.id, c.name'
        );
        $stmt->execute([$req->param('candidateId')]);

        $row = $stmt->fetch();

        if ($row === false) {
            return $res->status(404)->json(['error' => 'Candidato não encontrado']);
        }

        $row['votes'] = (int) $row['votes'];

        return $res->json($row);
    }

    /**
     * DELETE /api/votes — limpa os votos (apenas para ambiente de teste).
     */
    public function destroy(ExpressRequest $req, ExpressResponse $res): ResponseInterface
    {
        $this->pdo->exec('TRUNCATE votes RESTART IDENTITY');
        $this->cache->del(self::RESULTS_CACHE_KEY);

        return $res->json(['cleared' => true]);
    }

    private function findCandidate(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM candidates WHERE id = ?');
        $stmt->execute([$id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<int, array{id:int, name:string, votes:int}>
     */
    private function aggregate(): array
    {
        $rows = $this->pdo->query(
            'SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id
             GROUP BY c.id, c.name
             ORDER BY c.id'
        )->fetchAll();

        foreach ($rows as &$row) {
            $row['id']    = (int) $row['id'];
            $row['votes'] = (int) $row['votes'];
        }
        unset($row);

        return $rows;
    }
}
