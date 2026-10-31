<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - MASTER AUTOMATED TEST RUNNER
 * ==============================================================================
 * Ejecuta de forma unificada y secuencial todas las suites de prueba:
 * 1. Unit Tests Suite (34 pruebas de algoritmos y criptografía)
 * 2. Auth & Security Direct DB Suite (pruebas de hashing BCRYPT y sanitización)
 * 3. Complete Integration & End-to-End Suite (15 pruebas de endpoints y reglas)
 *
 * Uso:
 *   php tests/run_all_tests.php
 * ==============================================================================
 */

echo "\n" . str_repeat('=', 75) . "\n";
echo "   KYNAP FITNESS PLATFORM - MASTER AUTOMATED TEST SUITE RUNNER\n";
echo "   Fecha: " . date('Y-m-d H:i:s') . " | PHP: " . PHP_VERSION . " | OS: " . PHP_OS . "\n";
echo str_repeat('=', 75) . "\n\n";

$suites = [
    [
        'name' => '1. UNIT TESTS SUITE (ALGORITMOS, CRIPTOGRAFÍA, FISIOLOGÍA)',
        'file' => __DIR__ . '/UnitTests.php',
    ],
    [
        'name' => '2. DIRECT DATABASE AUTH & SECURITY SUITE (BCRYPT, BIOMETRÍA)',
        'file' => dirname(__DIR__) . '/test_auth_security.php',
    ],
    [
        'name' => '3. COMPREHENSIVE INTEGRATION & API SUITE (ENDPOINTS, REGLAS)',
        'file' => dirname(__DIR__) . '/test_complete_integration.php',
    ]
];

$globalStart = microtime(true);
$suiteResults = [];

foreach ($suites as $suite) {
    echo "\n>>> EJECUTANDO: {$suite['name']}...\n";
    echo str_repeat('-', 75) . "\n";
    $suiteStart = microtime(true);
    
    // Ejecutar mediante CLI
    $cmd = 'php ' . escapeshellarg($suite['file']);
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    $suiteTime = round((microtime(true) - $suiteStart) * 1000, 2);

    echo implode("\n", $output) . "\n";

    $status = ($returnCode === 0) ? 'EXITOSA (PASS)' : 'FALLIDA (FAIL)';
    $suiteResults[] = [
        'name'   => $suite['name'],
        'status' => $status,
        'code'   => $returnCode,
        'time'   => $suiteTime
    ];
}

$globalTime = round((microtime(true) - $globalStart) * 1000, 2);

echo "\n" . str_repeat('=', 75) . "\n";
echo "                    RESUMEN GENERAL DE EJECUCIÓN\n";
echo str_repeat('=', 75) . "\n";

$allPassed = true;
foreach ($suiteResults as $res) {
    $flag = ($res['code'] === 0) ? "[PASS]" : "[FAIL]";
    printf(" %-6s %-55s (%6.1f ms)\n", $flag, $res['name'], $res['time']);
    if ($res['code'] !== 0) $allPassed = false;
}

echo str_repeat('-', 75) . "\n";
echo " TIEMPO TOTAL DE PRUEBAS: {$globalTime} ms\n";
echo " ESTADO GLOBAL: " . ($allPassed ? "TODAS LAS SUITES PASARON (100% OK)" : "FALLAS DETECTADAS") . "\n";
echo str_repeat('=', 75) . "\n\n";

exit($allPassed ? 0 : 1);

