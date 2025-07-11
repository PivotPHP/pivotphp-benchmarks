<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Request;

class HealthController
{
    public function index(Request $request, Response $response): void
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'uptime' => $this->getUptime(),
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->getMemoryLimit(),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => php_sapi_name(),
            ],
            'system' => [
                'os' => PHP_OS,
                'hostname' => gethostname(),
                'load_average' => sys_getloadavg(),
            ],
        ];
        
        $response->json($health);
    }

    private function getUptime(): string
    {
        static $startTime = null;
        
        if ($startTime === null) {
            $startTime = time();
        }
        
        $uptime = time() - $startTime;
        
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        $seconds = $uptime % 60;
        
        return sprintf('%dd %dh %dm %ds', $days, $hours, $minutes, $seconds);
    }

    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        
        if ($limit === '-1') {
            return -1;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}