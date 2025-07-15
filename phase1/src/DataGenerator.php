<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase1;

/**
 * Gerador de dados JSON determinístico para benchmarks científicos
 * 
 * Características:
 * - Reprodutibilidade total com seeds fixos
 * - Tamanhos controláveis e precisos
 * - Estruturas complexas variadas
 * - Hash de verificação integrado
 */
class DataGenerator
{
    private array $config;
    private array $seeds;

    public function __construct(array $config, array $seeds)
    {
        $this->config = $config;
        $this->seeds = $seeds;
    }

    /**
     * Gera JSON determinístico baseado no cenário
     */
    public function generateJson(string $scenario): array
    {
        if (!isset($this->config['json_scenarios'][$scenario])) {
            throw new \InvalidArgumentException("Cenário '$scenario' não encontrado");
        }

        $scenarioConfig = $this->config['json_scenarios'][$scenario];
        $seed = $this->seeds['scenario_seeds'][$scenario . '_json']['structure'] ?? 42;
        
        // Configurar seed determinístico
        mt_srand($seed);
        
        return [
            'data' => $this->generateDataStructure($scenarioConfig),
            'metadata' => $this->generateMetadata($scenario, $seed),
            'integrity' => $this->generateIntegrityData()
        ];
    }

    /**
     * Gera estrutura de dados baseada na configuração do cenário
     */
    private function generateDataStructure(array $config): array
    {
        $targetSize = $config['target_size_bytes'];
        $elementsRange = $config['elements_range'];
        $depthRange = $config['depth_range'];

        if ($targetSize === 'random') {
            $targetSize = mt_rand(100, 100000);
        }

        $structure = [];
        $currentSize = 0;
        $maxElements = mt_rand($elementsRange[0], $elementsRange[1]);
        $maxDepth = mt_rand($depthRange[0], $depthRange[1]);

        while ($currentSize < $targetSize && count($structure) < $maxElements) {
            $element = $this->generateElement($maxDepth);
            $structure['item_' . count($structure)] = $element;
            $currentSize = strlen(json_encode($structure));
        }

        // Adicionar dados de preenchimento se necessário
        if ($currentSize < $targetSize) {
            $padding = str_repeat('x', $targetSize - $currentSize - 50); // -50 para overhead JSON
            $structure['_padding'] = $padding;
        }

        return $structure;
    }

    /**
     * Gera elemento individual com profundidade controlada
     */
    private function generateElement(int $maxDepth, int $currentDepth = 0): mixed
    {
        if ($currentDepth >= $maxDepth) {
            return $this->generateScalar();
        }

        $elementType = mt_rand(1, 100);

        return match (true) {
            $elementType <= 30 => $this->generateScalar(),
            $elementType <= 60 => $this->generateArray($maxDepth, $currentDepth + 1),
            default => $this->generateObject($maxDepth, $currentDepth + 1)
        };
    }

    /**
     * Gera valor scalar aleatório
     */
    private function generateScalar(): mixed
    {
        $type = mt_rand(1, 100);

        return match (true) {
            $type <= 25 => $this->generateString(),
            $type <= 50 => mt_rand(-1000000, 1000000),
            $type <= 75 => mt_rand(0, 999999) / 100,
            $type <= 90 => (bool) mt_rand(0, 1),
            default => null
        };
    }

    /**
     * Gera string aleatória com tamanho variável
     */
    private function generateString(int $minLength = 5, int $maxLength = 100): string
    {
        $length = mt_rand($minLength, $maxLength);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .-_';
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $string;
    }

    /**
     * Gera array com elementos aleatórios
     */
    private function generateArray(int $maxDepth, int $currentDepth): array
    {
        $size = mt_rand(1, 20);
        $array = [];

        for ($i = 0; $i < $size; $i++) {
            $array[] = $this->generateElement($maxDepth, $currentDepth);
        }

        return $array;
    }

    /**
     * Gera objeto com propriedades aleatórias
     */
    private function generateObject(int $maxDepth, int $currentDepth): array
    {
        $size = mt_rand(1, 10);
        $object = [];

        for ($i = 0; $i < $size; $i++) {
            $key = 'prop_' . $i . '_' . $this->generateString(3, 10);
            $object[$key] = $this->generateElement($maxDepth, $currentDepth);
        }

        return $object;
    }

    /**
     * Gera metadata do JSON para rastreabilidade
     */
    private function generateMetadata(string $scenario, int $seed): array
    {
        // Usar timestamp determinístico baseado no seed para reprodutibilidade
        $deterministicTimestamp = 1720000000.0 + ($seed % 86400);
        
        return [
            'scenario' => $scenario,
            'seed' => $seed,
            'generated_at' => $deterministicTimestamp,
            'php_version' => PHP_VERSION,
            'generator_version' => '1.0.0',
            'size_bytes' => 0, // Será calculado após serialização
            'hash_md5' => '',  // Será calculado após serialização
            'hash_sha256' => '' // Será calculado após serialização
        ];
    }

    /**
     * Gera dados para verificação de integridade
     */
    private function generateIntegrityData(): array
    {
        // Usar timestamp determinístico para reprodutibilidade
        $timestamp = 1720000000.0 + (mt_rand(0, 86399) % 86400);
        $randomData = mt_rand(100000, 999999);
        
        return [
            'timestamp' => $timestamp,
            'random_marker' => $randomData,
            'checksum' => hash('crc32', $timestamp . $randomData),
            'validation_string' => 'INTEGRITY_CHECK_' . $randomData
        ];
    }

    /**
     * Finaliza JSON com hashes de verificação
     */
    public function finalizeJson(array $jsonData): array
    {
        $serialized = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
        
        $jsonData['metadata']['size_bytes'] = strlen($serialized);
        $jsonData['metadata']['hash_md5'] = md5($serialized);
        $jsonData['metadata']['hash_sha256'] = hash('sha256', $serialized);
        
        return $jsonData;
    }

    /**
     * Gera conjunto de JSONs para um cenário completo
     */
    public function generateScenarioSet(string $scenario, int $count = 100): array
    {
        $set = [];
        $baseSeed = $this->seeds['scenario_seeds'][$scenario . '_json']['sequence'] ?? 1000;
        
        for ($i = 0; $i < $count; $i++) {
            // Usar seed incremental para variação controlada
            $this->seeds['scenario_seeds'][$scenario . '_json']['structure'] = $baseSeed + $i;
            
            $json = $this->generateJson($scenario);
            $json = $this->finalizeJson($json);
            $json['_sequence_number'] = $i;
            
            $set[] = $json;
        }
        
        return $set;
    }

    /**
     * Valida reprodutibilidade gerando o mesmo JSON duas vezes
     */
    public function validateReproducibility(string $scenario): bool
    {
        $json1 = $this->generateJson($scenario);
        $json1 = $this->finalizeJson($json1);
        
        $json2 = $this->generateJson($scenario);
        $json2 = $this->finalizeJson($json2);
        
        return $json1['metadata']['hash_sha256'] === $json2['metadata']['hash_sha256'];
    }
}