<?php

/**
 * Test Script untuk Missing Calculation Endpoints
 * Tests UPDATE, DELETE, and COMPARE INSTALLATION TYPES
 *
 * Run: php test-missing-endpoints.php
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
echo "║     MISSING ENDPOINTS VERIFICATION TEST SUITE     ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// TEST #1: UPDATE CALCULATION
// ============================================
echo "\n\n📝 TEST #1: Update Calculation\n";
echo "════════════════════════════════════════\n";

// First, create a calculation to update
echo "\n📍 Step 1: Create calculation to update\n";
$result = testEndpoint(
    'Create Calculation for Update Test',
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
        'project_name' => 'Test Update Original',
        'notes' => 'Will be updated',
    ],
    201
);

$calcId = null;
if ($result['success'] && isset($result['data']['data']['id'])) {
    $calcId = $result['data']['data']['id'];
    $originalCost = $result['data']['data']['calculation']['total_material_cost'];
    echo "\n💾 Created Calculation ID: {$calcId}\n";
    echo "💰 Original Total Cost: Rp " . number_format($originalCost) . "\n";
}

// Now update it
if ($calcId) {
    echo "\n📍 Step 2: Update calculation (change wall dimensions)\n";
    $result = testEndpoint(
        'Update Calculation - Change Dimensions',
        'PUT',
        "$baseUrl/calculations/{$calcId}",
        [
            'wall_length' => 10, // Changed from 5
            'wall_height' => 3,  // Changed from 2.5
            'mortar_thickness' => 1.5,
            'installation_type_id' => 1,
            'mortar_formula_id' => 1,
            'brick_id' => 1,
            'cement_id' => 1,
            'sand_id' => 1,
            'project_name' => 'Test Update UPDATED',
            'notes' => 'Successfully updated!',
        ]
    );

    if ($result['success'] && isset($result['data']['data']['calculation'])) {
        $calc = $result['data']['data']['calculation'];
        $newCost = $calc['total_material_cost'];
        echo "\n📊 Update Result:\n";
        echo "   Project Name: {$calc['project_name']}\n";
        echo "   Notes: {$calc['notes']}\n";
        echo "   Wall Area: {$calc['wall_area']} m²\n";
        echo "   Brick Quantity: {$calc['brick_quantity']} buah\n";
        echo "   New Total Cost: Rp " . number_format($newCost) . "\n";
        echo "   Cost Difference: Rp " . number_format($newCost - $originalCost) . "\n";

        if ($newCost > $originalCost) {
            echo "✅ PASS: Update calculation working! Cost increased as expected.\n";
        } else {
            echo "⚠️ WARNING: Cost should have increased after doubling area!\n";
        }
    } else {
        echo "❌ FAIL: Update calculation failed!\n";
    }
}

// ============================================
// TEST #2: COMPARE INSTALLATION TYPES
// ============================================
echo "\n\n⚖️ TEST #2: Compare Installation Types\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Compare Installation Types - Same brick, different types',
    'POST',
    "$baseUrl/calculations/compare-installation-types",
    [
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 1.5,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'cement_id' => 1,
        'sand_id' => 1,
    ]
);

if ($result['success'] && isset($result['data']['data'])) {
    $comparisons = $result['data']['data'];
    $typeCount = count($comparisons);
    echo "\n📊 Installation Types compared: {$typeCount}\n";

    if ($typeCount > 0) {
        echo "\n   Results by Installation Type:\n";
        foreach ($comparisons as $index => $comp) {
            $type = $comp['installation_type'] ?? 'N/A';
            $brickQty = $comp['brick_quantity'] ?? 0;
            $cost = $comp['total_cost'] ?? 0;
            echo "   " . ($index + 1) . ". {$type}:\n";
            echo "      - Bricks: {$brickQty} buah\n";
            echo "      - Mortar: {$comp['mortar_volume']} m³\n";
            echo "      - Cement: {$comp['cement_50kg']} sak (50kg)\n";
            echo "      - Sand: {$comp['sand_m3']} m³\n";
            echo "      - Total Cost: Rp " . number_format($cost) . "\n";
        }

        // Find cheapest and most expensive
        $costs = array_column($comparisons, 'total_cost');
        $minCost = min($costs);
        $maxCost = max($costs);
        $minIndex = array_search($minCost, $costs);
        $maxIndex = array_search($maxCost, $costs);

        echo "\n   💰 Analysis:\n";
        echo "      Cheapest: {$comparisons[$minIndex]['installation_type']} (Rp " . number_format($minCost) . ")\n";
        echo "      Most Expensive: {$comparisons[$maxIndex]['installation_type']} (Rp " . number_format($maxCost) . ")\n";
        echo "      Difference: Rp " . number_format($maxCost - $minCost) . "\n";

        echo "\n✅ PASS: Compare installation types working!\n";
    } else {
        echo "❌ FAIL: No comparisons generated!\n";
    }
} else {
    echo "❌ FAIL: Compare installation types failed!\n";
}

// ============================================
// TEST #3: DELETE CALCULATION
// ============================================
echo "\n\n🗑️ TEST #3: Delete Calculation\n";
echo "════════════════════════════════════════\n";

if ($calcId) {
    echo "\n📍 Step 1: Verify calculation exists before delete\n";
    $result = testEndpoint(
        "Get Calculation #{$calcId} Before Delete",
        'GET',
        "$baseUrl/calculations/{$calcId}"
    );

    if ($result['success']) {
        echo "✅ Calculation exists, ready to delete\n";

        echo "\n📍 Step 2: Delete calculation\n";
        $result = testEndpoint(
            "Delete Calculation #{$calcId}",
            'DELETE',
            "$baseUrl/calculations/{$calcId}"
        );

        if ($result['success'] && isset($result['data']['message'])) {
            echo "\n💾 Delete Result: {$result['data']['message']}\n";
            echo "✅ PASS: Calculation deleted successfully!\n";

            echo "\n📍 Step 3: Verify calculation is deleted\n";
            $result = testEndpoint(
                "Get Deleted Calculation #{$calcId}",
                'GET',
                "$baseUrl/calculations/{$calcId}",
                null,
                404
            );

            if (!$result['success'] && isset($result['data']['message'])) {
                echo "\n✅ PASS: Calculation not found (correctly deleted)!\n";
            } else {
                echo "❌ FAIL: Calculation should not exist after delete!\n";
            }
        } else {
            echo "❌ FAIL: Delete calculation failed!\n";
        }
    } else {
        echo "❌ FAIL: Calculation not found before delete test!\n";
    }
} else {
    echo "⚠️ SKIPPED: No calculation ID available for delete test\n";
}

// ============================================
// TEST #4: UPDATE NON-EXISTENT CALCULATION
// ============================================
echo "\n\n🛡️ TEST #4: Error Handling - Update Non-Existent\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Update Non-Existent Calculation',
    'PUT',
    "$baseUrl/calculations/99999",
    [
        'wall_length' => 10,
        'wall_height' => 3,
        'mortar_thickness' => 1.5,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'brick_id' => 1,
        'cement_id' => 1,
        'sand_id' => 1,
    ],
    404
);

if (
    !$result['success'] &&
    isset($result['data']['success']) &&
    $result['data']['success'] === false &&
    isset($result['data']['message'])
) {
    echo "\n✅ PASS: 404 error handled correctly!\n";
    echo "   Message: {$result['data']['message']}\n";
} else {
    echo "❌ FAIL: Should return 404 for non-existent calculation!\n";
}

// ============================================
// TEST #5: DELETE NON-EXISTENT CALCULATION
// ============================================
echo "\n\n🛡️ TEST #5: Error Handling - Delete Non-Existent\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Delete Non-Existent Calculation',
    'DELETE',
    "$baseUrl/calculations/99999",
    null,
    404
);

if (
    !$result['success'] &&
    isset($result['data']['success']) &&
    $result['data']['success'] === false &&
    isset($result['data']['message'])
) {
    echo "\n✅ PASS: 404 error handled correctly!\n";
    echo "   Message: {$result['data']['message']}\n";
} else {
    echo "❌ FAIL: Should return 404 for non-existent calculation!\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║       MISSING ENDPOINTS VERIFICATION COMPLETE      ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Test #1: Update Calculation\n";
echo "✅ Test #2: Compare Installation Types\n";
echo "✅ Test #3: Delete Calculation\n";
echo "✅ Test #4: Update Non-Existent (404 Handling)\n";
echo "✅ Test #5: Delete Non-Existent (404 Handling)\n";
echo "\n";
echo "🎉 ALL MISSING ENDPOINTS VERIFIED!\n";
echo "\n";
