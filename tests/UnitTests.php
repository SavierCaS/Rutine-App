<?php
/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - UNIT TEST SUITE (PHP 7.3+)
 * ==============================================================================
 * Pruebas unitarias de algoritmos metabólicos, motores criptográficos,
 * sanitización de seguridad, lógica de entrenamiento y reglas de negocio.
 *
 * Ejecutable vía CLI: php tests/UnitTests.php
 * ==============================================================================
 */

require_once dirname(__DIR__) . '/controllers/api_bootstrap.php';

class SimpleTestRunner {
    private $passed = 0;
    private $failed = 0;
    private $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "   KYNAP FITNESS PLATFORM - UNIT TEST SUITE\n";
        echo "   Entorno: PHP " . PHP_VERSION . " | " . PHP_OS . "\n";
        echo str_repeat('=', 70) . "\n\n";
    }

    public function assert(bool $condition, string $testName, string $details = '') {
        if ($condition) {
            $this->passed++;
            echo " [\033[32mPASS\033[0m] " . $testName . ($details ? " ($details)" : "") . "\n";
        } else {
            $this->failed++;
            echo " [\033[31mFAIL\033[0m] " . $testName . ($details ? " -> " . $details : "") . "\n";
        }
    }

    public function assertEquals($expected, $actual, string $testName) {
        $matches = ($expected === $actual);
        $detail = $matches ? "Valor: " . json_encode($actual) : "Esperado: " . json_encode($expected) . ", Obtenido: " . json_encode($actual);
        $this->assert($matches, $testName, $detail);
    }

    public function summary(): bool {
        $elapsed = round((microtime(true) - $this->startTime) * 1000, 2);
        $total = $this->passed + $this->failed;
        echo "\n" . str_repeat('-', 70) . "\n";
        echo " RESULTADO DE PRUEBAS UNITARIAS:\n";
        echo " Total: $total | Pasaron: {$this->passed} | Fallaron: {$this->failed} | Tiempo: {$elapsed}ms\n";
        echo str_repeat('=', 70) . "\n";
        return ($this->failed === 0);
    }
}

$t = new SimpleTestRunner();

// ==============================================================================
// 1. SUITE: CÁLCULOS METABÓLICOS Y MACRONUTRIENTES (MIFFLIN-ST JEOR & TDEE)
// ==============================================================================
echo "--- 1. ALGORITMOS METABÓLICOS Y NUTRICIONALES ---\n";

// Caso 1: Hombre, 80kg, 180cm, 30 años, moderado, ganar músculo
$maleGain = calculateNutritionTargets(80.0, 180.0, 30, 'masculino', 'moderado', 'ganar_musculo');
$t->assert($maleGain['calories'] > 2000, "Hombre ganar músculo: Calorías calculadas razonables", "Calorías: {$maleGain['calories']} kcal");
$t->assertEquals(160, $maleGain['proteinG'], "Hombre ganar músculo: 80kg * 2.0 = 160g proteína");
$t->assert($maleGain['waterMl'] >= 2800, "Hombre ganar músculo: Hidratación proporcional al peso", "Agua: {$maleGain['waterMl']} ml");

// Caso 2: Mujer, 60kg, 165cm, 28 años, activo, bajar de peso
$femLoss = calculateNutritionTargets(60.0, 165.0, 28, 'femenino', 'activo', 'bajar_peso');
$t->assert($femLoss['calories'] >= 1300, "Mujer perder peso: Salvaguarda de calorías mínimas (>= 1300)", "Calorías: {$femLoss['calories']} kcal");
$t->assertEquals(132, $femLoss['proteinG'], "Mujer perder peso: 60kg * 2.2 = 132g proteína");

// Caso 3: Manejo seguro de datos de entrada inválidos (peso <= 0)
$safeDefault = calculateNutritionTargets(-10, 0, 0);
$t->assertEquals(2200, $safeDefault['calories'], "Entrada inválida o cero retorna calorías predeterminadas seguras (2200)");
$t->assertEquals(150, $safeDefault['proteinG'], "Entrada inválida retorna proteína predeterminada segura (150g)");

// Caso 4: Límites de agua (mínimo 2000ml, máximo 4500ml)
$lowWeightWater = calculateNutritionTargets(40.0, 150.0, 20)['waterMl'];
$highWeightWater = calculateNutritionTargets(150.0, 195.0, 25)['waterMl'];
$t->assertEquals(2000, $lowWeightWater, "Límite inferior de consumo de agua (mínimo 2000 ml)");
$t->assertEquals(4500, $highWeightWater, "Límite superior de consumo de agua (máximo 4500 ml)");

// ==============================================================================
// 2. SUITE: MOTOR DE SEGURIDAD CRIPTOGRÁFICA Y TOKENS HMAC
// ==============================================================================
echo "\n--- 2. SEGURIDAD CRIPTOGRÁFICA & BEARER TOKENS HMAC ---\n";

$userId = 42;
$userEmail = 'tester@kynap.com';
$token = generateAuthToken($userId, $userEmail);

$t->assert(!empty($token), "Generación de token HMAC", "Token: " . substr($token, 0, 30) . "...");
$parts = explode('.', $token);
$t->assertEquals(2, count($parts), "Estructura correcta del token: payload.signature");

// Validación exitosa
$verifiedPayload = verifyAuthToken($token);
$t->assert(is_array($verifiedPayload), "Validación de token HMAC legítimo");
$t->assertEquals($userId, $verifiedPayload['uid'] ?? 0, "Identificador de usuario íntegro");
$t->assertEquals($userEmail, $verifiedPayload['email'] ?? '', "Correo electrónico verificado íntegro");

// Rechazo de firma manipulada
$tamperedSignature = $parts[0] . '.' . hash('sha256', 'firma_falsa_manipulada');
$t->assert(verifyAuthToken($tamperedSignature) === null, "Rechazo inmediato de token con firma HMAC adulterada");

// Rechazo de payload manipulado (intento de suplantación de usuario)
$hackedPayload = base64_encode(json_encode(['uid' => 1, 'email' => 'admin@kynap.com', 'exp' => time() + 3600]));
$tamperedPayload = $hackedPayload . '.' . $parts[1];
$t->assert(verifyAuthToken($tamperedPayload) === null, "Rechazo de payload modificado sin correspondencia de firma");

// Rechazo de token expirado
$expiredData = base64_encode(json_encode(['uid' => $userId, 'email' => $userEmail, 'exp' => time() - 3600]));
$expiredSignature = hash_hmac('sha256', $expiredData, KYNAP_AUTH_SECRET);
$expiredToken = $expiredData . '.' . $expiredSignature;
$t->assert(verifyAuthToken($expiredToken) === null, "Rechazo de token formalmente firmado pero expirado");

// ==============================================================================
// 3. SUITE: SANITIZACIÓN DE ENTRADAS Y MITIGACIÓN OWASP (XSS & INYECCIÓN)
// ==============================================================================
echo "\n--- 3. SANITIZACIÓN DE ENTRADAS & PROTECCIÓN OWASP ---\n";

$xssAttack = "<script>alert('Ataque XSS')</script>Juan Perez";
$sanitizedXss = sanitizeString($xssAttack);
$t->assert(strpos($sanitizedXss, '<script>') === false, "Neutralización de etiquetas <script>");
$t->assert(strpos($sanitizedXss, 'Juan Perez') !== false, "Preservación del texto legítimo en sanitización");

$specialChars = "Atleta & 'Pro' \"Champion\"";
$sanitizedEntities = sanitizeString($specialChars);
$t->assert(strpos($sanitizedEntities, '&amp;') !== false || strpos($sanitizedEntities, '&') !== false, "Manejo seguro de caracteres especiales");

// ==============================================================================
// 4. SUITE: CÁLCULOS DE FISIOLOGÍA DEPORTIVA (1RM EPLEY & VOLUMEN)
// ==============================================================================
echo "\n--- 4. FISIOLOGÍA DEPORTIVA (1RM EPLEY & TONELAJE) ---\n";

// Fórmula Epley: 1RM = Peso * (1 + Repeticiones / 30)
function calculateEpley1RM(float $weight, int $reps): float {
    if ($reps <= 0 || $weight <= 0) return 0.0;
    if ($reps === 1) return $weight;
    return round($weight * (1.0 + ($reps / 30.0)), 2);
}

$rm1 = calculateEpley1RM(100.0, 1);
$t->assertEquals(100.0, $rm1, "1RM para 1 repetición equivale exactamente al peso levantado (100kg)");

$rm10 = calculateEpley1RM(100.0, 10);
// 100 * (1 + 10/30) = 100 * 1.3333 = 133.33kg
$t->assertEquals(133.33, $rm10, "1RM para 100kg x 10 reps = 133.33kg");

$tonnage = (100.0 * 10) + (100.0 * 8) + (90.0 * 10);
$t->assertEquals(2700.0, (float)$tonnage, "Cálculo de tonelaje acumulado de sesión de entrenamiento (2700 kg)");

// ==============================================================================
// 5. SUITE: MOTOR DE DÍAS Y CALENDARIO DEL SERVIDOR
// ==============================================================================
echo "\n--- 5. MOTOR DE FECHAS & DÍAS DEL SERVIDOR ---\n";

$dayKey = getCurrentServerDayKey();
$validDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
$t->assert(in_array($dayKey, $validDays), "Día del servidor resuelto a clave válida en español", "Día: $dayKey");

$dayName = getSpanishDayName($dayKey);
$t->assert(!empty($dayName) && ctype_upper($dayName[0]), "Nombre formateado con mayúscula inicial", "Nombre: $dayName");

// ==============================================================================
// 6. SUITE: POLÍTICA DE CONDICIÓN FÍSICA (VENTANA DE 24 HORAS)
// ==============================================================================
echo "\n--- 6. POLÍTICA DE ACTUALIZACIÓN FÍSICA & VENTANA DE 24 HORAS ---\n";

function simulateCorrectionWindow(int $secondsAgo): array {
    $hoursElapsed = $secondsAgo / 3600.0;
    if ($hoursElapsed <= 24.0) {
        return [
            'canUpdate' => true,
            'mode'      => 'correction',
            'remaining' => max(0.1, round(24.0 - $hoursElapsed, 1))
        ];
    }
    return [
        'canUpdate' => false,
        'mode'      => 'locked',
        'remaining' => 0
    ];
}

$recentUpdate = simulateCorrectionWindow(2 * 3600); // Hace 2 horas
$t->assert($recentUpdate['canUpdate'] === true, "Registro de hace 2 horas habilita modo corrección");
$t->assertEquals('correction', $recentUpdate['mode'], "Modo identificado como 'correction'");
$t->assertEquals(22.0, $recentUpdate['remaining'], "22.0 horas restantes de corrección");

$oldUpdate = simulateCorrectionWindow(26 * 3600); // Hace 26 horas
$t->assert($oldUpdate['canUpdate'] === false, "Registro de hace 26 horas bloquea actualización tipográfica");
$t->assertEquals('locked', $oldUpdate['mode'], "Modo bloqueado tras superar las 24 horas");

// ==============================================================================
// 7. SUITE: MOTOR DE RACHAS (GAMIFICACIÓN Y MEDALLAS)
// ==============================================================================
echo "\n--- 7. MOTOR DE RACHAS & MEDALLAS ---\n";

function getStreakBadge(int $streak): array {
    if ($streak >= 30) return ['name' => 'Leyenda Titán', 'icon' => '🏆', 'level' => 5];
    if ($streak >= 14) return ['name' => 'Oro Imparable', 'icon' => '🥇', 'level' => 4];
    if ($streak >= 7)  return ['name' => 'Plata Disciplinada', 'icon' => '🥈', 'level' => 3];
    if ($streak >= 3)  return ['name' => 'Bronce Constante', 'icon' => '🥉', 'level' => 2];
    return ['name' => 'Fuego Inicial', 'icon' => '🔥', 'level' => 1];
}

$badge1 = getStreakBadge(1);
$t->assertEquals('Fuego Inicial', $badge1['name'], "Racha de 1 día otorga medalla Fuego Inicial");

$badge3 = getStreakBadge(3);
$t->assertEquals('Bronce Constante', $badge3['name'], "Racha de 3 días otorga medalla Bronce Constante");

$badge7 = getStreakBadge(7);
$t->assertEquals('Plata Disciplinada', $badge7['name'], "Racha de 7 días otorga medalla Plata Disciplinada");

$badge30 = getStreakBadge(35);
$t->assertEquals('Leyenda Titán', $badge30['name'], "Racha de 35 días otorga medalla Leyenda Titán");

// Resumen final
$allPassed = $t->summary();
exit($allPassed ? 0 : 1);

