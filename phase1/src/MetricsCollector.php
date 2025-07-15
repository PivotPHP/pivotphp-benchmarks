<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase1;

/**
 * Coletor de métricas de sistema para benchmarks científicos
 * 
 * Características:
 * - Monitoramento em tempo real de CPU, memória, I/O
 * - Coleta não-intrusiva com overhead mínimo
 * - Agregação estatística automática
 * - Detecção de anomalias e picos
 * - Export de dados para análise
 */
class MetricsCollector
{
    private array $config;
    private array $systemMetrics = [];
    private array $processMetrics = [];
    private bool $isCollecting = false;
    private float $collectionStartTime;
    private int $collectionInterval = 100; // milliseconds

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Inicia coleta de métricas de sistema
     */
    public function startCollection(): void
    {
        $this->isCollecting = true;
        $this->collectionStartTime = microtime(true);
        $this->systemMetrics = [];
        $this->processMetrics = [];
        
        echo "📊 Iniciando coleta de métricas de sistema...\n";
    }

    /**
     * Para coleta de métricas
     */
    public function stopCollection(): array
    {
        $this->isCollecting = false;
        $duration = microtime(true) - $this->collectionStartTime;
        
        echo "✅ Coleta de métricas finalizada ({$duration}s)\n";
        
        return $this->generateReport();
    }

    /**
     * Coleta snapshot atual das métricas
     */
    public function collectSnapshot(): array
    {
        $timestamp = microtime(true);
        
        $snapshot = [
            'timestamp' => $timestamp,
            'memory' => $this->collectMemoryMetrics(),
            'cpu' => $this->collectCpuMetrics(),
            'system' => $this->collectSystemMetrics(),
            'php' => $this->collectPhpMetrics()
        ];
        
        $this->systemMetrics[] = $snapshot;
        
        return $snapshot;
    }

    /**
     * Coleta métricas de memória
     */
    private function collectMemoryMetrics(): array
    {
        return [
            // PHP Memory
            'php_memory_usage' => memory_get_usage(true),
            'php_memory_peak' => memory_get_peak_usage(true),
            'php_memory_usage_real' => memory_get_usage(false),
            'php_memory_peak_real' => memory_get_peak_usage(false),
            
            // System Memory (se disponível)
            'system_memory' => $this->getSystemMemory(),
            'memory_limit' => $this->parseMemoryLimit(ini_get('memory_limit'))
        ];
    }

    /**
     * Coleta métricas de CPU (estimativa)
     */
    private function collectCpuMetrics(): array
    {
        static $lastCpuTime = null;
        static $lastTimestamp = null;
        
        $currentTime = microtime(true);
        $cpuUsage = $this->getCpuUsage();
        
        $metrics = [
            'timestamp' => $currentTime,
            'cpu_usage_percent' => $cpuUsage,
            'load_average' => $this->getLoadAverage(),
        ];
        
        // Calcular CPU rate se temos medição anterior
        if ($lastCpuTime !== null && $lastTimestamp !== null) {
            $timeDiff = $currentTime - $lastTimestamp;
            if ($timeDiff > 0) {
                $metrics['cpu_rate'] = ($cpuUsage - $lastCpuTime) / $timeDiff;
            }
        }
        
        $lastCpuTime = $cpuUsage;
        $lastTimestamp = $currentTime;
        
        return $metrics;
    }

    /**
     * Coleta métricas gerais do sistema
     */
    private function collectSystemMetrics(): array
    {
        return [
            'uptime' => $this->getSystemUptime(),
            'disk_usage' => $this->getDiskUsage(),
            'network_stats' => $this->getNetworkStats(),
            'process_count' => $this->getProcessCount()
        ];
    }

    /**
     * Coleta métricas específicas do PHP
     */
    private function collectPhpMetrics(): array
    {
        $metrics = [
            'opcache' => $this->getOpcacheStats(),
            'garbage_collection' => $this->getGcStats(),
            'extensions' => get_loaded_extensions(),
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI
        ];
        
        // JIT stats se disponível
        if (function_exists('opcache_get_status')) {
            $opcacheStatus = opcache_get_status(false);
            if (isset($opcacheStatus['jit'])) {
                $metrics['jit'] = $opcacheStatus['jit'];
            }
        }
        
        return $metrics;
    }

    /**
     * Obtém uso de memória do sistema
     */
    private function getSystemMemory(): array
    {
        $memInfo = [];
        
        if (file_exists('/proc/meminfo')) {
            $content = file_get_contents('/proc/meminfo');
            if ($content) {
                preg_match_all('/^(\w+):\s+(\d+)\s+kB$/m', $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $memInfo[strtolower($match[1])] = (int)$match[2] * 1024; // Convert to bytes
                }
            }
        }
        
        return [
            'total' => $memInfo['memtotal'] ?? null,
            'free' => $memInfo['memfree'] ?? null,
            'available' => $memInfo['memavailable'] ?? null,
            'used' => isset($memInfo['memtotal'], $memInfo['memfree']) 
                ? $memInfo['memtotal'] - $memInfo['memfree'] 
                : null,
            'cached' => $memInfo['cached'] ?? null,
            'buffers' => $memInfo['buffers'] ?? null
        ];
    }

    /**
     * Obtém uso de CPU (aproximação)
     */
    private function getCpuUsage(): float
    {
        static $lastStats = null;
        
        if (!file_exists('/proc/stat')) {
            return 0.0;
        }
        
        $content = file_get_contents('/proc/stat');
        if (!$content) {
            return 0.0;
        }
        
        $lines = explode("\n", $content);
        $cpuLine = $lines[0];
        
        if (!preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $cpuLine, $matches)) {
            return 0.0;
        }
        
        $user = (int)$matches[1];
        $nice = (int)$matches[2];
        $system = (int)$matches[3];
        $idle = (int)$matches[4];
        
        $total = $user + $nice + $system + $idle;
        $work = $user + $nice + $system;
        
        if ($lastStats !== null) {
            $totalDiff = $total - $lastStats['total'];
            $workDiff = $work - $lastStats['work'];
            
            if ($totalDiff > 0) {
                $lastStats = ['total' => $total, 'work' => $work];
                return ($workDiff / $totalDiff) * 100;
            }
        }
        
        $lastStats = ['total' => $total, 'work' => $work];
        return 0.0;
    }

    /**
     * Obtém load average do sistema
     */
    private function getLoadAverage(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2]
            ];
        }
        
        return null;
    }

    /**
     * Obtém uptime do sistema
     */
    private function getSystemUptime(): ?float
    {
        if (file_exists('/proc/uptime')) {
            $content = file_get_contents('/proc/uptime');
            if ($content) {
                $parts = explode(' ', trim($content));
                return (float)$parts[0];
            }
        }
        
        return null;
    }

    /**
     * Obtém uso de disco
     */
    private function getDiskUsage(): array
    {
        $path = '/';
        
        return [
            'total_bytes' => disk_total_space($path),
            'free_bytes' => disk_free_space($path),
            'used_bytes' => disk_total_space($path) - disk_free_space($path),
            'usage_percent' => disk_total_space($path) > 0 
                ? ((disk_total_space($path) - disk_free_space($path)) / disk_total_space($path)) * 100 
                : 0
        ];
    }

    /**
     * Obtém estatísticas de rede (básico)
     */
    private function getNetworkStats(): array
    {
        $stats = [];
        
        if (file_exists('/proc/net/dev')) {
            $content = file_get_contents('/proc/net/dev');
            if ($content) {
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    if (strpos($line, ':') !== false) {
                        $parts = preg_split('/\s+/', trim($line));
                        $interface = rtrim($parts[0], ':');
                        if ($interface !== 'lo') { // Ignorar loopback
                            $stats[$interface] = [
                                'rx_bytes' => (int)($parts[1] ?? 0),
                                'tx_bytes' => (int)($parts[9] ?? 0)
                            ];
                        }
                    }
                }
            }
        }
        
        return $stats;
    }

    /**
     * Obtém contagem de processos
     */
    private function getProcessCount(): ?int
    {
        if (function_exists('shell_exec')) {
            $output = shell_exec('ps aux | wc -l');
            return $output ? (int)trim($output) - 1 : null; // -1 para header
        }
        
        return null;
    }

    /**
     * Obtém estatísticas do OPcache
     */
    private function getOpcacheStats(): ?array
    {
        if (!function_exists('opcache_get_status')) {
            return null;
        }
        
        $status = opcache_get_status(false);
        if (!$status) {
            return null;
        }
        
        return [
            'enabled' => $status['opcache_enabled'] ?? false,
            'cache_full' => $status['cache_full'] ?? false,
            'restart_pending' => $status['restart_pending'] ?? false,
            'restart_in_progress' => $status['restart_in_progress'] ?? false,
            'memory_usage' => $status['memory_usage'] ?? [],
            'opcache_statistics' => $status['opcache_statistics'] ?? [],
            'jit' => $status['jit'] ?? null
        ];
    }

    /**
     * Obtém estatísticas do Garbage Collector
     */
    private function getGcStats(): array
    {
        return [
            'enabled' => gc_enabled(),
            'status' => function_exists('gc_status') ? gc_status() : null,
            'collected' => function_exists('gc_collect_cycles') ? gc_collect_cycles() : null
        ];
    }

    /**
     * Converte limite de memória para bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') {
            return -1;
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int)$limit
        };
    }

    /**
     * Gera relatório estatístico das métricas coletadas
     */
    private function generateReport(): array
    {
        if (empty($this->systemMetrics)) {
            return ['error' => 'Nenhuma métrica coletada'];
        }
        
        $report = [
            'collection_summary' => [
                'start_time' => $this->collectionStartTime,
                'end_time' => microtime(true),
                'duration_seconds' => microtime(true) - $this->collectionStartTime,
                'snapshots_count' => count($this->systemMetrics),
                'collection_frequency_hz' => count($this->systemMetrics) / (microtime(true) - $this->collectionStartTime)
            ],
            'memory_analysis' => $this->analyzeMemoryMetrics(),
            'cpu_analysis' => $this->analyzeCpuMetrics(),
            'system_analysis' => $this->analyzeSystemMetrics(),
            'php_analysis' => $this->analyzePhpMetrics(),
            'raw_data' => $this->systemMetrics
        ];
        
        return $report;
    }

    /**
     * Analisa métricas de memória
     */
    private function analyzeMemoryMetrics(): array
    {
        $memoryUsage = array_column(array_column($this->systemMetrics, 'memory'), 'php_memory_usage');
        $memoryPeak = array_column(array_column($this->systemMetrics, 'memory'), 'php_memory_peak');
        
        return [
            'php_memory_usage' => [
                'min_bytes' => min($memoryUsage),
                'max_bytes' => max($memoryUsage),
                'mean_bytes' => array_sum($memoryUsage) / count($memoryUsage),
                'final_bytes' => end($memoryUsage),
                'growth_bytes' => end($memoryUsage) - $memoryUsage[0],
                'peak_bytes' => max($memoryPeak)
            ]
        ];
    }

    /**
     * Analisa métricas de CPU
     */
    private function analyzeCpuMetrics(): array
    {
        $cpuUsage = array_filter(array_column(array_column($this->systemMetrics, 'cpu'), 'cpu_usage_percent'));
        
        if (empty($cpuUsage)) {
            return ['error' => 'Dados de CPU indisponíveis'];
        }
        
        return [
            'cpu_usage_percent' => [
                'min' => min($cpuUsage),
                'max' => max($cpuUsage),
                'mean' => array_sum($cpuUsage) / count($cpuUsage),
                'median' => $this->calculateMedian($cpuUsage)
            ]
        ];
    }

    /**
     * Analisa métricas do sistema
     */
    private function analyzeSystemMetrics(): array
    {
        return [
            'uptime_seconds' => $this->systemMetrics[0]['system']['uptime'] ?? null,
            'load_average' => end($this->systemMetrics)['cpu']['load_average'] ?? null,
            'disk_usage' => end($this->systemMetrics)['system']['disk_usage'] ?? null
        ];
    }

    /**
     * Analisa métricas do PHP
     */
    private function analyzePhpMetrics(): array
    {
        $lastMetrics = end($this->systemMetrics)['php'] ?? [];
        
        return [
            'version' => $lastMetrics['version'] ?? PHP_VERSION,
            'sapi' => $lastMetrics['sapi'] ?? PHP_SAPI,
            'opcache_enabled' => $lastMetrics['opcache']['enabled'] ?? false,
            'jit_enabled' => isset($lastMetrics['jit']['enabled']) ? $lastMetrics['jit']['enabled'] : false,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }

    /**
     * Calcula mediana
     */
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }
        
        return $values[intval($count / 2)];
    }

    /**
     * Exporta métricas brutas para análise externa
     */
    public function exportRawMetrics(): array
    {
        return $this->systemMetrics;
    }

    /**
     * Coleta métricas em loop contínuo (para uso assíncrono)
     */
    public function collectContinuously(int $intervalMs = 100): \Generator
    {
        $this->collectionInterval = $intervalMs;
        
        while ($this->isCollecting) {
            yield $this->collectSnapshot();
            usleep($intervalMs * 1000); // Convert to microseconds
        }
    }
}