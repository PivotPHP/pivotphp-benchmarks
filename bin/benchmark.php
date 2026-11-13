#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PivotPHP Benchmarks — Runner CLI Unificado (SPEC-115)
 * Inspirado na arquitetura do fastify/benchmarks.
 *
 * Uso:
 *   php bin/benchmark.php list
 *   php bin/benchmark.php bench <framework> [scenario]
 *   php bin/benchmark.php compare [scenario]
 *   php bin/benchmark.php run-all
 *   php bin/benchmark.php update-readme
 */

$rootDir = dirname(__DIR__);
$frameworks = require $rootDir . '/config/frameworks.php';
$scenarios = require $rootDir . '/config/scenarios.php';

$argvCopy = $argv;
array_shift($argvCopy); // remove script name
$command = array_shift($argvCopy) ?? 'help';

switch ($command) {
    case 'list':
        commandList($frameworks, $scenarios);
        break;
    case 'bench':
        $fw = array_shift($argvCopy);
        $sc = array_shift($argvCopy) ?? 'bare-get';
        commandBench($fw, $sc, $frameworks, $scenarios, $rootDir);
        break;
    case 'compare':
        $sc = array_shift($argvCopy) ?? 'bare-get';
        commandCompare($sc, $frameworks, $scenarios, $rootDir);
        break;
    case 'run-all':
        commandRunAll($frameworks, $scenarios, $rootDir);
        break;
    case 'update-readme':
        commandUpdateReadme($rootDir);
        break;
    case 'help':
    default:
        commandHelp();
        break;
}

function commandHelp(): void
{
    echo <<<HELP
\033[1;36m===============================================================\033[0m
\033[1;37m  PivotPHP Benchmarks — Runner CLI (SPEC-115)\033[0m
\033[1;36m===============================================================\033[0m

\033[1;33mUso:\033[0m
  php bin/benchmark.php <comando> [argumentos] [opções]

\033[1;33mComandos Disponíveis:\033[0m
  \033[1;32mlist\033[0m                    Lista frameworks e cenários suportados
  \033[1;32mbench <fw> [cenário]\033[0m    Executa benchmark de um framework específico
  \033[1;32mcompare [cenário]\033[0m       Compara todos os frameworks em um cenário
  \033[1;32mrun-all\033[0m                 Executa a matriz completa (todos os frameworks x cenários)
  \033[1;32mupdate-readme\033[0m           Atualiza o README.md com o último relatório gerado
  \033[1;32mhelp\033[0m                    Exibe esta ajuda

\033[1;33mExemplos:\033[0m
  php bin/benchmark.php bench pivotphp bare-get
  php bin/benchmark.php bench slim params
  php bin/benchmark.php compare bare-get
  php bin/benchmark.php compare middleware

HELP;
}

function commandList(array $frameworks, array $scenarios): void
{
    echo "\n\033[1;37m>> Frameworks Disponíveis:\033[0m\n";
    foreach ($frameworks as $key => $fw) {
        printf("  - \033[1;32m%-10s\033[0m : %-15s (Porta: %d, Runtime: %s)\n", $key, $fw['name'], $fw['port'], $fw['runtime']);
    }

    echo "\n\033[1;37m>> Cenários de Teste (SPEC-115):\033[0m\n";
    foreach ($scenarios as $key => $sc) {
        printf("  - \033[1;33m%-12s\033[0m : %-35s [%s %s]\n", $key, $sc['name'], $sc['method'], $sc['path']);
        printf("                   \033[0;37m%s\033[0m\n", $sc['description']);
    }
    echo "\n";
}

function commandBench(?string $fw, string $scenarioKey, array $frameworks, array $scenarios, string $rootDir): void
{
    if (!$fw || !isset($frameworks[$fw])) {
        echo "\033[1;31mErro: Framework inválido ou não especificado.\033[0m Use 'php bin/benchmark.php list'.\n";
        exit(1);
    }

    if (!isset($scenarios[$scenarioKey])) {
        echo "\033[1;31mErro: Cenário inválido '{$scenarioKey}'.\033[0m Use 'php bin/benchmark.php list'.\n";
        exit(1);
    }

    $framework = $frameworks[$fw];
    $scenario = $scenarios[$scenarioKey];

    echo "\n\033[1;36m===============================================================\033[0m\n";
    printf("  Iniciando Benchmark: \033[1;32m%s\033[0m | Cenário: \033[1;33m%s\033[0m\n", $framework['name'], $scenario['name']);
    printf("  Rota: %s %s\n", $scenario['method'], $scenario['path']);
    echo "\033[1;36m===============================================================\033[0m\n\n";

    $resultsDir = $rootDir . '/results/cli_' . date('Ymd_His');
    if (!is_dir($resultsDir)) {
        mkdir($resultsDir, 0777, true);
    }

    $metrics = executeBenchmark($fw, $framework, $scenarioKey, $scenario, $resultsDir, $rootDir);

    printResultsTable($scenarioKey, [$fw => $metrics], $frameworks);
}

function commandCompare(string $scenarioKey, array $frameworks, array $scenarios, string $rootDir): void
{
    if (!isset($scenarios[$scenarioKey])) {
        echo "\033[1;31mErro: Cenário inválido '{$scenarioKey}'.\033[0m Use 'php bin/benchmark.php list'.\n";
        exit(1);
    }

    $scenario = $scenarios[$scenarioKey];
    $timestamp = date('Ymd_His');
    $resultsDir = $rootDir . '/results/compare_' . $scenarioKey . '_' . $timestamp;
    if (!is_dir($resultsDir)) {
        mkdir($resultsDir, 0777, true);
    }

    echo "\n\033[1;36m===============================================================\033[0m\n";
    printf("  Comparativo Geral (Fastify-style) — Cenário: \033[1;33m%s\033[0m\n", $scenario['name']);
    printf("  Rota: %s %s | VUs: %d | Duração: %ds (+ %ds warm-up)\n", $scenario['method'], $scenario['path'], $scenario['vus'], $scenario['duration'], $scenario['warmup']);
    echo "\033[1;36m===============================================================\033[0m\n\n";

    $collected = [];
    foreach ($frameworks as $fwKey => $fwData) {
        echo "\033[1;37m>> Executando [{$fwData['name']}]...\033[0m\n";
        $collected[$fwKey] = executeBenchmark($fwKey, $fwData, $scenarioKey, $scenario, $resultsDir, $rootDir);
        echo "\n";
    }

    printResultsTable($scenarioKey, $collected, $frameworks);

    // Salvar JSON consolidado
    file_put_contents(
        $resultsDir . '/comparison.json',
        json_encode(['scenario' => $scenarioKey, 'timestamp' => date('c'), 'results' => $collected], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    echo "\033[1;32m✓ Resultados salvos em: {$resultsDir}/comparison.json\033[0m\n\n";
}

function commandRunAll(array $frameworks, array $scenarios, string $rootDir): void
{
    foreach ($scenarios as $sKey => $sData) {
        if ($sKey === 'full-app') {
            continue; // full app é muito longo para rodar no lote rápido
        }
        commandCompare($sKey, $frameworks, $scenarios, $rootDir);
    }
}

function commandUpdateReadme(string $rootDir): void
{
    $resultsBase = $rootDir . '/results';
    if (!is_dir($resultsBase)) {
        echo "Nenhum resultado encontrado em results/.\n";
        return;
    }

    $files = glob($resultsBase . '/compare_*/comparison.json');
    if (empty($files)) {
        echo "Nenhum arquivo comparison.json encontrado em results/.\n";
        return;
    }

    // Ordena pelo mais recente
    usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

    $frameworks = require $rootDir . '/config/frameworks.php';
    $scenarios = require $rootDir . '/config/scenarios.php';

    // Agrupa todos os cenários mais recentes
    $latestByScenario = [];
    foreach ($files as $f) {
        $data = json_decode((string) file_get_contents($f), true);
        if (!$data || !isset($data['scenario'])) {
            continue;
        }
        $scKey = $data['scenario'];
        if (!isset($latestByScenario[$scKey])) {
            $latestByScenario[$scKey] = $data;
        }
    }

    if (empty($latestByScenario)) {
        echo "Nenhum dado válido de comparação encontrado.\n";
        return;
    }

    $md = "<!-- BENCHMARK-LEADERBOARD-START -->\n";
    $md .= "## Leaderboard — Resultados Oficiais (SPEC-115)\n\n";
    $md .= "> Resultados medidos em ambiente Docker isolado com limites estritos (1.0 vCPU / 512 MB RAM).\n\n";

    foreach ($latestByScenario as $scKey => $payload) {
        $scName = $scenarios[$scKey]['name'] ?? $scKey;
        $results = $payload['results'] ?? [];
        uasort($results, fn ($a, $b) => ($b['rps'] ?? 0) <=> ($a['rps'] ?? 0));

        $md .= "### Cenário: {$scName} (`{$scKey}`)\n\n";
        $md .= "| Pos | Framework | Runtime | Throughput (req/s) | Latência p50 | Latência p95 | Latência p99 | Erros | RAM RSS |\n";
        $md .= "|---|---|---|---|---|---|---|---|---|\n";

        $pos = 1;
        foreach ($results as $fwKey => $row) {
            $fwMeta = $frameworks[$fwKey] ?? [];
            $name = $fwMeta['name'] ?? $fwKey;
            $runtime = $fwMeta['runtime'] ?? 'PHP';
            $rps = number_format($row['rps'] ?? 0, 2, '.', ',');
            $p50 = $row['p50'] ?? '—';
            $p95 = $row['p95'] ?? '—';
            $p99 = $row['p99'] ?? '—';
            $err = $row['error_rate'] ?? '0.00%';
            $rss = $row['rss_mb'] ?? '—';

            $bold = ($fwKey === 'pivotphp') ? '**' : '';
            $md .= sprintf(
                "| %d | %s%s%s | %s | %s%s%s | %s | %s | %s | %s | %s |\n",
                $pos++,
                $bold, $name, $bold,
                $runtime,
                $bold, $rps, $bold,
                $p50,
                $p95,
                $p99,
                $err,
                $rss
            );
        }
        $md .= "\n";
    }
    $md .= "<!-- BENCHMARK-LEADERBOARD-END -->";

    $readmePath = $rootDir . '/README.md';
    $readmeContent = file_get_contents($readmePath);

    if (str_contains($readmeContent, '<!-- BENCHMARK-LEADERBOARD-START -->')) {
        $pattern = '/<!-- BENCHMARK-LEADERBOARD-START -->.*?<!-- BENCHMARK-LEADERBOARD-END -->/s';
        $updated = preg_replace($pattern, $md, $readmeContent);
    } else {
        // Adiciona antes da seção Estrutura
        $target = '## Estrutura';
        if (str_contains($readmeContent, $target)) {
            $updated = str_replace($target, $md . "\n\n" . $target, $readmeContent);
        } else {
            $updated = $readmeContent . "\n\n" . $md;
        }
    }

    file_put_contents($readmePath, $updated);
    echo "\033[1;32m✓ README.md atualizado com sucesso com as tabelas de leaderboard!\033[0m\n";
}

function executeBenchmark(string $fwKey, array $fwData, string $scenarioKey, array $scenario, string $resultsDir, string $rootDir): array
{
    $fwDir = $rootDir . '/' . $fwData['dir'];
    $needsDb = $scenario['needs_database'] ?? false;
    $services = $needsDb ? 'postgres redis app' : 'app';

    // 1. Subir contêineres
    $upFlags = $needsDb ? '' : '--no-deps';
    echo "   [1/5] Subindo contêiner ({$services})...\n";
    shell_exec("cd '{$fwDir}' && docker compose up -d {$upFlags} {$services} >/dev/null 2>&1");

    // 2. Aguardar healthcheck
    echo "   [2/5] Aguardando prontidão da aplicação (porta {$fwData['port']})...\n";
    $ready = false;
    $healthUrl = "http://127.0.0.1:{$fwData['port']}/api/health";
    for ($i = 0; $i < 30; $i++) {
        $ch = curl_init($healthUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $ready = true;
            break;
        }
        usleep(300000); // 300ms
    }

    if (!$ready) {
        echo "   \033[1;31m[!] Aplicação não respondeu em tempo hábil.\033[0m\n";
        shell_exec("cd '{$fwDir}' && docker compose down -v >/dev/null 2>&1");
        return [
            'status' => 'Falha',
            'rps' => 0.0,
            'p50' => '—',
            'p90' => '—',
            'p95' => '—',
            'p99' => '—',
            'error_rate' => '100%',
            'rss_mb' => '—',
        ];
    }

    // 3. Execução k6
    $summaryFile = "{$resultsDir}/{$fwKey}_{$scenarioKey}_summary.json";
    $logFile = "{$resultsDir}/{$fwKey}_{$scenarioKey}.log";

    $k6Script = $scenario['k6_script'] ?? 'modular-runner.js';
    $method = $scenario['method'];
    $path = $scenario['path'];
    $body = $scenario['body'] ? escapeshellarg($scenario['body']) : "''";
    $vus = $scenario['vus'];
    $warmup = $scenario['warmup'];
    $duration = $scenario['duration'];
    $expectedStatus = $scenario['expected_status'];

    $uid = posix_getuid();
    $gid = posix_getgid();

    echo "   [3/5] Executando carga e warm-up ({$duration}s)...\n";
    $k6Cmd = sprintf(
        "cd '%s' && docker compose --profile loadtest run --rm " .
        "--user '%d:%d' " .
        "-v '%s':/out " .
        "-e SCENARIO_METHOD='%s' " .
        "-e SCENARIO_PATH='%s' " .
        "-e SCENARIO_BODY=%s " .
        "-e SCENARIO_VUS=%d " .
        "-e SCENARIO_WARMUP=%d " .
        "-e SCENARIO_DURATION=%d " .
        "-e SCENARIO_STATUS=%d " .
        "k6 run --summary-export '/out/%s_%s_summary.json' '%s' > '%s' 2>&1",
        $fwDir,
        $uid,
        $gid,
        $resultsDir,
        $method,
        $path,
        $body,
        $vus,
        $warmup,
        $duration,
        $expectedStatus,
        $fwKey,
        $scenarioKey,
        $k6Script,
        $logFile
    );

    shell_exec($k6Cmd);

    // 4. Medir Memória RSS
    $containerName = "{$fwData['dir']}-app";
    $stats = shell_exec("docker stats {$containerName} --no-stream --format '{{.MemUsage}}' 2>/dev/null");
    $rss = '—';
    if ($stats && preg_match('/([\d.]+\s*[A-Za-z]+)/', $stats, $m)) {
        $rss = trim($m[1]);
    }

    // 5. Teardown
    echo "   [4/5] Coletando métricas e limpando contêiner...\n";
    shell_exec("cd '{$fwDir}' && docker compose down -v >/dev/null 2>&1");

    // 6. Parse JSON Summary
    $metrics = parseSummary($summaryFile);
    $metrics['rss_mb'] = $rss;
    echo "   \033[1;32m[5/5] Concluído: {$metrics['rps']} req/s (p50: {$metrics['p50']})\033[0m\n";

    return $metrics;
}

function parseSummary(string $summaryFile): array
{
    $default = [
        'status' => 'Concluído',
        'rps' => 0.0,
        'p50' => '—',
        'p90' => '—',
        'p95' => '—',
        'p99' => '—',
        'error_rate' => '0.00%',
        'total_reqs' => 0,
    ];

    if (!file_exists($summaryFile)) {
        return $default;
    }

    $data = json_decode((string) file_get_contents($summaryFile), true);
    if (!is_array($data) || !isset($data['metrics'])) {
        return $default;
    }

    $m = $data['metrics'];
    $reqs = $m['http_reqs']['values'] ?? $m['http_reqs'] ?? [];
    $duration = $m['http_req_duration']['values'] ?? $m['http_req_duration'] ?? [];
    $failed = $m['http_req_failed']['values'] ?? $m['http_req_failed'] ?? [];

    $rate = $reqs['rate'] ?? 0.0;
    $count = $reqs['count'] ?? 0;

    $p50 = isset($duration['med']) ? sprintf('%.2fms', $duration['med']) : '—';
    $p90 = isset($duration['p(90)']) ? sprintf('%.2fms', $duration['p(90)']) : '—';
    $p95 = isset($duration['p(95)']) ? sprintf('%.2fms', $duration['p(95)']) : '—';
    $p99 = isset($duration['p(99)']) ? sprintf('%.2fms', $duration['p(99)']) : '—';

    $failRate = ($failed['rate'] ?? 0.0) * 100;

    return [
        'status' => 'Concluído',
        'rps' => round($rate, 2),
        'p50' => $p50,
        'p90' => $p90,
        'p95' => $p95,
        'p99' => $p99,
        'error_rate' => sprintf('%.2f%%', $failRate),
        'total_reqs' => $count,
    ];
}

function printResultsTable(string $scenarioKey, array $results, array $frameworks): void
{
    // Ordenar decrescente por RPS
    uasort($results, fn ($a, $b) => ($b['rps'] ?? 0) <=> ($a['rps'] ?? 0));

    echo "\n\033[1;37m=========================================================================================\033[0m\n";
    printf("  \033[1;36mRESULTADOS: %s\033[0m\n", strtoupper($scenarioKey));
    echo "\033[1;37m=========================================================================================\033[0m\n";
    printf(
        " %-4s | %-12s | %-14s | %-10s | %-10s | %-10s | %-8s | %-8s\n",
        'Pos', 'Framework', 'Throughput', 'Latência p50', 'Latência p95', 'Latência p99', 'Erros', 'RAM RSS'
    );
    echo "-----------------------------------------------------------------------------------------\n";

    $pos = 1;
    foreach ($results as $fwKey => $row) {
        $fwName = $frameworks[$fwKey]['name'] ?? $fwKey;
        $isPivot = ($fwKey === 'pivotphp');
        $color = $isPivot ? "\033[1;32m" : "\033[0m";
        $reset = "\033[0m";

        printf(
            " %-4d | %s%-12s%s | %s%10.2f req/s%s | %10s | %10s | %10s | %8s | %8s\n",
            $pos++,
            $color,
            $fwName,
            $reset,
            $color,
            $row['rps'] ?? 0.0,
            $reset,
            $row['p50'] ?? '—',
            $row['p95'] ?? '—',
            $row['p99'] ?? '—',
            $row['error_rate'] ?? '—',
            $row['rss_mb'] ?? '—'
        );
    }
    echo "=========================================================================================\n\n";
}
