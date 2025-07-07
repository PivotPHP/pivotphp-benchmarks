#!/usr/bin/env php
<?php

/**
 * Symfony Middleware Benchmark
 * 
 * Tests the performance of middleware (event listeners) execution in Symfony
 */

require_once __DIR__ . '/../../../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use HelixBenchmark\BenchmarkBase;

class SymfonyMiddlewareBenchmark extends BenchmarkBase
{
    private HttpKernel $kernel;
    private EventDispatcher $dispatcher;
    
    protected function getName(): string
    {
        return 'comparative/symfony/middleware';
    }
    
    protected function setup(): void
    {
        $this->dispatcher = new EventDispatcher();
        
        // Add middleware-like event listeners
        for ($i = 0; $i < 5; $i++) {
            // Request listener (before controller)
            $this->dispatcher->addListener(KernelEvents::REQUEST, function (RequestEvent $event) use ($i) {
                $request = $event->getRequest();
                $request->attributes->set("middleware_{$i}", true);
            }, -($i * 10)); // Priority to ensure order
            
            // Response listener (after controller)
            $this->dispatcher->addListener(KernelEvents::RESPONSE, function (ResponseEvent $event) use ($i) {
                $response = $event->getResponse();
                $response->headers->set("X-Middleware-{$i}", 'processed');
            }, -($i * 10));
        }
        
        // Create kernel
        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();
        
        $this->kernel = new HttpKernel(
            $this->dispatcher,
            $controllerResolver,
            null,
            $argumentResolver
        );
    }
    
    protected function runSingleIteration(): void
    {
        // Create request
        $request = Request::create('/test', 'GET');
        
        // Set controller
        $request->attributes->set('_controller', function () {
            return new JsonResponse(['status' => 'ok']);
        });
        
        // Handle request
        $response = $this->kernel->handle($request);
    }
    
    protected function generateResults(): array
    {
        $results = parent::generateResults();
        $results['framework'] = 'symfony';
        $results['middleware_count'] = 5;
        return $results;
    }
}

// Parse command line arguments
$options = getopt('', ['iterations:', 'warmup:', 'json']);

// Run benchmark
$benchmark = new SymfonyMiddlewareBenchmark($options);
$benchmark->run();