<?php

declare(strict_types=1);

namespace PivotBenchmark;

class Timer
{
    private float $startTime;
    private float $endTime;
    private float $startMemory;
    private float $endMemory;
    private float $peakMemory = 0;
    private array $measurements = [];
    
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
    
    public function stop(): void
    {
        $this->endTime = microtime(true);
        $this->endMemory = memory_get_usage();
        $this->peakMemory = memory_get_peak_usage();
    }
    
    public function lap(): void
    {
        $this->measurements[] = [
            'time' => microtime(true) - $this->startTime,
            'memory' => memory_get_usage()
        ];
    }
    
    public function getElapsedTime(): float
    {
        return $this->endTime - $this->startTime;
    }
    
    public function getMemoryUsage(): float
    {
        return $this->endMemory - $this->startMemory;
    }
    
    public function getPeakMemory(): float
    {
        return $this->peakMemory;
    }
    
    public function report(): array
    {
        return [
            'elapsed_time' => $this->getElapsedTime(),
            'memory_used_bytes' => $this->getMemoryUsage(),
            'memory_used_mb' => $this->getMemoryUsage() / 1024 / 1024,
            'peak_memory_bytes' => $this->getPeakMemory(),
            'peak_memory_mb' => $this->getPeakMemory() / 1024 / 1024,
            'measurements' => $this->measurements
        ];
    }
    
    public function printReport(): void
    {
        $report = $this->report();
        echo "Execution Time: " . number_format($report['elapsed_time'], 4) . " seconds\n";
        echo "Memory Used: " . number_format($report['memory_used_mb'], 2) . " MB\n";
        echo "Peak Memory: " . number_format($report['peak_memory_mb'], 2) . " MB\n";
    }
}