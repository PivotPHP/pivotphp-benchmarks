<?php

declare(strict_types=1);

use Bench\Support\Cache;
use Bench\Support\CpfValidator;
use Predis\Client;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// ---- Banco & Cache (lazy initialization para não onerar rotas sem banco) ----
$getPdo = static function (): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST') ?: '127.0.0.1',
                getenv('DB_PORT') ?: '5432',
                getenv('DB_NAME') ?: 'voting_db'
            ),
            getenv('DB_USER') ?: 'bench',
            getenv('DB_PASSWORD') ?: 'bench_secret',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
};

$getCache = static function (): Cache {
    static $cache = null;
    if ($cache === null) {
        $cache = new Cache(new Client([
            'scheme' => 'tcp',
            'host'   => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port'   => 6379,
        ]));
    }
    return $cache;
};

function jsonResponse(Response $res, mixed $data, int $status = 200): Response
{
    $res->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

    return $res
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// SPEC-115 — Cenários Modulares (estilo Fastify)
$app->get('/api/bare', function (Request $req, Response $res): Response {
    return jsonResponse($res, ['hello' => 'world']);
});

$app->get('/api/user/{id}', function (Request $req, Response $res, array $args): Response {
    return jsonResponse($res, ['id' => (string) ($args['id'] ?? '')]);
});

$mwPass = function (Request $request, $handler): Response {
    return $handler->handle($request);
};
$app->get('/api/middleware', function (Request $req, Response $res): Response {
    return jsonResponse($res, ['hello' => 'world']);
})->add($mwPass)->add($mwPass)->add($mwPass)->add($mwPass)->add($mwPass);

$app->post('/api/echo', function (Request $req, Response $res): Response {
    $data = (array) $req->getParsedBody();
    return jsonResponse($res, $data);
});

$app->get('/api/health', function (Request $req, Response $res): Response {
    return jsonResponse($res, ['status' => 'ok']);
});

$app->get('/api/candidates', function (Request $req, Response $res) use ($getPdo, $getCache): Response {
    $cache = $getCache();
    $cached = $cache->get('candidates');

    if ($cached !== null) {
        $res->getBody()->write($cached);

        return $res->withHeader('Content-Type', 'application/json');
    }

    $pdo = $getPdo();
    $candidates = $pdo->query('SELECT id, name, position FROM candidates ORDER BY id')->fetchAll();
    $cache->set('candidates', (string) json_encode($candidates, JSON_UNESCAPED_UNICODE));

    return jsonResponse($res, $candidates);
});

$app->get('/api/candidates/{id}', function (Request $req, Response $res, array $args) use ($getPdo): Response {
    $pdo = $getPdo();
    $stmt = $pdo->prepare('SELECT id, name, position FROM candidates WHERE id = ?');
    $stmt->execute([$args['id']]);
    $candidate = $stmt->fetch();

    if ($candidate === false) {
        return jsonResponse($res, ['error' => 'Candidato não encontrado'], 404);
    }

    return jsonResponse($res, $candidate);
});

$app->post('/api/votes', function (Request $req, Response $res) use ($getPdo, $getCache): Response {
    $body = (array) $req->getParsedBody();
    $candidateId = $body['candidateId'] ?? null;
    $cpf = $body['cpf'] ?? null;

    if ($candidateId === null || $cpf === null) {
        return jsonResponse($res, ['error' => 'candidateId e cpf são obrigatórios'], 422);
    }

    if (!CpfValidator::isValid($cpf)) {
        return jsonResponse($res, ['error' => 'CPF inválido'], 422);
    }

    $pdo = $getPdo();
    $stmt = $pdo->prepare('SELECT id FROM candidates WHERE id = ?');
    $stmt->execute([(int) $candidateId]);
    if ($stmt->fetch() === false) {
        return jsonResponse($res, ['error' => 'Candidato não encontrado'], 404);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO votes (candidate_id, cpf) VALUES (?, ?) ON CONFLICT (cpf) DO NOTHING');
        $stmt->execute([$candidateId, (string) $cpf]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();

            return jsonResponse($res, ['error' => 'Voto duplicado'], 409);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $cache = $getCache();
    $cache->del('votes:results');

    return jsonResponse($res, ['candidateId' => (int) $candidateId, 'voted' => true], 201);
});

$app->get('/api/votes/results', function (Request $req, Response $res) use ($getPdo, $getCache): Response {
    $cache = $getCache();
    $cached = $cache->get('votes:results');

    if ($cached !== null) {
        $res->getBody()->write($cached);

        return $res->withHeader('Content-Type', 'application/json');
    }

    $pdo = $getPdo();
    $rows = $pdo->query(
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

    return jsonResponse($res, $payload);
});

$app->get('/api/votes/results/{candidateId}', function (Request $req, Response $res, array $args) use ($getPdo): Response {
    $pdo = $getPdo();
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name, COUNT(v.id) AS votes
         FROM candidates c
         LEFT JOIN votes v ON v.candidate_id = c.id
         WHERE c.id = ?
         GROUP BY c.id, c.name'
    );
    $stmt->execute([$args['candidateId']]);
    $row = $stmt->fetch();

    if ($row === false) {
        return jsonResponse($res, ['error' => 'Candidato não encontrado'], 404);
    }

    $row['votes'] = (int) $row['votes'];

    return jsonResponse($res, $row);
});

$app->delete('/api/votes', function (Request $req, Response $res) use ($getPdo, $getCache): Response {
    $pdo = $getPdo();
    $pdo->exec('TRUNCATE votes RESTART IDENTITY');
    $cache = $getCache();
    $cache->del('votes:results');

    return jsonResponse($res, ['cleared' => true]);
});

$app->run();
