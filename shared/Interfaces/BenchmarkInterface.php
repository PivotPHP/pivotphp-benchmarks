<?php

declare(strict_types=1);

namespace Shared\Interfaces;

interface BenchmarkInterface
{
    /**
     * Run the benchmark with specified iterations
     */
    public function run(int $iterations = 10000, int $warmup = 1000): array;
    
    /**
     * Get benchmark name
     */
    public function getName(): string;
    
    /**
     * Get benchmark description
     */
    public function getDescription(): string;
}