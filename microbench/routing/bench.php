<?php

/**
 * Microbench de roteamento do pivotphp/core-routing.
 *
 * Mede, em um processo isolado, o custo de registrar N rotas e de resolver uma requisição
 * nos cenários que o core trata (rota estática, dinâmica, HEAD, OPTIONS, 405 e 404).
 *
 * A resolução usa a mesma sequência de chamadas que o core faz em cada versão:
 * - core-routing 3.1+ (core 5.2): Router::resolve()
 * - core-routing 3.0  (core 5.0): Router::identify() e, se não casar, Router::allowedMethods()
 *
 * Uso:
 *   php bench.php --autoload=versions/3.1.0/vendor/autoload.php [--routes=500] [--samples=30]
 *                 [--iterations=2000] [--api=auto|legacy]
 *
 * Saída: JSON em stdout, com a mediana e os quartis de ns/op de cada cenário.
 */

declare(strict_types=1);

use Composer\InstalledVersions;
use PivotPHP\Routing\Router\Router;

$options = getopt('', ['autoload:', 'routes::', 'samples::', 'iterations::', 'api::']);
if (!isset($options['autoload']) || !is_string($options['autoload'])) {
    fwrite(STDERR, "uso: php bench.php --autoload=<vendor/autoload.php> [--routes=N] [--samples=N]"
        . " [--iterations=N] [--api=auto|legacy]\n");
    exit(2);
}

require $options['autoload'];

$routeCount = max(5, (int) ($options['routes'] ?? 500));
$samples = max(5, (int) ($options['samples'] ?? 30));
$iterations = max(1, (int) ($options['iterations'] ?? 2000));
$apiOption = (string) ($options['api'] ?? 'auto');

$hasResolve = method_exists(Router::class, 'resolve');
$api = ($apiOption === 'legacy' || !$hasResolve) ? 'legacy' : 'resolve';

/**
 * Registra as rotas de forma determinística: recursos REST com 5 rotas cada
 * (2 estáticas + 3 dinâmicas), como uma API típica.
 */
function buildRouter(int $routeCount): Router
{
    $router = new Router();
    $handler = static fn (): null => null;
    $resources = intdiv($routeCount, 5);

    for ($i = 0; $i < $resources; $i++) {
        $base = "/api/v1/resource{$i}";
        $router->get($base, $handler);
        $router->post($base, $handler);
        $router->get("{$base}/:id", $handler);
        $router->put("{$base}/:id", $handler);
        $router->delete("{$base}/:id", $handler);
    }

    return $router;
}

/**
 * Resolve como o core da versão correspondente e devolve o desfecho:
 * 'match', 'allow' (caminho existe em outro método: 405 ou OPTIONS automático) ou 'none' (404).
 */
function dispatch(Router $router, string $api, string $method, string $path): string
{
    if ($api === 'resolve') {
        $resolution = $router->resolve($method, $path);
        if ($resolution->isMatched()) {
            return 'match';
        }
        return $resolution->isMethodNotAllowed() ? 'allow' : 'none';
    }

    if ($router->identify($method, $path) !== null) {
        return 'match';
    }
    return $router->allowedMethods($path) !== [] ? 'allow' : 'none';
}

/**
 * @param callable(): void $operation
 * @return array{median_ns: float, p25_ns: float, p75_ns: float, samples_ns: list<float>}
 */
function measure(callable $operation, int $samples, int $iterations): array
{
    // Aquecimento: popula caches de regex e do autoload antes de medir.
    for ($i = 0; $i < $iterations; $i++) {
        $operation();
    }

    $perOp = [];
    for ($s = 0; $s < $samples; $s++) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $operation();
        }
        $perOp[] = (hrtime(true) - $start) / $iterations;
    }

    $sorted = $perOp;
    sort($sorted);

    return [
        'median_ns' => percentile($sorted, 0.50),
        'p25_ns' => percentile($sorted, 0.25),
        'p75_ns' => percentile($sorted, 0.75),
        'samples_ns' => $perOp,
    ];
}

/**
 * @param list<float> $sorted
 */
function percentile(array $sorted, float $q): float
{
    $pos = ($sorted === [] ? 0 : count($sorted) - 1) * $q;
    $low = (int) floor($pos);
    $high = (int) ceil($pos);
    return $sorted[$low] + ($sorted[$high] - $sorted[$low]) * ($pos - $low);
}

$router = buildRouter($routeCount);
$last = intdiv($routeCount, 5) - 1;
$base = "/api/v1/resource{$last}";

// O alvo é o último recurso registrado: pior caso para uma busca linear.
$scenarios = [
    'static_hit' => ['GET', $base, 'match'],
    'dynamic_hit' => ['GET', "{$base}/123", 'match'],
    'head_fallback' => ['HEAD', $base, 'match'],
    'options_auto' => ['OPTIONS', $base, 'allow'],
    'method_not_allowed' => ['PATCH', $base, 'allow'],
    'not_found' => ['GET', '/api/v1/missing/123', 'none'],
];

$results = [];

foreach ($scenarios as $name => [$method, $path, $expected]) {
    $outcome = dispatch($router, $api, $method, $path);
    if ($outcome !== $expected) {
        fwrite(STDERR, "cenário {$name}: esperado '{$expected}', obtido '{$outcome}' ({$method} {$path})\n");
        exit(1);
    }

    $results[$name] = measure(
        static function () use ($router, $api, $method, $path): void {
            dispatch($router, $api, $method, $path);
        },
        $samples,
        $iterations
    );
}

// Registro: constrói o router inteiro a cada operação, com menos iterações por amostra.
$results['register'] = measure(
    static function () use ($routeCount): void {
        buildRouter($routeCount);
    },
    $samples,
    max(1, intdiv($iterations, 100))
);

echo json_encode([
    'core_routing' => InstalledVersions::getPrettyVersion('pivotphp/core-routing'),
    'api' => $api,
    'php' => PHP_VERSION,
    'opcache' => function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false),
    'jit' => (string) ini_get('opcache.jit'),
    'routes' => $routeCount,
    'samples' => $samples,
    'iterations' => $iterations,
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
