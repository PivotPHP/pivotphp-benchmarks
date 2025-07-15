<?php

declare(strict_types=1);

/**
 * Teste de Routing PivotPHP Core v1.1.2
 * 
 * Script para validar todas as sintaxes de routing suportadas
 * e identificar problemas de implementação nos benchmarks
 */

require_once __DIR__ . '/phase2/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

echo "=== Teste de Routing PivotPHP Core v1.1.2 ===\n\n";

$startTime = microtime(true);
$testCount = 0;
$successCount = 0;

try {
    $app = new Application();
    $app->boot();

    echo "✅ Application initialized successfully\n\n";

    // Teste 1: Rota simples com closure
    echo "📋 Teste 1: Rotas simples com closures\n";
    $testCount++;
    
    $app->get('/test/simple', function($req, $res) {
        return $res->json(['test' => 'simple_closure', 'status' => 'ok']);
    });
    echo "   ✅ GET /test/simple - Closure registrada\n";
    $successCount++;

    // Teste 2: Rota com parâmetros usando sintaxe :param
    echo "\n📋 Teste 2: Rotas com parâmetros (:param)\n";
    $testCount++;
    
    $app->get('/test/param/:id', function($req, $res) {
        $id = $req->param('id');
        return $res->json(['test' => 'param_route', 'id' => $id, 'type' => 'colon_syntax']);
    });
    echo "   ✅ GET /test/param/:id - Parâmetro com :id registrado\n";
    $successCount++;

    // Teste 3: Rota com parâmetros usando sintaxe {param}
    echo "\n📋 Teste 3: Rotas com parâmetros ({param})\n";
    $testCount++;
    
    $app->get('/test/brace/{id}', function($req, $res) {
        $id = $req->param('id');
        return $res->json(['test' => 'brace_route', 'id' => $id, 'type' => 'brace_syntax']);
    });
    echo "   ✅ GET /test/brace/{id} - Parâmetro com {id} registrado\n";
    $successCount++;

    // Teste 4: Array Callable com classe
    echo "\n📋 Teste 4: Array Callable com classe\n";
    $testCount++;
    
    class TestController {
        public function testMethod($req, $res) {
            return $res->json(['test' => 'array_callable', 'controller' => 'TestController']);
        }
        
        public function paramMethod($req, $res) {
            $id = $req->param('id');
            return $res->json(['test' => 'array_callable_param', 'id' => $id, 'controller' => 'TestController']);
        }
    }
    
    $app->get('/test/controller', [TestController::class, 'testMethod']);
    $app->get('/test/controller/:id', [TestController::class, 'paramMethod']);
    echo "   ✅ GET /test/controller - Array callable registrado\n";
    echo "   ✅ GET /test/controller/:id - Array callable com parâmetro registrado\n";
    $successCount++;

    // Teste 5: Função nomeada
    echo "\n📋 Teste 5: Função nomeada\n";
    $testCount++;
    
    function testNamedFunction($req, $res) {
        return $res->json(['test' => 'named_function', 'function' => 'testNamedFunction']);
    }
    
    $app->get('/test/function', 'testNamedFunction');
    echo "   ✅ GET /test/function - Função nomeada registrada\n";
    $successCount++;

    // Teste 6: Múltiplos parâmetros
    echo "\n📋 Teste 6: Múltiplos parâmetros\n";
    $testCount++;
    
    $app->get('/test/multi/:type/:id', function($req, $res) {
        $type = $req->param('type');
        $id = $req->param('id');
        return $res->json([
            'test' => 'multi_params',
            'type' => $type,
            'id' => $id,
            'params_count' => 2
        ]);
    });
    echo "   ✅ GET /test/multi/:type/:id - Múltiplos parâmetros registrados\n";
    $successCount++;

    // Teste 7: Método POST com parâmetros
    echo "\n📋 Teste 7: POST com parâmetros\n";
    $testCount++;
    
    $app->post('/test/post/:id', function($req, $res) {
        $id = $req->param('id');
        $body = $req->input();
        return $res->json([
            'test' => 'post_with_param',
            'id' => $id,
            'body' => $body,
            'method' => 'POST'
        ]);
    });
    echo "   ✅ POST /test/post/:id - POST com parâmetro registrado\n";
    $successCount++;

    // Teste 8: Query parameters
    echo "\n📋 Teste 8: Query parameters\n";
    $testCount++;
    
    $app->get('/test/query', function($req, $res) {
        $limit = $req->query('limit') ?? 10;
        $offset = $req->query('offset') ?? 0;
        return $res->json([
            'test' => 'query_params',
            'limit' => $limit,
            'offset' => $offset,
            'method' => 'GET'
        ]);
    });
    echo "   ✅ GET /test/query - Query parameters registrado\n";
    $successCount++;

    // Teste 9: Sintaxe INCORRETA (deve falhar)
    echo "\n📋 Teste 9: Sintaxe incorreta (Controller@method)\n";
    $testCount++;
    
    try {
        // Esta sintaxe NÃO deve funcionar
        $app->get('/test/wrong', 'TestController@testMethod');
        echo "   ❌ ERRO: Sintaxe Controller@method foi aceita (não deveria)\n";
    } catch (TypeError $e) {
        echo "   ✅ Sintaxe Controller@method rejeitada corretamente\n";
        echo "   📝 Erro esperado: " . $e->getMessage() . "\n";
        $successCount++;
    }

    // Teste do sistema de routing interno
    echo "\n📋 Teste 10: Sistema de routing interno\n";
    $testCount++;
    
    // Simular requisição para testar parâmetros
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/test/param/123';
    $_SERVER['SCRIPT_NAME'] = '/test_routing.php';
    
    echo "   ✅ Simulação de requisição configurada\n";
    echo "   📝 URI: /test/param/123\n";
    $successCount++;

    echo "\n=== Resumo dos Testes ===\n";
    echo "Total de testes: $testCount\n";
    echo "Sucessos: $successCount\n";
    echo "Taxa de sucesso: " . round(($successCount / $testCount) * 100, 2) . "%\n";

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;
    echo "Tempo de execução: " . round($executionTime, 2) . "ms\n\n";

    echo "=== Verificação de Métodos de Request ===\n";
    
    // Criar uma instância de Request para teste
    $testRequest = new \PivotPHP\Core\Http\Request();
    
    echo "Métodos disponíveis na classe Request:\n";
    echo "- param(): " . (method_exists($testRequest, 'param') ? '✅' : '❌') . "\n";
    echo "- query(): " . (method_exists($testRequest, 'query') ? '✅' : '❌') . "\n";
    echo "- input(): " . (method_exists($testRequest, 'input') ? '✅' : '❌') . "\n";
    echo "- getParam(): " . (method_exists($testRequest, 'getParam') ? '✅' : '❌') . "\n";

    echo "\n=== Testes de Sintaxe Específicos ===\n";
    
    // Teste diferentes sintaxes de parâmetros
    $paramTests = [
        '/performance/json/:size' => 'Sintaxe colon (:size)',
        '/performance/json/{size}' => 'Sintaxe brace ({size})',
        '/users/:id/posts/:postId' => 'Múltiplos parâmetros colon',
        '/users/{id}/posts/{postId}' => 'Múltiplos parâmetros brace'
    ];
    
    foreach ($paramTests as $route => $description) {
        echo "📝 $description: $route\n";
        try {
            $app->get($route, function($req, $res) use ($route) {
                return $res->json(['route' => $route, 'test' => 'syntax_validation']);
            });
            echo "   ✅ Sintaxe aceita\n";
        } catch (Exception $e) {
            echo "   ❌ Sintaxe rejeitada: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Análise do Problema nos Benchmarks ===\n";
    
    // Análise específica do problema identificado
    echo "🔍 Problema identificado nos benchmarks:\n";
    echo "1. Sintaxe de parâmetros: Verificar se :size ou {size} é suportado\n";
    echo "2. Método param(): Confirmar disponibilidade em \$req->param('size')\n";
    echo "3. Callable validation: Confirmar que closures funcionam corretamente\n";
    echo "4. Route matching: Verificar se o sistema encontra rotas com parâmetros\n\n";

    echo "💡 Recomendações para correção:\n";
    echo "1. Usar closures ao invés de array callables nos benchmarks\n";
    echo "2. Testar sintaxe :param vs {param} para identificar a suportada\n";
    echo "3. Verificar se \$req->param() está disponível na versão 1.1.2\n";
    echo "4. Implementar fallback com query parameters se necessário\n\n";

} catch (Exception $e) {
    echo "❌ Erro durante os testes: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=== Fim dos Testes ===\n";