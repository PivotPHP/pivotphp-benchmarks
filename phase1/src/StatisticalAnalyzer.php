<?php

declare(strict_types=1);

namespace PivotPHP\Benchmark\Phase1;

/**
 * Analisador estatístico para benchmarks científicos
 * 
 * Características:
 * - Análise estatística rigorosa (média, mediana, percentis, CV)
 * - Detecção de outliers e anomalias
 * - Teste de normalidade e distribuição
 * - Análise de tendências temporais
 * - Validação de significância estatística
 * - Geração de relatórios científicos
 */
class StatisticalAnalyzer
{
    private array $config;
    private array $analysisResults = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Executa análise estatística completa de um dataset
     */
    public function analyzeDataset(array $data, string $metric = 'response_time_ms'): array
    {
        if (empty($data)) {
            return ['error' => 'Dataset vazio'];
        }

        $values = $this->extractMetricValues($data, $metric);
        if (empty($values)) {
            return ['error' => "Métrica '$metric' não encontrada nos dados"];
        }

        $analysis = [
            'dataset_info' => $this->getDatasetInfo($data, $metric),
            'descriptive_statistics' => $this->calculateDescriptiveStatistics($values),
            'distribution_analysis' => $this->analyzeDistribution($values),
            'outlier_analysis' => $this->detectOutliers($values),
            'trend_analysis' => $this->analyzeTrends($values),
            'quality_assessment' => $this->assessDataQuality($values),
            'reproducibility' => $this->assessReproducibility($values)
        ];

        $this->analysisResults[] = $analysis;
        return $analysis;
    }

    /**
     * Extrai valores de uma métrica específica do dataset
     */
    private function extractMetricValues(array $data, string $metric): array
    {
        $values = [];
        
        foreach ($data as $item) {
            if (isset($item[$metric])) {
                $values[] = (float)$item[$metric];
            } elseif (isset($item['measurements'])) {
                // Se for resultado de benchmark com medições
                foreach ($item['measurements'] as $measurement) {
                    if (isset($measurement[$metric])) {
                        $values[] = (float)$measurement[$metric];
                    }
                }
            }
        }
        
        return array_filter($values, 'is_finite'); // Remove NaN e Inf
    }

    /**
     * Obtém informações básicas do dataset
     */
    private function getDatasetInfo(array $data, string $metric): array
    {
        return [
            'total_records' => count($data),
            'metric_analyzed' => $metric,
            'analysis_timestamp' => microtime(true),
            'data_collection_period' => $this->calculateCollectionPeriod($data)
        ];
    }

    /**
     * Calcula período de coleta dos dados
     */
    private function calculateCollectionPeriod(array $data): ?array
    {
        $timestamps = [];
        
        foreach ($data as $item) {
            if (isset($item['timestamp'])) {
                $timestamps[] = $item['timestamp'];
            }
        }
        
        if (empty($timestamps)) {
            return null;
        }
        
        sort($timestamps);
        return [
            'start_time' => min($timestamps),
            'end_time' => max($timestamps),
            'duration_seconds' => max($timestamps) - min($timestamps)
        ];
    }

    /**
     * Calcula estatísticas descritivas completas
     */
    private function calculateDescriptiveStatistics(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $n = count($values);

        return [
            'count' => $n,
            'mean' => $this->mean($values),
            'median' => $this->median($values),
            'mode' => $this->mode($values),
            'minimum' => min($values),
            'maximum' => max($values),
            'range' => max($values) - min($values),
            'variance' => $this->variance($values),
            'standard_deviation' => $this->standardDeviation($values),
            'coefficient_of_variation' => $this->coefficientOfVariation($values),
            'skewness' => $this->skewness($values),
            'kurtosis' => $this->kurtosis($values),
            'percentiles' => $this->calculatePercentiles($values, [1, 5, 10, 25, 50, 75, 90, 95, 99]),
            'quartiles' => [
                'q1' => $this->percentile($values, 25),
                'q2' => $this->percentile($values, 50),
                'q3' => $this->percentile($values, 75),
                'iqr' => $this->percentile($values, 75) - $this->percentile($values, 25)
            ]
        ];
    }

    /**
     * Analisa distribuição dos dados
     */
    private function analyzeDistribution(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        return [
            'normality_test' => $this->testNormality($values),
            'distribution_shape' => $this->analyzeShape($values),
            'histogram_data' => $this->generateHistogram($values),
            'density_estimation' => $this->estimateDensity($values)
        ];
    }

    /**
     * Testa normalidade da distribuição (Shapiro-Wilk simplificado)
     */
    private function testNormality(array $values): array
    {
        $n = count($values);
        if ($n < 3) {
            return ['test' => 'shapiro_wilk', 'result' => 'insufficient_data'];
        }

        // Implementação simplificada do teste de normalidade
        $mean = $this->mean($values);
        $std = $this->standardDeviation($values);
        
        // Calcular desvios padronizados
        $standardized = array_map(function($x) use ($mean, $std) {
            return $std > 0 ? ($x - $mean) / $std : 0;
        }, $values);

        // Teste simplificado: verificar se ~68% dos dados estão dentro de 1 desvio padrão
        $within_1_std = 0;
        foreach ($standardized as $z) {
            if (abs($z) <= 1) {
                $within_1_std++;
            }
        }

        $percentage_within_1_std = ($within_1_std / $n) * 100;
        $is_approximately_normal = $percentage_within_1_std >= 60 && $percentage_within_1_std <= 76;

        return [
            'test' => 'simplified_normality',
            'percentage_within_1_std' => $percentage_within_1_std,
            'expected_normal_range' => '68%',
            'is_approximately_normal' => $is_approximately_normal,
            'confidence' => abs($percentage_within_1_std - 68) <= 8 ? 'high' : 'low'
        ];
    }

    /**
     * Analisa forma da distribuição
     */
    private function analyzeShape(array $values): array
    {
        $skew = $this->skewness($values);
        $kurt = $this->kurtosis($values);

        return [
            'skewness' => $skew,
            'skewness_interpretation' => $this->interpretSkewness($skew),
            'kurtosis' => $kurt,
            'kurtosis_interpretation' => $this->interpretKurtosis($kurt),
            'distribution_type' => $this->classifyDistribution($skew, $kurt)
        ];
    }

    /**
     * Interpreta valor de skewness
     */
    private function interpretSkewness(float $skewness): string
    {
        if (abs($skewness) < 0.5) {
            return 'approximately_symmetric';
        } elseif ($skewness > 0.5) {
            return 'right_skewed_positive';
        } else {
            return 'left_skewed_negative';
        }
    }

    /**
     * Interpreta valor de kurtosis
     */
    private function interpretKurtosis(float $kurtosis): string
    {
        if ($kurtosis > 3) {
            return 'leptokurtic_heavy_tails';
        } elseif ($kurtosis < 3) {
            return 'platykurtic_light_tails';
        } else {
            return 'mesokurtic_normal';
        }
    }

    /**
     * Classifica tipo de distribuição
     */
    private function classifyDistribution(float $skewness, float $kurtosis): string
    {
        if (abs($skewness) < 0.5 && abs($kurtosis - 3) < 0.5) {
            return 'approximately_normal';
        } elseif ($skewness > 1) {
            return 'exponential_like';
        } elseif ($kurtosis > 5) {
            return 'heavy_tailed';
        } else {
            return 'non_normal';
        }
    }

    /**
     * Gera dados de histograma
     */
    private function generateHistogram(array $values, int $bins = 20): array
    {
        if (empty($values)) {
            return [];
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;
        $binWidth = $range / $bins;

        $histogram = array_fill(0, $bins, 0);
        $binEdges = [];

        for ($i = 0; $i <= $bins; $i++) {
            $binEdges[] = $min + ($i * $binWidth);
        }

        foreach ($values as $value) {
            $binIndex = min(intval(($value - $min) / $binWidth), $bins - 1);
            $histogram[$binIndex]++;
        }

        return [
            'bins' => $bins,
            'bin_edges' => $binEdges,
            'frequencies' => $histogram,
            'bin_width' => $binWidth
        ];
    }

    /**
     * Estima densidade de probabilidade
     */
    private function estimateDensity(array $values): array
    {
        $histogram = $this->generateHistogram($values);
        $total = array_sum($histogram['frequencies']);
        
        $densities = array_map(function($freq) use ($total, $histogram) {
            return $total > 0 ? $freq / ($total * $histogram['bin_width']) : 0;
        }, $histogram['frequencies']);

        return [
            'bin_centers' => $this->calculateBinCenters($histogram['bin_edges']),
            'densities' => $densities,
            'total_area' => array_sum($densities) * $histogram['bin_width']
        ];
    }

    /**
     * Calcula centros dos bins
     */
    private function calculateBinCenters(array $binEdges): array
    {
        $centers = [];
        for ($i = 0; $i < count($binEdges) - 1; $i++) {
            $centers[] = ($binEdges[$i] + $binEdges[$i + 1]) / 2;
        }
        return $centers;
    }

    /**
     * Detecta outliers usando múltiplos métodos
     */
    private function detectOutliers(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        return [
            'iqr_method' => $this->detectOutliersIQR($values),
            'z_score_method' => $this->detectOutliersZScore($values),
            'modified_z_score' => $this->detectOutliersModifiedZScore($values),
            'summary' => $this->summarizeOutliers($values)
        ];
    }

    /**
     * Detecta outliers usando método IQR
     */
    private function detectOutliersIQR(array $values): array
    {
        sort($values);
        $q1 = $this->percentile($values, 25);
        $q3 = $this->percentile($values, 75);
        $iqr = $q3 - $q1;
        
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        $outliers = array_filter($values, function($value) use ($lowerBound, $upperBound) {
            return $value < $lowerBound || $value > $upperBound;
        });

        return [
            'method' => 'IQR',
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
            'outliers' => array_values($outliers),
            'outlier_count' => count($outliers),
            'outlier_percentage' => (count($outliers) / count($values)) * 100
        ];
    }

    /**
     * Detecta outliers usando Z-score
     */
    private function detectOutliersZScore(array $values, float $threshold = 3.0): array
    {
        $mean = $this->mean($values);
        $std = $this->standardDeviation($values);
        
        if ($std == 0) {
            return ['method' => 'Z-Score', 'outliers' => [], 'outlier_count' => 0];
        }

        $outliers = [];
        foreach ($values as $value) {
            $zScore = abs(($value - $mean) / $std);
            if ($zScore > $threshold) {
                $outliers[] = $value;
            }
        }

        return [
            'method' => 'Z-Score',
            'threshold' => $threshold,
            'outliers' => $outliers,
            'outlier_count' => count($outliers),
            'outlier_percentage' => (count($outliers) / count($values)) * 100
        ];
    }

    /**
     * Detecta outliers usando Modified Z-score (baseado na mediana)
     */
    private function detectOutliersModifiedZScore(array $values, float $threshold = 3.5): array
    {
        $median = $this->median($values);
        $mad = $this->medianAbsoluteDeviation($values);
        
        if ($mad == 0) {
            return ['method' => 'Modified Z-Score', 'outliers' => [], 'outlier_count' => 0];
        }

        $outliers = [];
        foreach ($values as $value) {
            $modifiedZScore = 0.6745 * abs(($value - $median) / $mad);
            if ($modifiedZScore > $threshold) {
                $outliers[] = $value;
            }
        }

        return [
            'method' => 'Modified Z-Score',
            'threshold' => $threshold,
            'outliers' => $outliers,
            'outlier_count' => count($outliers),
            'outlier_percentage' => (count($outliers) / count($values)) * 100
        ];
    }

    /**
     * Resume análise de outliers
     */
    private function summarizeOutliers(array $values): array
    {
        $iqr = $this->detectOutliersIQR($values);
        $zscore = $this->detectOutliersZScore($values);
        $modified = $this->detectOutliersModifiedZScore($values);

        $allOutliers = array_unique(array_merge($iqr['outliers'], $zscore['outliers'], $modified['outliers']));
        
        return [
            'consensus_outliers' => array_values($allOutliers),
            'consensus_count' => count($allOutliers),
            'consensus_percentage' => (count($allOutliers) / count($values)) * 100,
            'data_quality' => count($allOutliers) / count($values) < 0.05 ? 'good' : 'poor'
        ];
    }

    /**
     * Analisa tendências temporais
     */
    private function analyzeTrends(array $values): array
    {
        if (count($values) < 3) {
            return ['error' => 'Dados insuficientes para análise de tendência'];
        }

        return [
            'linear_trend' => $this->calculateLinearTrend($values),
            'moving_average' => $this->calculateMovingAverage($values),
            'trend_direction' => $this->determineTrendDirection($values),
            'volatility' => $this->calculateVolatility($values)
        ];
    }

    /**
     * Calcula tendência linear usando regressão simples
     */
    private function calculateLinearTrend(array $values): array
    {
        $n = count($values);
        $x = range(1, $n); // Índices como variável independente
        
        $meanX = $this->mean($x);
        $meanY = $this->mean($values);
        
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $numerator += ($x[$i] - $meanX) * ($values[$i] - $meanY);
            $denominator += pow($x[$i] - $meanX, 2);
        }
        
        $slope = $denominator != 0 ? $numerator / $denominator : 0;
        $intercept = $meanY - ($slope * $meanX);
        
        // Calcular R²
        $totalSumSquares = 0;
        $residualSumSquares = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $predicted = $intercept + ($slope * $x[$i]);
            $totalSumSquares += pow($values[$i] - $meanY, 2);
            $residualSumSquares += pow($values[$i] - $predicted, 2);
        }
        
        $rSquared = $totalSumSquares != 0 ? 1 - ($residualSumSquares / $totalSumSquares) : 0;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => $rSquared,
            'trend_strength' => abs($rSquared) > 0.7 ? 'strong' : (abs($rSquared) > 0.3 ? 'moderate' : 'weak')
        ];
    }

    /**
     * Calcula média móvel
     */
    private function calculateMovingAverage(array $values, int $window = 5): array
    {
        $movingAvg = [];
        $n = count($values);
        
        for ($i = 0; $i < $n; $i++) {
            $start = max(0, $i - intval($window / 2));
            $end = min($n - 1, $i + intval($window / 2));
            
            $windowValues = array_slice($values, $start, $end - $start + 1);
            $movingAvg[] = $this->mean($windowValues);
        }
        
        return [
            'window_size' => $window,
            'values' => $movingAvg,
            'smoothness_improvement' => $this->calculateSmoothness($values) - $this->calculateSmoothness($movingAvg)
        ];
    }

    /**
     * Determina direção da tendência
     */
    private function determineTrendDirection(array $values): string
    {
        $trend = $this->calculateLinearTrend($values);
        
        if (abs($trend['slope']) < 0.01) {
            return 'stable';
        } elseif ($trend['slope'] > 0) {
            return 'increasing';
        } else {
            return 'decreasing';
        }
    }

    /**
     * Calcula volatilidade (variabilidade)
     */
    private function calculateVolatility(array $values): array
    {
        if (count($values) < 2) {
            return ['error' => 'Dados insuficientes'];
        }

        $returns = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] != 0) {
                $returns[] = ($values[$i] - $values[$i - 1]) / $values[$i - 1];
            }
        }

        return [
            'relative_volatility' => empty($returns) ? 0 : $this->standardDeviation($returns),
            'absolute_volatility' => $this->standardDeviation($values),
            'volatility_level' => $this->classifyVolatility($values)
        ];
    }

    /**
     * Classifica nível de volatilidade
     */
    private function classifyVolatility(array $values): string
    {
        $cv = $this->coefficientOfVariation($values);
        
        if ($cv < 5) {
            return 'very_low';
        } elseif ($cv < 15) {
            return 'low';
        } elseif ($cv < 30) {
            return 'moderate';
        } else {
            return 'high';
        }
    }

    /**
     * Calcula suavidade de uma série
     */
    private function calculateSmoothness(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $differences = [];
        for ($i = 1; $i < count($values); $i++) {
            $differences[] = abs($values[$i] - $values[$i - 1]);
        }

        return $this->mean($differences);
    }

    /**
     * Avalia qualidade dos dados
     */
    private function assessDataQuality(array $values): array
    {
        $cv = $this->coefficientOfVariation($values);
        $outlierAnalysis = $this->detectOutliersIQR($values);
        
        return [
            'coefficient_of_variation' => $cv,
            'cv_quality' => $cv < 5 ? 'excellent' : ($cv < 15 ? 'good' : ($cv < 30 ? 'fair' : 'poor')),
            'outlier_percentage' => $outlierAnalysis['outlier_percentage'],
            'outlier_quality' => $outlierAnalysis['outlier_percentage'] < 5 ? 'excellent' : 'needs_attention',
            'sample_size' => count($values),
            'sample_size_quality' => count($values) >= 100 ? 'adequate' : 'small',
            'overall_quality' => $this->calculateOverallQuality($cv, $outlierAnalysis['outlier_percentage'], count($values))
        ];
    }

    /**
     * Calcula qualidade geral dos dados
     */
    private function calculateOverallQuality(float $cv, float $outlierPercentage, int $sampleSize): string
    {
        $score = 0;
        
        // CV score
        if ($cv < 5) $score += 3;
        elseif ($cv < 15) $score += 2;
        elseif ($cv < 30) $score += 1;
        
        // Outlier score
        if ($outlierPercentage < 2) $score += 3;
        elseif ($outlierPercentage < 5) $score += 2;
        elseif ($outlierPercentage < 10) $score += 1;
        
        // Sample size score
        if ($sampleSize >= 1000) $score += 3;
        elseif ($sampleSize >= 100) $score += 2;
        elseif ($sampleSize >= 30) $score += 1;
        
        return match (true) {
            $score >= 8 => 'excellent',
            $score >= 6 => 'good',
            $score >= 4 => 'fair',
            default => 'poor'
        };
    }

    /**
     * Avalia reprodutibilidade dos dados
     */
    private function assessReproducibility(array $values): array
    {
        $maxAllowedCV = $this->config['benchmark_parameters']['max_variance_percent'] ?? 5.0;
        $cv = $this->coefficientOfVariation($values);
        
        return [
            'coefficient_of_variation' => $cv,
            'max_allowed_cv' => $maxAllowedCV,
            'is_reproducible' => $cv <= $maxAllowedCV,
            'reproducibility_score' => max(0, 100 - ($cv / $maxAllowedCV * 100)),
            'confidence_level' => $cv <= $maxAllowedCV * 0.5 ? 'high' : ($cv <= $maxAllowedCV ? 'medium' : 'low'),
            'scientific_validity' => $cv <= $maxAllowedCV ? 'valid' : 'questionable'
        ];
    }

    // Métodos estatísticos básicos

    private function mean(array $values): float
    {
        return count($values) > 0 ? array_sum($values) / count($values) : 0;
    }

    private function median(array $values): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }
        
        return $values[intval($count / 2)];
    }

    private function mode(array $values): array
    {
        if (empty($values)) return [];
        
        $frequency = array_count_values($values);
        $maxFreq = max($frequency);
        
        return array_keys($frequency, $maxFreq);
    }

    private function variance(array $values): float
    {
        if (count($values) < 2) return 0;
        
        $mean = $this->mean($values);
        $sumSquaredDiffs = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values));
        
        return $sumSquaredDiffs / (count($values) - 1);
    }

    private function standardDeviation(array $values): float
    {
        return sqrt($this->variance($values));
    }

    private function coefficientOfVariation(array $values): float
    {
        $mean = $this->mean($values);
        return $mean != 0 ? ($this->standardDeviation($values) / $mean) * 100 : 0;
    }

    private function skewness(array $values): float
    {
        $n = count($values);
        if ($n < 3) return 0;
        
        $mean = $this->mean($values);
        $std = $this->standardDeviation($values);
        
        if ($std == 0) return 0;
        
        $sum = array_sum(array_map(fn($x) => pow(($x - $mean) / $std, 3), $values));
        
        return ($n / (($n - 1) * ($n - 2))) * $sum;
    }

    private function kurtosis(array $values): float
    {
        $n = count($values);
        if ($n < 4) return 0;
        
        $mean = $this->mean($values);
        $std = $this->standardDeviation($values);
        
        if ($std == 0) return 0;
        
        $sum = array_sum(array_map(fn($x) => pow(($x - $mean) / $std, 4), $values));
        
        return (($n * ($n + 1)) / (($n - 1) * ($n - 2) * ($n - 3))) * $sum - 
               (3 * pow($n - 1, 2)) / (($n - 2) * ($n - 3));
    }

    private function percentile(array $values, float $percentile): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $values[$lower];
        }
        
        return $values[$lower] + ($index - $lower) * ($values[$upper] - $values[$lower]);
    }

    private function calculatePercentiles(array $values, array $percentiles): array
    {
        $result = [];
        foreach ($percentiles as $p) {
            $result["p$p"] = $this->percentile($values, $p);
        }
        return $result;
    }

    private function medianAbsoluteDeviation(array $values): float
    {
        $median = $this->median($values);
        $deviations = array_map(fn($x) => abs($x - $median), $values);
        return $this->median($deviations);
    }

    /**
     * Gera relatório estatístico completo
     */
    public function generateComprehensiveReport(array $benchmarkResults): array
    {
        $report = [
            'report_metadata' => [
                'generated_at' => date('c'),
                'analyzer_version' => '1.0.0',
                'total_datasets' => count($benchmarkResults)
            ],
            'analyses' => []
        ];

        foreach ($benchmarkResults as $index => $result) {
            $analysis = $this->analyzeDataset($result['measurements'] ?? [], 'response_time_ms');
            $analysis['dataset_index'] = $index;
            $analysis['endpoint'] = $result['endpoint'] ?? 'unknown';
            
            $report['analyses'][] = $analysis;
        }

        $report['summary'] = $this->summarizeAllAnalyses($report['analyses']);
        
        return $report;
    }

    /**
     * Resume todas as análises
     */
    private function summarizeAllAnalyses(array $analyses): array
    {
        if (empty($analyses)) {
            return [];
        }

        $cvValues = [];
        $qualityScores = [];
        
        foreach ($analyses as $analysis) {
            if (isset($analysis['reproducibility']['coefficient_of_variation'])) {
                $cvValues[] = $analysis['reproducibility']['coefficient_of_variation'];
            }
            if (isset($analysis['quality_assessment']['overall_quality'])) {
                $qualityScores[] = $analysis['quality_assessment']['overall_quality'];
            }
        }

        return [
            'overall_reproducibility' => [
                'mean_cv' => $this->mean($cvValues),
                'max_cv' => max($cvValues),
                'datasets_within_tolerance' => count(array_filter($cvValues, fn($cv) => $cv <= 5.0)),
                'reproducibility_rate' => count(array_filter($cvValues, fn($cv) => $cv <= 5.0)) / count($cvValues) * 100
            ],
            'quality_distribution' => array_count_values($qualityScores),
            'recommendation' => $this->generateRecommendation($cvValues, $qualityScores)
        ];
    }

    /**
     * Gera recomendação baseada na análise
     */
    private function generateRecommendation(array $cvValues, array $qualityScores): string
    {
        $meanCV = $this->mean($cvValues);
        $excellentQuality = count(array_filter($qualityScores, fn($q) => $q === 'excellent'));
        $totalDatasets = count($qualityScores);

        if ($meanCV <= 2.0 && $excellentQuality / $totalDatasets >= 0.8) {
            return 'Excelente qualidade científica. Resultados altamente confiáveis e reproduzíveis.';
        } elseif ($meanCV <= 5.0 && $excellentQuality / $totalDatasets >= 0.6) {
            return 'Boa qualidade científica. Resultados confiáveis com reprodutibilidade adequada.';
        } elseif ($meanCV <= 10.0) {
            return 'Qualidade moderada. Considere aumentar o número de amostras ou revisar metodologia.';
        } else {
            return 'Qualidade insuficiente. Revise completamente a metodologia e ambiente de teste.';
        }
    }

    /**
     * Exporta resultados de análise
     */
    public function exportAnalysisResults(): array
    {
        return $this->analysisResults;
    }
}