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

interface ServiceInterface
{
    public function process(): string;
}

class SimpleService implements ServiceInterface
{
    public function process(): string
    {
        return 'processed';
    }
}

class ComplexService implements ServiceInterface
{
    private SimpleService $simpleService;
    private array $config;
    
    public function __construct(SimpleService $simpleService, array $config = [])
    {
        $this->simpleService = $simpleService;
        $this->config = $config;
    }
    
    public function process(): string
    {
        return $this->simpleService->process() . '-complex';
    }
}

class ServiceWithMultipleDependencies
{
    private ServiceInterface $service1;
    private ServiceInterface $service2;
    private ComplexService $complexService;
    
    public function __construct(
        ServiceInterface $service1,
        ServiceInterface $service2,
        ComplexService $complexService
    ) {
        $this->service1 = $service1;
        $this->service2 = $service2;
        $this->complexService = $complexService;
    }
    
    public function execute(): string
    {
        return $this->service1->process() . 
               $this->service2->process() . 
               $this->complexService->process();
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
        
        // Register simple bindings
        $this->container->bind(ServiceInterface::class, SimpleService::class);
        $this->container->bind(SimpleService::class, SimpleService::class);
        
        // Register complex binding with closure
        $this->container->bind(ComplexService::class, function($container) {
            return new ComplexService(
                $container->make(SimpleService::class),
                ['option' => 'value']
            );
        });
        
        // Register singleton
        $this->container->singleton('config', function() {
            return [
                'app_name' => 'PivotPHP Benchmark',
                'version' => '1.0.0',
                'debug' => false
            ];
        });
        
        // Register service with multiple dependencies
        $this->container->bind(ServiceWithMultipleDependencies::class);
        
        // Register 50 additional services to simulate real-world container
        for ($i = 1; $i <= 50; $i++) {
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
        // Test simple resolution
        $simple = $this->container->make(SimpleService::class);
        
        // Test interface resolution
        $service = $this->container->make(ServiceInterface::class);
        
        // Test complex resolution with dependencies
        $complex = $this->container->make(ComplexService::class);
        
        // Test singleton resolution
        $config = $this->container->make('config');
        
        // Test resolution with multiple dependencies
        $multiDep = $this->container->make(ServiceWithMultipleDependencies::class);
        
        // Test random service resolution
        $randomService = $this->container->make('service.' . rand(1, 50));
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new ContainerResolutionBenchmark($options);
$benchmark->run();