<?php

declare(strict_types=1);

/**
 * Entry point para servidor ReactPHP - Fase 2
 * 
 * Este arquivo inicializa o servidor ReactPHP com configurações
 * otimizadas para benchmarks de alta concorrência.
 */

use PivotPHP\Benchmark\Phase2\ReactPHP\Server;

// Carregar autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Configurar environment para ReactPHP
ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Configurar memory limit para long-running process
ini_set('memory_limit', '1G');
ini_set('max_execution_time', '0'); // Sem limite para servidor contínuo

// Configurar timezone UTC
date_default_timezone_set('UTC');

// Carregar configurações
$config = json_decode(file_get_contents(__DIR__ . '/../../config/benchmark-phase2.json'), true)['phase2_config'];
$seeds = json_decode(file_get_contents(__DIR__ . '/../../config/seeds-phase2.json'), true)['deterministic_seeds'];

// Configurar seed global
$globalSeed = getenv('BENCHMARK_SEED') ?: $seeds['primary_seed'];
mt_srand((int)$globalSeed);

// Configurações do servidor
$serverConfig = [
    'host' => getenv('REACTPHP_HOST') ?: '0.0.0.0',
    'port' => (int)(getenv('REACTPHP_PORT') ?: 8080),
    'websocket_port' => (int)(getenv('REACTPHP_WS_PORT') ?: 9090),
    'max_connections' => (int)(getenv('REACTPHP_MAX_CONN') ?: 1000),
    'memory_limit' => getenv('MEMORY_LIMIT') ?: '1G',
    'gc_enabled' => true
];

// Exibir informações de inicialização
echo "🔧 Inicializando ReactPHP Server - Fase 2\n";
echo "=====================================\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "OPcache: " . (function_exists('opcache_get_status') && opcache_get_status()['opcache_enabled'] ? 'Enabled' : 'Disabled') . "\n";
echo "Seed: $globalSeed\n";
echo "=====================================\n";

// Handler para sinais de sistema (graceful shutdown)
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function () {
        echo "\n🛑 Received SIGTERM, shutting down gracefully...\n";
        exit(0);
    });
    
    pcntl_signal(SIGINT, function () {
        echo "\n🛑 Received SIGINT, shutting down gracefully...\n";
        exit(0);
    });
}

try {
    // Criar e iniciar servidor
    $server = new Server($serverConfig);
    
    // Registrar handler para erros não capturados
    set_exception_handler(function (Throwable $e) {
        echo "❌ Uncaught exception: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    });
    
    // Registrar handler para erros fatais
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            echo "❌ Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}\n";
        }
    });
    
    // Iniciar servidor (bloqueante)
    $server->start();
    
} catch (Throwable $e) {
    echo "❌ Failed to start ReactPHP server: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}