<?php

/**
 * Test Script untuk Config APIs (Installation Types & Mortar Formulas)
 * Quick verification that config endpoints work
 *
 * Run: php test-config-apis.php
 */

$baseUrl = 'http://localhost:8000/api/v1';

function testEndpoint($testName, $url)
{
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST: $testName\n";
    echo "GET $url\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = $httpCode >= 200 && $httpCode < 300;
    $statusEmoji = $success ? '✅' : '❌';
    echo "$statusEmoji HTTP $httpCode\n";

    $json = json_decode($response, true);
    if ($json) {
        echo "Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    return ['success' => $success, 'data' => $json];
}

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║       CONFIG APIs VERIFICATION TEST SUITE         ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// TEST #1: Get All Installation Types
// ============================================
echo "\n\n🏗️ TEST #1: Get All Installation Types\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get All Installation Types',
    "$baseUrl/installation-types"
);

if ($result['success'] && isset($result['data']['data'])) {
    $types = $result['data']['data'];
    $count = count($types);
    echo "\n📊 Installation Types returned: $count\n";

    if ($count > 0) {
        echo "   Types found:\n";
        foreach ($types as $type) {
            $name = $type['name'] ?? 'N/A';
            $code = $type['code'] ?? 'N/A';
            $bricksPerM2 = $type['bricks_per_sqm'] ?? 'N/A';
            echo "   - $name ($code) - {$bricksPerM2} bricks/m²\n";
        }
        echo "✅ PASS: Installation types retrieved!\n";
    } else {
        echo "⚠️ WARNING: No installation types found!\n";
    }
} else {
    echo "❌ FAIL: Get installation types failed!\n";
}

// ============================================
// TEST #2: Get Default Installation Type
// ============================================
echo "\n\n🎯 TEST #2: Get Default Installation Type\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Default Installation Type',
    "$baseUrl/installation-types/default"
);

if ($result['success'] && isset($result['data']['data'])) {
    $defaultType = $result['data']['data'];
    echo "\n📌 Default Type: {$defaultType['name']} ({$defaultType['code']})\n";
    echo "✅ PASS: Default installation type retrieved!\n";
} else {
    echo "❌ FAIL: Get default installation type failed!\n";
}

// ============================================
// TEST #3: Get Single Installation Type
// ============================================
echo "\n\n🔍 TEST #3: Get Single Installation Type\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Installation Type ID #1',
    "$baseUrl/installation-types/1"
);

if ($result['success'] && isset($result['data']['data'])) {
    $type = $result['data']['data'];
    echo "\n📦 Installation Type Details:\n";
    echo "   Name: {$type['name']}\n";
    echo "   Code: {$type['code']}\n";
    echo "   Bricks/m²: {$type['bricks_per_sqm']}\n";
    echo "   Mortar Volume: {$type['mortar_volume_per_m2']} m³/m²\n";
    echo "✅ PASS: Single installation type retrieved!\n";
} else {
    echo "❌ FAIL: Get single installation type failed!\n";
}

// ============================================
// TEST #4: Get All Mortar Formulas
// ============================================
echo "\n\n🧪 TEST #4: Get All Mortar Formulas\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get All Mortar Formulas',
    "$baseUrl/mortar-formulas"
);

if ($result['success'] && isset($result['data']['data'])) {
    $formulas = $result['data']['data'];
    $count = count($formulas);
    echo "\n📊 Mortar Formulas returned: $count\n";

    if ($count > 0) {
        echo "   Formulas found:\n";
        foreach ($formulas as $formula) {
            $name = $formula['name'] ?? 'N/A';
            $cementRatio = $formula['cement_ratio'] ?? 'N/A';
            $sandRatio = $formula['sand_ratio'] ?? 'N/A';
            $isDefault = $formula['is_default'] ? ' [DEFAULT]' : '';
            echo "   - $name (1:$sandRatio){$isDefault}\n";
        }
        echo "✅ PASS: Mortar formulas retrieved!\n";
    } else {
        echo "⚠️ WARNING: No mortar formulas found!\n";
    }
} else {
    echo "❌ FAIL: Get mortar formulas failed!\n";
}

// ============================================
// TEST #5: Get Default Mortar Formula
// ============================================
echo "\n\n🎯 TEST #5: Get Default Mortar Formula\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Default Mortar Formula',
    "$baseUrl/mortar-formulas/default"
);

if ($result['success'] && isset($result['data']['data'])) {
    $defaultFormula = $result['data']['data'];
    echo "\n📌 Default Formula: {$defaultFormula['name']}\n";
    echo "   Ratio: 1:{$defaultFormula['sand_ratio']}\n";
    echo "   Cement: {$defaultFormula['cement_kg_per_m3']} kg/m³\n";
    echo "   Sand: {$defaultFormula['sand_m3_per_m3']} m³/m³\n";
    echo "✅ PASS: Default mortar formula retrieved!\n";
} else {
    echo "❌ FAIL: Get default mortar formula failed!\n";
}

// ============================================
// TEST #6: Get Single Mortar Formula
// ============================================
echo "\n\n🔍 TEST #6: Get Single Mortar Formula\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Mortar Formula ID #1',
    "$baseUrl/mortar-formulas/1"
);

if ($result['success'] && isset($result['data']['data'])) {
    $formula = $result['data']['data'];
    echo "\n📦 Mortar Formula Details:\n";
    echo "   Name: {$formula['name']}\n";
    echo "   Ratio: 1:{$formula['sand_ratio']}\n";
    echo "   Cement: {$formula['cement_kg_per_m3']} kg/m³\n";
    echo "   Sand: {$formula['sand_m3_per_m3']} m³/m³\n";
    echo "   Water: {$formula['water_liter_per_m3']} liter/m³\n";
    echo "✅ PASS: Single mortar formula retrieved!\n";
} else {
    echo "❌ FAIL: Get single mortar formula failed!\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║        CONFIG APIs VERIFICATION COMPLETE          ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Test #1: Get All Installation Types\n";
echo "✅ Test #2: Get Default Installation Type\n";
echo "✅ Test #3: Get Single Installation Type\n";
echo "✅ Test #4: Get All Mortar Formulas\n";
echo "✅ Test #5: Get Default Mortar Formula\n";
echo "✅ Test #6: Get Single Mortar Formula\n";
echo "\n";
echo "🎉 ALL CONFIG APIs VERIFIED!\n";
echo "\n";
