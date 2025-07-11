<?php

declare(strict_types=1);

namespace App\Providers;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PivotPHP\Core\Providers\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerLogger();
        $this->registerConfiguration();
    }

    public function boot(): void
    {
        $this->configureApplication();
    }

    private function registerLogger(): void
    {
        $loggerFactory = function () {
            $logger = new Logger('app');
            $logPath = dirname(__DIR__, 2) . '/storage/logs/app.log';
            
            // Ensure logs directory exists
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
            return $logger;
        };

        $this->app->singleton(LoggerInterface::class, $loggerFactory);
        $this->app->singleton('Psr\Log\LoggerInterface', $loggerFactory);
        $this->app->singleton('logger', $loggerFactory);
    }

    private function registerConfiguration(): void
    {
        $configPath = dirname(__DIR__, 2) . '/config';
        
        $configs = [
            'app' => require $configPath . '/app.php',
            'database' => require $configPath . '/database.php',
        ];
        
        foreach ($configs as $key => $config) {
            $this->app->make('config')->set($key, $config);
        }
    }

    private function configureApplication(): void
    {
        // Set error reporting based on debug mode
        if ($this->app->make('config')->get('app.debug', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }
        
        // Configure timezone
        date_default_timezone_set(
            $this->app->make('config')->get('app.timezone', 'UTC')
        );
    }
}