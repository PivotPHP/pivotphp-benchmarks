<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Bench\Support\Cache;
use Bench\Support\CpfValidator;
use Predis\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// Banco & Cache (lazy initialization para não onerar rotas sem banco).
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

$routes = new RouteCollection();

// SPEC-115 — Cenários Modulares (estilo Fastify)
$routes->add('bare', new Route('/api/bare', [
    '_controller' => static fn (): JsonResponse => new JsonResponse(['hello' => 'world']),
]));

$routes->add('user_param', new Route('/api/user/{id}', [
    '_controller' => static fn (string $id): JsonResponse => new JsonResponse(['id' => (string) $id]),
]));

$routes->add('middleware', new Route('/api/middleware', [
    '_controller' => static function (Request $request): JsonResponse {
        $mw = static fn ($req, $next) => $next($req);
        $final = static fn ($req) => new JsonResponse(['hello' => 'world']);
        $pipeline = array_reduce(
            array_fill(0, 5, $mw),
            static fn ($next, $current) => static fn ($req) => $current($req, $next),
            $final
        );
        return $pipeline($request);
    },
]));

$routes->add('echo', new Route('/api/echo', [
    '_controller' => static function (Request $request): JsonResponse {
        $body = json_decode($request->getContent(), true) ?? [];
        return new JsonResponse((array) $body);
    },
], methods: ['POST']));

// Rotas do app completo (votação)
$routes->add('health', new Route('/api/health', [
    '_controller' => static fn (): JsonResponse => new JsonResponse(['status' => 'ok']),
]));

$routes->add('candidates', new Route('/api/candidates', [
    '_controller' => static function () use ($getPdo, $getCache): JsonResponse {
        $cache = $getCache();
        $cached = $cache->get('candidates');
        if ($cached !== null) {
            return JsonResponse::fromJsonString($cached);
        }

        $pdo = $getPdo();
        $candidates = $pdo->query('SELECT id, name, position FROM candidates ORDER BY id')->fetchAll();
        $cache->set('candidates', (string) json_encode($candidates, JSON_UNESCAPED_UNICODE));

        return new JsonResponse($candidates);
    },
]));

$routes->add('candidate_show', new Route('/api/candidates/{id}', [
    '_controller' => static function (int $id) use ($getPdo): JsonResponse {
        $pdo = $getPdo();
        $stmt = $pdo->prepare('SELECT id, name, position FROM candidates WHERE id = ?');
        $stmt->execute([$id]);
        $candidate = $stmt->fetch();

        if ($candidate === false) {
            return new JsonResponse(['error' => 'Candidato não encontrado'], 404);
        }

        return new JsonResponse($candidate);
    },
]));

$routes->add('vote', new Route('/api/votes', [
    '_controller' => static function (Request $request) use ($getPdo, $getCache): JsonResponse {
        $body = json_decode($request->getContent(), true) ?? [];
        $candidateId = $body['candidateId'] ?? null;
        $cpf = $body['cpf'] ?? null;

        if ($candidateId === null || $cpf === null) {
            return new JsonResponse(['error' => 'candidateId e cpf são obrigatórios'], 422);
        }

        if (!CpfValidator::isValid($cpf)) {
            return new JsonResponse(['error' => 'CPF inválido'], 422);
        }

        $pdo = $getPdo();
        $stmt = $pdo->prepare('SELECT id FROM candidates WHERE id = ?');
        $stmt->execute([(int) $candidateId]);
        if ($stmt->fetch() === false) {
            return new JsonResponse(['error' => 'Candidato não encontrado'], 404);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO votes (candidate_id, cpf) VALUES (?, ?) ON CONFLICT (cpf) DO NOTHING');
            $stmt->execute([$candidateId, (string) $cpf]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();

                return new JsonResponse(['error' => 'Voto duplicado'], 409);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $cache = $getCache();
        $cache->del('votes:results');

        return new JsonResponse(['candidateId' => (int) $candidateId, 'voted' => true], 201);
    },
], methods: ['POST']));

$routes->add('results', new Route('/api/votes/results', [
    '_controller' => static function () use ($getPdo, $getCache): JsonResponse {
        $cache = $getCache();
        $cached = $cache->get('votes:results');
        if ($cached !== null) {
            return JsonResponse::fromJsonString($cached);
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

        return new JsonResponse($payload);
    },
]));

$routes->add('result_candidate', new Route('/api/votes/results/{candidateId}', [
    '_controller' => static function (int $candidateId) use ($getPdo): JsonResponse {
        $pdo = $getPdo();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, COUNT(v.id) AS votes
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id
             WHERE c.id = ?
             GROUP BY c.id, c.name'
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return new JsonResponse(['error' => 'Candidato não encontrado'], 404);
        }

        $row['votes'] = (int) $row['votes'];

        return new JsonResponse($row);
    },
]));

$routes->add('clear', new Route('/api/votes', [
    '_controller' => static function () use ($getPdo, $getCache): JsonResponse {
        $pdo = $getPdo();
        $pdo->exec('TRUNCATE votes RESTART IDENTITY');
        $cache = $getCache();
        $cache->del('votes:results');

        return new JsonResponse(['cleared' => true]);
    },
], methods: ['DELETE']));

// ---- Kernel (microkernel Symfony) ----
$request = Request::createFromGlobals();

$matcher = new UrlMatcher($routes, new RequestContext());
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack()));

$kernel = new HttpKernel(
    $dispatcher,
    new ControllerResolver(),
    new RequestStack(),
    new ArgumentResolver()
);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
