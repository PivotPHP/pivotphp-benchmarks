#!/usr/bin/env php
<?php

/**
 * Container Resolution Benchmark
 * 
 * Tests the performance of dependency injection container
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use PivotPHP\Core\Core\Container;
use PivotBenchmark\BenchmarkBase;

// Simple service for benchmarking
class TestService
{
    public function process(): string
    {
        return 'processed';
    }
}

class ContainerResolutionBenchmark extends BenchmarkBase
{
    private Container $container;
    
    protected function getName(): string
    {
        return 'container/resolution';
    }
    
    protected function setup(): void
    {
        $this->container = Container::getInstance();
        
        // Register service using closure to avoid binding issues
        $this->container->bind('test_service', function() {
            return new TestService();
        });
        
        // Register singleton
        $this->container->singleton('config', function() {
            return [
                'app_name' => 'PivotPHP Benchmark',
                'version' => '1.0.0',
                'debug' => false
            ];
        });
        
        // Register 10 numbered services
        for ($i = 1; $i <= 10; $i++) {
            $this->container->bind("service.{$i}", function() use ($i) {
                return new class($i) {
                    private int $id;
                    
                    public function __construct(int $id)
                    {
                        $this->id = $id;
                    }
                    
                    public function getId(): int
                    {
                        return $this->id;
                    }
                };
            });
        }
    }
    
    protected function runSingleIteration(): void
    {
        // Test service resolution  
        $service = $this->container->make('test_service');
        
        // Test singleton resolution
        $config = $this->container->make('config');
        
        // Test random service resolution
        $randomService = $this->container->make('service.' . rand(1, 10));
        
        // Consume services to ensure they work
        $service->process();
        count($config);
        $randomService->getId();
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new ContainerResolutionBenchmark($options);
$benchmark->run();