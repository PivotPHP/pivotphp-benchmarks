<?php

declare(strict_types=1);

/**
 * Validador de versões do ecossistema PivotPHP Benchmarks
 * 
 * Verifica se todos os projetos estão usando PivotPHP Core v1.1.4
 */

echo "🔍 VALIDAÇÃO DE VERSÕES - PivotPHP BENCHMARKS\n";
echo "============================================\n\n";

$projectsToCheck = [
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase1' => 'Phase 1 (Core Baseline)',
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase2' => 'Phase 2 (Extensions)',
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase2/docker' => 'Phase 2 Docker (ORM)',
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase2/docker' => 'Phase 2 Docker (ReactPHP)'
];

$allUpdated = true;

foreach ($projectsToCheck as $path => $description) {
    echo "📦 Verificando: $description\n";
    echo "   Path: $path\n";
    
    // Verificar composer.json principal
    $composerJsonPath = $path . '/composer.json';
    if (file_exists($composerJsonPath)) {
        $composerData = json_decode(file_get_contents($composerJsonPath), true);
        
        if (isset($composerData['require']['pivotphp/core'])) {
            $version = $composerData['require']['pivotphp/core'];
            echo "   composer.json: $version ";
            
            if (strpos($version, '^1.1.4') !== false) {
                echo "✅\n";
            } else {
                echo "❌ (needs update to ^1.1.4)\n";
                $allUpdated = false;
            }
        } else {
            echo "   composer.json: No pivotphp/core dependency\n";
        }
    }
    
    // Verificar composer.lock para versão instalada
    $composerLockPath = $path . '/composer.lock';
    if (file_exists($composerLockPath)) {
        $lockData = json_decode(file_get_contents($composerLockPath), true);
        
        foreach ($lockData['packages'] ?? [] as $package) {
            if ($package['name'] === 'pivotphp/core') {
                $installedVersion = $package['version'];
                echo "   composer.lock: $installedVersion ";
                
                if (strpos($installedVersion, '1.1.4') !== false) {
                    echo "✅\n";
                } else {
                    echo "❌ (needs composer update)\n";
                    $allUpdated = false;
                }
                break;
            }
        }
    }
    
    echo "\n";
}

// Verificar arquivos específicos do Docker
$dockerFiles = [
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase2/docker/composer-orm.json' => 'ORM Docker Config',
    '/home/cfernandes/pivotphp/pivotphp-benchmarks/phase2/docker/composer-reactphp.json' => 'ReactPHP Docker Config'
];

echo "🐳 CONFIGURAÇÕES DOCKER\n";
echo "=======================\n\n";

foreach ($dockerFiles as $filePath => $description) {
    echo "📄 Verificando: $description\n";
    echo "   File: $filePath\n";
    
    if (file_exists($filePath)) {
        $data = json_decode(file_get_contents($filePath), true);
        
        if (isset($data['require']['pivotphp/core'])) {
            $version = $data['require']['pivotphp/core'];
            echo "   Version: $version ";
            
            if (strpos($version, '^1.1.4') !== false) {
                echo "✅\n";
            } else {
                echo "❌ (needs update to ^1.1.4)\n";
                $allUpdated = false;
            }
        }
    } else {
        echo "   ❌ File not found\n";
        $allUpdated = false;
    }
    
    echo "\n";
}

// Verificar extensões específicas
echo "📊 EXTENSÕES PIVOTPHP\n";
echo "===================\n\n";

$extensionPaths = [
    '/home/cfernandes/pivotphp/pivotphp-cycle-orm' => 'PivotPHP Cycle ORM',
    '/home/cfernandes/pivotphp/pivotphp-reactphp' => 'PivotPHP ReactPHP'
];

foreach ($extensionPaths as $path => $name) {
    echo "🔧 Verificando: $name\n";
    echo "   Path: $path\n";
    
    if (is_dir($path)) {
        $composerFile = $path . '/composer.json';
        if (file_exists($composerFile)) {
            $data = json_decode(file_get_contents($composerFile), true);
            
            if (isset($data['require']['pivotphp/core'])) {
                $version = $data['require']['pivotphp/core'];
                echo "   Requires PivotPHP Core: $version ";
                
                if (strpos($version, '^1.1') !== false) {
                    echo "✅ (Compatible)\n";
                } else {
                    echo "❌ (May need update)\n";
                    $allUpdated = false;
                }
            } else {
                echo "   No direct dependency on pivotphp/core\n";
            }
            
            // Verificar versão da própria extensão
            if (isset($data['version'])) {
                echo "   Extension Version: {$data['version']}\n";
            }
        } else {
            echo "   ❌ composer.json not found\n";
        }
    } else {
        echo "   ⚠️ Directory not found (extension not installed locally)\n";
    }
    
    echo "\n";
}

// Resumo final
echo "🎯 RESUMO FINAL\n";
echo "==============\n\n";

if ($allUpdated) {
    echo "✅ TODOS OS PROJETOS ATUALIZADOS PARA v1.1.4!\n";
    echo "   - Todos os composer.json apontam para ^1.1.4\n";
    echo "   - Configurações Docker atualizadas\n";
    echo "   - Compatibilidade das extensões verificada\n\n";
    echo "🚀 Sistema pronto para benchmarks com PivotPHP v1.1.4!\n";
} else {
    echo "❌ ALGUMAS ATUALIZAÇÕES NECESSÁRIAS\n";
    echo "   Execute os comandos de atualização necessários:\n";
    echo "   composer update pivotphp/core --with-dependencies\n\n";
    echo "⚠️ Verifique as versões marcadas como ❌ acima.\n";
}

echo "\n📅 Validação concluída em: " . date('Y-m-d H:i:s') . "\n";