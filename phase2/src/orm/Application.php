<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase2\ORM;

use PivotPHP\Core\Core\Application as PivotApplication;
use Cycle\ORM\ORM;
use Cycle\ORM\Factory;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator;
use Cycle\Schema\Registry;
use Spiral\Tokenizer\Tokenizer;
use Spiral\Tokenizer\Config\TokenizerConfig;
use PivotPHP\Benchmark\Phase2\ORM\Models\{User, Post, Comment, Tag};
use PivotPHP\Benchmark\Phase2\ORM\Repositories\{UserRepository, PostRepository, CommentRepository, TagRepository};

/**
 * Aplicação PivotPHP Core + Cycle ORM configurada para benchmarks científicos
 * 
 * Características:
 * - Configuração otimizada para performance ORM
 * - Endpoints específicos para testes CRUD
 * - Queries complexas para benchmark
 * - Cache de metadata habilitado
 */
class Application
{
    private PivotApplication $app;
    private ORM $orm;
    private array $config;
    private array $dbConfig;

    public function __construct(array $config, array $dbConfig)
    {
        $this->config = $config;
        $this->dbConfig = $dbConfig;
        
        $this->initializeApplication();
        $this->initializeORM();
        $this->registerRoutes();
    }

    /**
     * Inicializa aplicação PivotPHP Core
     */
    private function initializeApplication(): void
    {
        $this->app = new PivotApplication();
        $this->app->boot();
        
        // Configurações de benchmark
        $this->configureForBenchmark();
    }

    /**
     * Configura aplicação para benchmark
     */
    private function configureForBenchmark(): void
    {
        // Desabilitar logs desnecessários
        if (getenv('BENCHMARK_PHASE') === '2') {
            error_reporting(E_ERROR | E_PARSE);
            ini_set('log_errors', '0');
        }
        
        // Configurar timezone
        date_default_timezone_set('UTC');
        
        // Configurar JSON encoding
        ini_set('serialize_precision', '17');
    }

    /**
     * Inicializa Cycle ORM
     */
    private function initializeORM(): void
    {
        // Configurar database manager
        $databaseManager = new DatabaseManager(
            new DatabaseConfig([
                'default' => 'default',
                'databases' => [
                    'default' => [
                        'connection' => 'mysql'
                    ]
                ],
                'connections' => [
                    'mysql' => [
                        'driver' => \Cycle\Database\Driver\MySQL\MySQLDriver::class,
                        'connection' => sprintf(
                            'mysql:host=%s;port=%d;dbname=%s',
                            $this->dbConfig['host'],
                            $this->dbConfig['port'],
                            $this->dbConfig['database']
                        ),
                        'username' => $this->dbConfig['username'],
                        'password' => $this->dbConfig['password'],
                        'options' => [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                            \PDO::ATTR_EMULATE_PREPARES => false,
                        ]
                    ]
                ]
            ])
        );

        // Configurar tokenizer para entities
        $tokenizer = new Tokenizer(new TokenizerConfig([
            'directories' => [__DIR__ . '/Models'],
            'exclude' => []
        ]));

        // Criar locator de entities
        $locator = new TokenizerEntityLocator($tokenizer);

        // Configurar registry
        $registry = new Registry($databaseManager);

        // Generators para schema
        $generators = [
            new Generator\ResetTables(),
            new Generator\GenerateRelations(),
            new Generator\ValidateEntities(),
            new Generator\RenderTables(),
            new Generator\RenderRelations(),
            new Generator\RenderModifiers(),
            new Generator\GenerateTypecast(),
        ];

        // Compilar schema
        $schema = (new Compiler())->compile($registry, $generators);

        // Criar ORM
        $this->orm = new ORM(
            new Factory($databaseManager),
            $schema
        );
    }

    /**
     * Registra todas as rotas de benchmark ORM
     */
    private function registerRoutes(): void
    {
        // Health check
        $this->app->get('/health', fn($request, $response) => $this->health($request, $response));
        
        // Metadata
        $this->app->get('/meta', fn($request, $response) => $this->metadata($request, $response));
        
        // === CRUD OPERATIONS ===
        
        // Users CRUD
        $this->app->get('/orm/users', fn($request, $response) => $this->listUsers($request, $response));
        $this->app->get('/orm/users/{id}', fn($request, $response) => $this->getUser($request, $response));
        $this->app->post('/orm/users', fn($request, $response) => $this->createUser($request, $response));
        $this->app->put('/orm/users/{id}', fn($request, $response) => $this->updateUser($request, $response));
        $this->app->delete('/orm/users/{id}', fn($request, $response) => $this->deleteUser($request, $response));
        
        // Posts CRUD
        $this->app->get('/orm/posts', fn($request, $response) => $this->listPosts($request, $response));
        $this->app->get('/orm/posts/{id}', fn($request, $response) => $this->getPost($request, $response));
        $this->app->post('/orm/posts', fn($request, $response) => $this->createPost($request, $response));
        $this->app->put('/orm/posts/{id}', fn($request, $response) => $this->updatePost($request, $response));
        $this->app->delete('/orm/posts/{id}', fn($request, $response) => $this->deletePost($request, $response));
        
        // Comments CRUD
        $this->app->get('/orm/comments', fn($request, $response) => $this->listComments($request, $response));
        $this->app->post('/orm/comments', fn($request, $response) => $this->createComment($request, $response));
        
        // === COMPLEX QUERIES ===
        
        // Queries com joins
        $this->app->get('/orm/users-with-posts', fn($request, $response) => $this->usersWithPosts($request, $response));
        $this->app->get('/orm/posts-with-comments', fn($request, $response) => $this->postsWithComments($request, $response));
        $this->app->get('/orm/complex-join', fn($request, $response) => $this->complexJoin($request, $response));
        
        // Queries de agregação
        $this->app->get('/orm/stats', fn($request, $response) => $this->getStats($request, $response));
        $this->app->get('/orm/popular-posts', fn($request, $response) => $this->popularPosts($request, $response));
        $this->app->get('/orm/user-stats/{id}', fn($request, $response) => $this->userStats($request, $response));
        
        // === PERFORMANCE TESTS ===
        
        // Lazy vs Eager loading
        $this->app->get('/orm/lazy-loading', fn($request, $response) => $this->lazyLoadingTest($request, $response));
        $this->app->get('/orm/eager-loading', fn($request, $response) => $this->eagerLoadingTest($request, $response));
        
        // Batch operations
        $this->app->post('/orm/batch-users', fn($request, $response) => $this->batchCreateUsers($request, $response));
        $this->app->post('/orm/batch-posts', fn($request, $response) => $this->batchCreatePosts($request, $response));
        
        // Hydration tests
        $this->app->get('/orm/hydration/{count}', fn($request, $response) => $this->hydrationTest($request, $response));
        
        // Transaction tests
        $this->app->post('/orm/transaction-test', fn($request, $response) => $this->transactionTest($request, $response));
    }

    // === HEALTH & META ===

    public function health($request, $response)
    {
        return $response->json([
            'status' => 'healthy',
            'extension' => 'cycle-orm',
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
            'orm_connected' => $this->testDatabaseConnection()
        ]);
    }

    public function metadata($request, $response)
    {
        return $response->json([
            'application' => [
                'name' => 'PivotPHP + Cycle ORM Benchmark Phase 2',
                'version' => '1.0.0',
                'framework' => 'pivotphp-core',
                'extension' => 'cycle-orm'
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'],
                'jit_enabled' => $this->isJitEnabled()
            ],
            'database' => [
                'connected' => $this->testDatabaseConnection(),
                'host' => $this->dbConfig['host'],
                'database' => $this->dbConfig['database']
            ]
        ]);
    }

    // === CRUD OPERATIONS ===

    public function listUsers($request, $response)
    {
        $page = (int)($request->query('page') ?? 1);
        $limit = (int)($request->query('limit') ?? 20);
        
        $repository = $this->orm->getRepository(User::class);
        $users = $repository->findPaginated($page, $limit);
        
        return $response->json([
            'users' => array_map(fn($user) => $user->toArray(), $users),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($users)
            ]
        ]);
    }

    public function getUser($request, $response)
    {
        $id = (int)$request->param('id');
        
        $user = $this->orm->getRepository(User::class)->findByPK($id);
        
        if (!$user) {
            return $response->status(404)->json(['error' => 'User not found']);
        }
        
        return $response->json($user->toArray());
    }

    public function createUser($request, $response)
    {
        $data = $request->json();
        
        if (empty($data['name']) || empty($data['email'])) {
            return $response->status(400)->json(['error' => 'Name and email required']);
        }
        
        $user = new User($data['name'], $data['email']);
        
        $this->orm->persist($user);
        $this->orm->run();
        
        return $response->status(201)->json($user->toArray());
    }

    public function updateUser($request, $response)
    {
        $id = (int)$request->param('id');
        $data = $request->json();
        
        $user = $this->orm->getRepository(User::class)->findByPK($id);
        
        if (!$user) {
            return $response->status(404)->json(['error' => 'User not found']);
        }
        
        if (!empty($data['name'])) {
            $user->updateName($data['name']);
        }
        
        if (!empty($data['email'])) {
            $user->updateEmail($data['email']);
        }
        
        $this->orm->persist($user);
        $this->orm->run();
        
        return $response->json($user->toArray());
    }

    public function deleteUser($request, $response)
    {
        $id = (int)$request->param('id');
        
        $user = $this->orm->getRepository(User::class)->findByPK($id);
        
        if (!$user) {
            return $response->status(404)->json(['error' => 'User not found']);
        }
        
        $this->orm->delete($user);
        $this->orm->run();
        
        return $response->status(204);
    }

    // === COMPLEX QUERIES ===

    public function complexJoin($request, $response)
    {
        $repository = $this->orm->getRepository(Post::class);
        $posts = $repository->findComplexJoin();
        
        return $response->json([
            'posts' => array_map(fn($post) => $post->toArray(), $posts),
            'query_info' => [
                'type' => 'complex_join',
                'joins' => ['user', 'comments.user', 'tags'],
                'count' => count($posts)
            ]
        ]);
    }

    public function getStats($request, $response)
    {
        $userRepo = $this->orm->getRepository(User::class);
        $postRepo = $this->orm->getRepository(Post::class);
        
        $userStats = $userRepo->getUsersStats();
        $postStats = $postRepo->getPostsStats();
        
        return $response->json([
            'stats' => array_merge($userStats, $postStats),
            'generated_at' => microtime(true)
        ]);
    }

    // === PERFORMANCE TESTS ===

    public function lazyLoadingTest($request, $response)
    {
        $startTime = microtime(true);
        
        $repository = $this->orm->getRepository(User::class);
        $users = $repository->findWithLazyLoading(10);
        
        // Acessar posts para triggerar lazy loading
        $postsCount = 0;
        foreach ($users as $user) {
            $postsCount += count($user->posts);
        }
        
        $endTime = microtime(true);
        
        return $response->json([
            'test_type' => 'lazy_loading',
            'users_count' => count($users),
            'posts_loaded' => $postsCount,
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    public function eagerLoadingTest($request, $response)
    {
        $startTime = microtime(true);
        
        $repository = $this->orm->getRepository(User::class);
        $users = $repository->findWithEagerLoading(10);
        
        $postsCount = 0;
        foreach ($users as $user) {
            $postsCount += count($user->posts);
        }
        
        $endTime = microtime(true);
        
        return $response->json([
            'test_type' => 'eager_loading',
            'users_count' => count($users),
            'posts_loaded' => $postsCount,
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    public function hydrationTest($request, $response)
    {
        $count = min((int)$request->param('count'), 1000); // Limite de segurança
        
        $startTime = microtime(true);
        
        $repository = $this->orm->getRepository(User::class);
        $users = $repository->select()->limit($count)->fetchAll();
        
        $endTime = microtime(true);
        
        return $response->json([
            'test_type' => 'hydration',
            'entities_hydrated' => count($users),
            'execution_time_ms' => ($endTime - $startTime) * 1000,
            'hydration_rate' => count($users) / (($endTime - $startTime) ?: 0.001),
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    // === UTILITY METHODS ===

    private function testDatabaseConnection(): bool
    {
        try {
            $this->orm->getRepository(User::class)->select()->limit(1)->fetchAll();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isJitEnabled(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }
        
        $status = opcache_get_status(false);
        return isset($status['jit']['enabled']) ? $status['jit']['enabled'] : false;
    }

    /**
     * Executa a aplicação
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * Retorna instância da aplicação PivotPHP
     */
    public function getApp(): PivotApplication
    {
        return $this->app;
    }

    /**
     * Retorna instância do ORM
     */
    public function getORM(): ORM
    {
        return $this->orm;
    }
}