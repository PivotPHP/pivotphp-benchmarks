<?php

declare(strict_types=1);

namespace app\controller;

use app\Support\Conn;
use support\Request;

class CandidateController
{
    public function index(Request $request)
    {
        $cache = Conn::cache();
        $cached = $cache->get('candidates');
        if ($cached !== null) {
            return json_http($cached);
        }

        $candidates = Conn::pdo()->query('SELECT id, name, position FROM candidates ORDER BY id')->fetchAll();
        $cache->set('candidates', (string) json_encode($candidates, JSON_UNESCAPED_UNICODE));

        return json($candidates);
    }

    public function show(Request $request, int $id)
    {
        $stmt = Conn::pdo()->prepare('SELECT id, name, position FROM candidates WHERE id = ?');
        $stmt->execute([$id]);
        $candidate = $stmt->fetch();

        if ($candidate === false) {
            return json(['error' => 'Candidato não encontrado'])->withStatus(404);
        }

        return json($candidate);
    }
}
