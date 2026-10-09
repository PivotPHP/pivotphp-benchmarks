<?php

declare(strict_types=1);

namespace app\controller;

use app\Support\Conn;
use app\Support\CpfValidator;
use support\Request;

class VoteController
{
    public function store(Request $request)
    {
        $body = json_decode($request->rawBody(), true) ?? [];
        $candidateId = $body['candidateId'] ?? null;
        $cpf = $body['cpf'] ?? null;

        if ($candidateId === null || $cpf === null) {
            return json(['error' => 'candidateId e cpf são obrigatórios'])->withStatus(422);
        }

        if (!CpfValidator::isValid($cpf)) {
            return json(['error' => 'CPF inválido'])->withStatus(422);
        }

        $pdo = Conn::pdo();

        $stmt = $pdo->prepare('SELECT id FROM candidates WHERE id = ?');
        $stmt->execute([(int) $candidateId]);
        if ($stmt->fetch() === false) {
            return json(['error' => 'Candidato não encontrado'])->withStatus(404);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO votes (candidate_id, cpf) VALUES (?, ?) ON CONFLICT (cpf) DO NOTHING');
            $stmt->execute([$candidateId, (string) $cpf]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();

                return json(['error' => 'Voto duplicado'])->withStatus(409);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Conn::cache()->del('votes:results');

        return json(['candidateId' => (int) $candidateId, 'voted' => true])->withStatus(201);
    }

    public function results(Request $request)
    {
        $cache = Conn::cache();
        $cached = $cache->get('votes:results');
        if ($cached !== null) {
            return json_http($cached);
        }

        $rows = Conn::pdo()->query(
            'SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id
             GROUP BY c.id, c.name
             ORDER BY c.id'
        )->fetchAll();

        $total = 0;
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['votes'] = (int) $row['votes'];
            $total += $row['votes'];
        }
        unset($row);

        $payload = ['total' => $total, 'candidates' => $rows];
        $cache->set('votes:results', (string) json_encode($payload, JSON_UNESCAPED_UNICODE));

        return json($payload);
    }

    public function resultForCandidate(Request $request, int $candidateId)
    {
        $stmt = Conn::pdo()->prepare(
            'SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id
             WHERE c.id = ?
             GROUP BY c.id, c.name'
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return json(['error' => 'Candidato não encontrado'])->withStatus(404);
        }

        $row['votes'] = (int) $row['votes'];

        return json($row);
    }

    public function destroy(Request $request)
    {
        Conn::pdo()->exec('TRUNCATE votes RESTART IDENTITY');
        Conn::cache()->del('votes:results');

        return json(['cleared' => true]);
    }
}
