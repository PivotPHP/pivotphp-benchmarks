<?php

declare(strict_types=1);

namespace Shared\Traits;

trait ConcurrencyTrait
{
    /**
     * Execute concurrent requests using multiple processes
     */
    protected function runConcurrent(callable $task, int $concurrency, int $totalRequests): array
    {
        $requestsPerProcess = (int) ceil($totalRequests / $concurrency);
        $pids = [];
        $stats = [];
        
        // Create shared memory for collecting results
        $shmKey = ftok(__FILE__, 'b');
        $shmId = shmop_open($shmKey, "c", 0644, 1024 * 1024); // 1MB shared memory
        
        // Initialize shared memory
        shmop_write($shmId, str_repeat("\0", 1024 * 1024), 0);
        
        $startTime = microtime(true);
        
        // Fork processes
        for ($i = 0; $i < $concurrency; $i++) {
            $pid = pcntl_fork();
            
            if ($pid == -1) {
                die("Could not fork process\n");
            } elseif ($pid == 0) {
                // Child process
                $processStats = $this->runRequestsInProcess($task, $requestsPerProcess, $i);
                
                // Write stats to shared memory
                $offset = $i * 128; // Each process gets 128 bytes
                $data = json_encode($processStats);
                shmop_write($shmId, str_pad($data, 128, "\0"), $offset);
                
                exit(0);
            } else {
                // Parent process
                $pids[] = $pid;
            }
        }
        
        // Wait for all child processes
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Collect results from shared memory
        $allStats = [];
        for ($i = 0; $i < $concurrency; $i++) {
            $offset = $i * 128;
            $data = trim(shmop_read($shmId, $offset, 128));
            if ($data) {
                $allStats[] = json_decode($data, true);
            }
        }
        
        // Clean up shared memory
        shmop_delete($shmId);
        shmop_close($shmId);
        
        // Aggregate statistics
        $totalCompleted = array_sum(array_column($allStats, 'completed'));
        $totalErrors = array_sum(array_column($allStats, 'errors'));
        $allLatencies = [];
        
        foreach ($allStats as $stat) {
            $allLatencies = array_merge($allLatencies, $stat['latencies'] ?? []);
        }
        
        sort($allLatencies);
        
        return [
            'total_requests' => $totalRequests,
            'concurrency' => $concurrency,
            'total_time' => $totalTime,
            'requests_per_second' => $totalCompleted / $totalTime,
            'completed_requests' => $totalCompleted,
            'failed_requests' => $totalErrors,
            'latency_ms' => [
                'min' => min($allLatencies) * 1000,
                'max' => max($allLatencies) * 1000,
                'avg' => (array_sum($allLatencies) / count($allLatencies)) * 1000,
                'p50' => $this->percentile($allLatencies, 0.50) * 1000,
                'p95' => $this->percentile($allLatencies, 0.95) * 1000,
                'p99' => $this->percentile($allLatencies, 0.99) * 1000,
            ]
        ];
    }
    
    private function runRequestsInProcess(callable $task, int $requests, int $processId): array
    {
        $completed = 0;
        $errors = 0;
        $latencies = [];
        
        for ($i = 0; $i < $requests; $i++) {
            $start = microtime(true);
            
            try {
                $task();
                $completed++;
            } catch (\Exception $e) {
                $errors++;
            }
            
            $latencies[] = microtime(true) - $start;
        }
        
        return [
            'process_id' => $processId,
            'completed' => $completed,
            'errors' => $errors,
            'latencies' => $latencies
        ];
    }
    
    private function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        $index = ceil($count * $percentile) - 1;
        return $values[$index] ?? 0;
    }
}