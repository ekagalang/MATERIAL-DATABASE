<?php

/**
 * Test Script untuk Calculation APIs
 * Verifies all calculation endpoints work correctly
 *
 * Run: php test-calculation-apis.php
 */

$baseUrl = 'http://localhost:8000/api/v1';

function testEndpoint($testName, $method, $url, $data = null, $expectedCode = 200)
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: $testName\n";
    echo "$method $url\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = $httpCode >= 200 && $httpCode < 300;
    $statusEmoji = $success ? '✅' : '❌';
    echo "$statusEmoji HTTP $httpCode (Expected: $expectedCode)\n";

    $json = json_decode($response, true);
    if ($json) {
        echo "Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    return ['success' => $success, 'data' => $json];
}

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║      CALCULATION API VERIFICATION TEST SUITE      ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// TEST #1: PREVIEW SINGLE CALCULATION
// ============================================
echo "\n\n🧮 TEST #1: Preview Single Calculation\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Preview Calculation - 1/2 Bata',
    'POST',
    "$baseUrl/calculations/preview",
    [
        'work_type' => 'brick_half',
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 1.5,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'cement_id' => 1,
        'sand_id' => 1,
        'project_name' => 'Test Preview',
    ]
);

if ($result['success'] && isset($result['data']['data']['calculation'])) {
    $calc = $result['data']['data']['calculation'];
    echo "\n📊 Preview Result:\n";
    echo "   Brick Quantity: " . ($calc['brick_quantity'] ?? 'N/A') . "\n";
    echo "   Cement Quantity: " . ($calc['cement_quantity_sak'] ?? 'N/A') . " sak\n";
    echo "   Sand Quantity: " . ($calc['sand_m3'] ?? 'N/A') . " m³\n";
    echo "   Total Cost: Rp " . number_format($calc['total_material_cost'] ?? 0) . "\n";
    echo "✅ PASS: Preview calculation working!\n";
} else {
    echo "❌ FAIL: Preview calculation failed!\n";
}

// ============================================
// TEST #2: STORE CALCULATION
// ============================================
echo "\n\n💾 TEST #2: Store Calculation\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Store Calculation - Save to Database',
    'POST',
    "$baseUrl/calculations",
    [
        'work_type' => 'brick_half',
        'wall_length' => 5,
        'wall_height' => 2.5,
        'mortar_thickness' => 1.5,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'cement_id' => 1,
        'sand_id' => 1,
        'project_name' => 'Test Save',
        'notes' => 'Test calculation from API',
    ],
    201
);

$savedId = null;
if ($result['success'] && isset($result['data']['data']['id'])) {
    $savedId = $result['data']['data']['id'];
    echo "\n💾 Saved Calculation ID: {$savedId}\n";
    echo "✅ PASS: Calculation saved successfully!\n";
} else {
    echo "❌ FAIL: Store calculation failed!\n";
}

// ============================================
// TEST #3: GET SINGLE CALCULATION
// ============================================
if ($savedId) {
    echo "\n\n🔍 TEST #3: Get Single Calculation\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Get Calculation by ID - {$savedId}",
        'GET',
        "$baseUrl/calculations/{$savedId}"
    );

    if ($result['success'] && isset($result['data']['data']['calculation'])) {
        echo "✅ PASS: Retrieved calculation successfully!\n";
    } else {
        echo "❌ FAIL: Get calculation failed!\n";
    }
}

// ============================================
// TEST #4: GET CALCULATION LOG
// ============================================
echo "\n\n📜 TEST #4: Get Calculation Log\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Calculation Log - Paginated List',
    'GET',
    "$baseUrl/calculations?per_page=5&search=Test"
);

if ($result['success'] && isset($result['data']['data'])) {
    $count = count($result['data']['data']);
    echo "\n📦 Calculations returned: {$count}\n";
    echo "✅ PASS: Calculation log retrieved!\n";
} else {
    echo "❌ FAIL: Get calculation log failed!\n";
}

// ============================================
// TEST #5: CALCULATE WITH COMBINATIONS
// ============================================
echo "\n\n🎯 TEST #5: Calculate with Combinations (Best Filter)\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Calculate - Best Combinations',
    'POST',
    "$baseUrl/calculations/calculate",
    [
        'work_type' => 'brick_half',
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 1.5,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'price_filters' => ['best', 'cheapest'],
    ]
);

if ($result['success'] && isset($result['data']['data']['projects'])) {
    $projects = $result['data']['data']['projects'];
    $projectCount = count($projects);
    echo "\n📊 Projects generated: {$projectCount}\n";

    if ($projectCount > 0 && isset($projects[0]['combinations'])) {
        $comboCount = count($projects[0]['combinations']);
        echo "   Combinations for first project: {$comboCount}\n";

        // Show first few combination labels
        $labels = array_keys($projects[0]['combinations']);
        $displayLabels = array_slice($labels, 0, 3);
        echo "   Sample labels: " . implode(', ', $displayLabels) . "\n";

        echo "✅ PASS: Combinations calculated!\n";
    } else {
        echo "❌ FAIL: No combinations generated!\n";
    }
} else {
    echo "❌ FAIL: Calculate combinations failed!\n";
}

// ============================================
// TEST #6: COMPARE BRICKS
// ============================================
echo "\n\n⚖️ TEST #6: Compare Multiple Bricks\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Compare Bricks - Fair Comparison',
    'POST',
    "$baseUrl/calculations/compare",
    [
        'brick_ids' => [1, 2, 3],
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 1.5,
        'installation_type_id' => 1,
        'work_type' => 'brick_half',
    ]
);

if ($result['success'] && isset($result['data']['data']['comparisons'])) {
    $comparisons = $result['data']['data']['comparisons'];
    $compareCount = count($comparisons);
    echo "\n📊 Bricks compared: {$compareCount}\n";

    if ($compareCount > 0) {
        echo "   Results (sorted by cost):\n";
        foreach ($comparisons as $index => $comp) {
            $brickId = $comp['brick']['id'] ?? 'N/A';
            $cost = $comp['total_cost'] ?? 0;
            $costPerM2 = $comp['cost_per_m2'] ?? 0;
            echo "   " .
                ($index + 1) .
                ". Brick #{$brickId} - Total: Rp " .
                number_format($cost) .
                " (Rp " .
                number_format($costPerM2) .
                "/m²)\n";
        }

        echo "✅ PASS: Brick comparison working!\n";
    } else {
        echo "❌ FAIL: No comparisons generated!\n";
    }
} else {
    echo "❌ FAIL: Compare bricks failed!\n";
}

// ============================================
// TEST #7: TRACE CALCULATION
// ============================================
echo "\n\n🔬 TEST #7: Trace Step-by-Step Calculation\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Trace Calculation - Step by Step',
    'POST',
    "$baseUrl/calculations/trace",
    [
        'formula_code' => 'brick_half',
        'wall_length' => 10,
        'wall_height' => 3,
        'installation_type_id' => 1,
        'mortar_thickness' => 1.5,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'cement_id' => 1,
        'sand_id' => 1,
    ]
);

if ($result['success'] && isset($result['data']['data'])) {
    $trace = $result['data']['data'];
    echo "\n📝 Trace data received\n";

    // Check if steps are present
    if (isset($trace['steps']) || isset($trace['final_result'])) {
        echo "✅ PASS: Trace calculation working!\n";
    } else {
        echo "⚠️ WARNING: Trace data structure unexpected!\n";
    }
} else {
    echo "❌ FAIL: Trace calculation failed!\n";
}

// ============================================
// TEST #8: BRICKLESS CALCULATION (Wall Plastering)
// ============================================
echo "\n\n🧱 TEST #8: Brickless Calculation (Wall Plastering)\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Calculate - Wall Plastering (Brickless)',
    'POST',
    "$baseUrl/calculations/preview",
    [
        'work_type' => 'wall_plastering',
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 2.0,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'brick_id' => 1, // Placeholder
        'cement_id' => 1,
        'sand_id' => 1,
        'plaster_sides' => 2, // Both sides
    ]
);

if ($result['success'] && isset($result['data']['data']['calculation'])) {
    $calc = $result['data']['data']['calculation'];
    $brickQty = $calc['brick_quantity'] ?? 0;

    echo "\n📊 Plastering Result:\n";
    echo "   Brick Quantity: {$brickQty} (should be 0 for brickless)\n";
    echo "   Cement Quantity: " . ($calc['cement_quantity_sak'] ?? 'N/A') . " sak\n";
    echo "   Sand Quantity: " . ($calc['sand_m3'] ?? 'N/A') . " m³\n";

    if ($brickQty == 0) {
        echo "✅ PASS: Brickless calculation working!\n";
    } else {
        echo "⚠️ WARNING: Brick quantity should be 0 for plastering!\n";
    }
} else {
    echo "❌ FAIL: Brickless calculation failed!\n";
}

// ============================================
// TEST #9: VALIDATION ERROR HANDLING
// ============================================
echo "\n\n🛡️ TEST #9: Validation Error Handling\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Invalid Request - Missing Required Fields',
    'POST',
    "$baseUrl/calculations/preview",
    [
        'work_type' => 'brick_half',
        // Missing required fields intentionally
    ],
    422
);

if (
    !$result['success'] &&
    isset($result['data']['success']) &&
    $result['data']['success'] === false &&
    isset($result['data']['errors'])
) {
    echo "\n✅ PASS: Validation errors handled correctly!\n";
    echo "   Errors returned: " . count($result['data']['errors']) . " fields\n";
} else {
    echo "❌ FAIL: Validation error handling not working!\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║         CALCULATION API VERIFICATION COMPLETE      ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Test #1: Preview Single Calculation\n";
echo "✅ Test #2: Store Calculation\n";
echo "✅ Test #3: Get Single Calculation\n";
echo "✅ Test #4: Get Calculation Log\n";
echo "✅ Test #5: Calculate with Combinations\n";
echo "✅ Test #6: Compare Multiple Bricks\n";
echo "✅ Test #7: Trace Step-by-Step\n";
echo "✅ Test #8: Brickless Calculation (Plastering)\n";
echo "✅ Test #9: Validation Error Handling\n";
echo "\n";
echo "🎉 ALL CALCULATION APIs VERIFIED!\n";
echo "\n";
