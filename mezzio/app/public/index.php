<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Bench\Support\Cache;
use Bench\Support\CpfValidator;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteResult;
use Predis\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// PSR-11 container mínimo.
$container = new class implements ContainerInterface {
    private array $services = [];

    public function get(string $id): mixed
    {
        return $this->services[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
};

// Banco (PDO, mesmo SQL em todos os frameworks).
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

$cache = new Cache(new Client([
    'scheme' => 'tcp',
    'host'   => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port'   => 6379,
]));

$json = static function (mixed $data, int $status = 200): ResponseInterface {
    $response = new Response();
    $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
};

$params = static function (ServerRequestInterface $request): array {
    /** @var RouteResult|null $result */
    $result = $request->getAttribute(RouteResult::class);

    return $result?->getMatchedParams() ?? [];
};

// ---- Wiring Mezzio (programático) ----
$router   = new FastRouteRouter();
$routes   = new RouteCollector($router);
$pipeline = new MiddlewarePipe();
$factory  = new MiddlewareFactory(new MiddlewareContainer($container));

$runner = new RequestHandlerRunner(
    $pipeline,
    new SapiEmitter(),
    [ServerRequestFactory::class, 'fromGlobals'],
    new ServerRequestErrorResponseGenerator(new ResponseFactory())
);

$app = new Application($factory, $pipeline, $routes, $runner);

// ---- Rotas (mesmo contrato/SQL da votação) ----
$app->get('/api/health', static fn (ServerRequestInterface $req): ResponseInterface => $json(['status' => 'ok']));

$app->get('/api/candidates', static function (ServerRequestInterface $req) use ($pdo, $cache, $json): ResponseInterface {
    $cached = $cache->get('candidates');
    if ($cached !== null) {
        $res = new Response();
        $res->getBody()->write($cached);

        return $res->withHeader('Content-Type', 'application/json');
    }

    $candidates = $pdo->query('SELECT id, name, position FROM candidates ORDER BY id')->fetchAll();
    $cache->set('candidates', (string) json_encode($candidates, JSON_UNESCAPED_UNICODE));

    return $json($candidates);
});

$app->get('/api/candidates/{id}', static function (ServerRequestInterface $req) use ($pdo, $json, $params): ResponseInterface {
    $stmt = $pdo->prepare('SELECT id, name, position FROM candidates WHERE id = ?');
    $stmt->execute([$params($req)['id']]);
    $candidate = $stmt->fetch();

    if ($candidate === false) {
        return $json(['error' => 'Candidato não encontrado'], 404);
    }

    return $json($candidate);
});

$app->post('/api/votes', static function (ServerRequestInterface $req) use ($pdo, $cache, $json): ResponseInterface {
    $body = json_decode((string) $req->getBody(), true) ?? [];
    $candidateId = $body['candidateId'] ?? null;
    $cpf = $body['cpf'] ?? null;

    if ($candidateId === null || $cpf === null) {
        return $json(['error' => 'candidateId e cpf são obrigatórios'], 422);
    }

    if (!CpfValidator::isValid($cpf)) {
        return $json(['error' => 'CPF inválido'], 422);
    }

    $stmt = $pdo->prepare('SELECT id FROM candidates WHERE id = ?');
    $stmt->execute([(int) $candidateId]);
    if ($stmt->fetch() === false) {
        return $json(['error' => 'Candidato não encontrado'], 404);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO votes (candidate_id, cpf) VALUES (?, ?) ON CONFLICT (cpf) DO NOTHING');
        $stmt->execute([$candidateId, (string) $cpf]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();

            return $json(['error' => 'Voto duplicado'], 409);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $cache->del('votes:results');

    return $json(['candidateId' => (int) $candidateId, 'voted' => true], 201);
});

$app->get('/api/votes/results', static function (ServerRequestInterface $req) use ($pdo, $cache, $json): ResponseInterface {
    $cached = $cache->get('votes:results');
    if ($cached !== null) {
        $res = new Response();
        $res->getBody()->write($cached);

        return $res->withHeader('Content-Type', 'application/json');
    }

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

    return $json($payload);
});

$app->get('/api/votes/results/{candidateId}', static function (ServerRequestInterface $req) use ($pdo, $json, $params): ResponseInterface {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name, COUNT(v.id) AS votes
         FROM candidates c
         LEFT JOIN votes v ON v.candidate_id = c.id
         WHERE c.id = ?
         GROUP BY c.id, c.name'
    );
    $stmt->execute([$params($req)['candidateId']]);
    $row = $stmt->fetch();

    if ($row === false) {
        return $json(['error' => 'Candidato não encontrado'], 404);
    }

    $row['votes'] = (int) $row['votes'];

    return $json($row);
});

$app->delete('/api/votes', static function (ServerRequestInterface $req) use ($pdo, $cache, $json): ResponseInterface {
    $pdo->exec('TRUNCATE votes RESTART IDENTITY');
    $cache->del('votes:results');

    return $json(['cleared' => true]);
});

// ---- Middleware de despacho das rotas ----
$app->pipe(new RouteMiddleware($router));
$app->pipe(new DispatchMiddleware());

$app->run();
