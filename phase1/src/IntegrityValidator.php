<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase1;

/**
 * Sistema de validação de integridade para benchmarks científicos
 * 
 * Características:
 * - Zero tolerância para dados corrompidos
 * - Validação byte-a-byte entre enviado e recebido
 * - Verificação de hash múltiplos (MD5, SHA256)
 * - Validação estrutural de JSON
 * - Detecção de truncamento e corrupção
 */
class IntegrityValidator
{
    private array $config;
    private array $validationLog = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Valida integridade completa entre dados enviados e recebidos
     */
    public function validateIntegrity(array $sentData, string $receivedJson): array
    {
        $result = [
            'is_valid' => true,
            'errors' => [],
            'validations' => [],
            'metrics' => []
        ];

        // 1. Validar se é JSON válido
        $receivedData = $this->validateJsonStructure($receivedJson, $result);
        if (!$receivedData) {
            return $result;
        }

        // 2. Validar integridade de hash
        $this->validateHashes($sentData, $receivedJson, $result);

        // 3. Validar completude dos dados
        $this->validateCompleteness($sentData, $receivedData, $result);

        // 4. Validar estrutura esperada
        $this->validateStructure($sentData, $receivedData, $result);

        // 5. Validar dados de integridade internos
        $this->validateIntegrityMarkers($receivedData, $result);

        // 6. Validar tamanho
        $this->validateSize($sentData, $receivedJson, $result);

        // Log da validação
        $this->logValidation($result);

        return $result;
    }

    /**
     * Valida se o JSON recebido é estruturalmente válido
     */
    private function validateJsonStructure(string $json, array &$result): ?array
    {
        $data = json_decode($json, true);
        $error = json_last_error();

        if ($error !== JSON_ERROR_NONE) {
            $result['is_valid'] = false;
            $result['errors'][] = [
                'type' => 'json_structure',
                'message' => 'JSON inválido: ' . json_last_error_msg(),
                'code' => $error
            ];
            return null;
        }

        $result['validations'][] = [
            'type' => 'json_structure',
            'status' => 'passed',
            'message' => 'JSON estruturalmente válido'
        ];

        return $data;
    }

    /**
     * Valida hashes MD5 e SHA256
     */
    private function validateHashes(array $sentData, string $receivedJson, array &$result): void
    {
        if (!$this->config['validation']['hash_algorithm']) {
            return;
        }

        $algorithms = ['md5', 'sha256'];
        
        foreach ($algorithms as $algo) {
            $expectedHash = $sentData['metadata']['hash_' . $algo] ?? null;
            if (!$expectedHash) {
                continue;
            }

            $actualHash = $algo === 'md5' ? md5($receivedJson) : hash('sha256', $receivedJson);

            if ($expectedHash === $actualHash) {
                $result['validations'][] = [
                    'type' => 'hash_' . $algo,
                    'status' => 'passed',
                    'expected' => $expectedHash,
                    'actual' => $actualHash
                ];
            } else {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'hash_' . $algo,
                    'message' => "Hash $algo não coincide",
                    'expected' => $expectedHash,
                    'actual' => $actualHash
                ];
            }
        }
    }

    /**
     * Valida completude dos dados (todas as chaves presentes)
     */
    private function validateCompleteness(array $sentData, array $receivedData, array &$result): void
    {
        $missing = $this->findMissingKeys($sentData, $receivedData);
        $extra = $this->findExtraKeys($sentData, $receivedData);

        if (empty($missing) && empty($extra)) {
            $result['validations'][] = [
                'type' => 'completeness',
                'status' => 'passed',
                'message' => 'Todos os dados presentes e sem extras'
            ];
        } else {
            $result['is_valid'] = false;
            if (!empty($missing)) {
                $result['errors'][] = [
                    'type' => 'missing_data',
                    'message' => 'Dados faltando',
                    'missing_keys' => $missing
                ];
            }
            if (!empty($extra)) {
                $result['errors'][] = [
                    'type' => 'extra_data',
                    'message' => 'Dados extras encontrados',
                    'extra_keys' => $extra
                ];
            }
        }
    }

    /**
     * Encontra chaves faltando recursivamente
     */
    private function findMissingKeys(array $expected, array $actual, string $path = ''): array
    {
        $missing = [];

        foreach ($expected as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;
            
            if (!array_key_exists($key, $actual)) {
                $missing[] = $currentPath;
            } elseif (is_array($value) && is_array($actual[$key])) {
                $missing = array_merge($missing, $this->findMissingKeys($value, $actual[$key], $currentPath));
            }
        }

        return $missing;
    }

    /**
     * Encontra chaves extras recursivamente
     */
    private function findExtraKeys(array $expected, array $actual, string $path = ''): array
    {
        $extra = [];

        foreach ($actual as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;
            
            if (!array_key_exists($key, $expected)) {
                $extra[] = $currentPath;
            } elseif (is_array($value) && isset($expected[$key]) && is_array($expected[$key])) {
                $extra = array_merge($extra, $this->findExtraKeys($expected[$key], $value, $currentPath));
            }
        }

        return $extra;
    }

    /**
     * Valida estrutura de dados criticamente
     */
    private function validateStructure(array $sentData, array $receivedData, array &$result): void
    {
        // Verificar se seções obrigatórias existem
        $requiredSections = ['data', 'metadata', 'integrity'];
        
        foreach ($requiredSections as $section) {
            if (!isset($receivedData[$section])) {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'structure',
                    'message' => "Seção obrigatória '$section' faltando"
                ];
            }
        }

        // Validar tipos de dados
        $this->validateDataTypes($sentData, $receivedData, $result);
    }

    /**
     * Valida tipos de dados recursivamente
     */
    private function validateDataTypes(array $expected, array $actual, array &$result, string $path = ''): void
    {
        foreach ($expected as $key => $expectedValue) {
            if (!array_key_exists($key, $actual)) {
                continue; // Já tratado em completeness
            }

            $actualValue = $actual[$key];
            $currentPath = $path ? "$path.$key" : $key;

            if (gettype($expectedValue) !== gettype($actualValue)) {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'data_type',
                    'message' => "Tipo incorreto em '$currentPath'",
                    'expected_type' => gettype($expectedValue),
                    'actual_type' => gettype($actualValue),
                    'path' => $currentPath
                ];
            } elseif (is_array($expectedValue) && is_array($actualValue)) {
                $this->validateDataTypes($expectedValue, $actualValue, $result, $currentPath);
            }
        }
    }

    /**
     * Valida marcadores internos de integridade
     */
    private function validateIntegrityMarkers(array $receivedData, array &$result): void
    {
        if (!isset($receivedData['integrity'])) {
            return;
        }

        $integrity = $receivedData['integrity'];
        
        // Validar timestamp
        if (isset($integrity['timestamp'])) {
            $timestamp = $integrity['timestamp'];
            if (!is_numeric($timestamp) || $timestamp <= 0) {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'integrity_timestamp',
                    'message' => 'Timestamp de integridade inválido',
                    'value' => $timestamp
                ];
            }
        }

        // Validar checksum
        if (isset($integrity['timestamp'], $integrity['random_marker'], $integrity['checksum'])) {
            $expectedChecksum = hash('crc32', $integrity['timestamp'] . $integrity['random_marker']);
            if ($integrity['checksum'] !== $expectedChecksum) {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'integrity_checksum',
                    'message' => 'Checksum de integridade não coincide',
                    'expected' => $expectedChecksum,
                    'actual' => $integrity['checksum']
                ];
            }
        }

        // Validar validation_string
        if (isset($integrity['validation_string'], $integrity['random_marker'])) {
            $expectedString = 'INTEGRITY_CHECK_' . $integrity['random_marker'];
            if ($integrity['validation_string'] !== $expectedString) {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'integrity_string',
                    'message' => 'String de validação não coincide',
                    'expected' => $expectedString,
                    'actual' => $integrity['validation_string']
                ];
            }
        }
    }

    /**
     * Valida tamanho dos dados
     */
    private function validateSize(array $sentData, string $receivedJson, array &$result): void
    {
        $expectedSize = $sentData['metadata']['size_bytes'] ?? null;
        $actualSize = strlen($receivedJson);

        if ($expectedSize !== null) {
            if ($expectedSize === $actualSize) {
                $result['validations'][] = [
                    'type' => 'size',
                    'status' => 'passed',
                    'expected_bytes' => $expectedSize,
                    'actual_bytes' => $actualSize
                ];
            } else {
                $result['is_valid'] = false;
                $result['errors'][] = [
                    'type' => 'size_mismatch',
                    'message' => 'Tamanho dos dados não coincide',
                    'expected_bytes' => $expectedSize,
                    'actual_bytes' => $actualSize,
                    'difference' => $actualSize - $expectedSize
                ];
            }
        }

        $result['metrics']['size_bytes'] = $actualSize;
    }

    /**
     * Log da validação para auditoria
     */
    private function logValidation(array $result): void
    {
        $logEntry = [
            'timestamp' => microtime(true),
            'validation_result' => $result,
            'php_memory_usage' => memory_get_usage(true),
            'php_peak_memory' => memory_get_peak_usage(true)
        ];

        $this->validationLog[] = $logEntry;
    }

    /**
     * Retorna estatísticas de validação
     */
    public function getValidationStats(): array
    {
        $total = count($this->validationLog);
        $valid = 0;
        $errorTypes = [];

        foreach ($this->validationLog as $entry) {
            if ($entry['validation_result']['is_valid']) {
                $valid++;
            } else {
                foreach ($entry['validation_result']['errors'] as $error) {
                    $errorTypes[$error['type']] = ($errorTypes[$error['type']] ?? 0) + 1;
                }
            }
        }

        return [
            'total_validations' => $total,
            'valid_count' => $valid,
            'invalid_count' => $total - $valid,
            'success_rate' => $total > 0 ? ($valid / $total) * 100 : 0,
            'error_types' => $errorTypes,
            'memory_usage' => [
                'average' => $total > 0 ? array_sum(array_column($this->validationLog, 'php_memory_usage')) / $total : 0,
                'peak' => max(array_column($this->validationLog, 'php_peak_memory'))
            ]
        ];
    }

    /**
     * Exporta log de validação para análise
     */
    public function exportValidationLog(): array
    {
        return $this->validationLog;
    }

    /**
     * Reset do log de validação
     */
    public function resetLog(): void
    {
        $this->validationLog = [];
    }
}