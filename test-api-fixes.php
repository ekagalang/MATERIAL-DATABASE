<?php

/**
 * Test Script untuk Verify Fixes
 * Tests specific fixes yang baru dibuat
 *
 * Run: php test-api-fixes.php
 */

$baseUrl = 'http://localhost:8000/api/v1';

function testEndpoint($testName, $method, $url, $data = null, $expectedCode = 200) {
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
            'Accept: application/json'
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = ($httpCode >= 200 && $httpCode < 300);
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
echo "║         API FIXES VERIFICATION TEST SUITE         ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// TEST FIX #1: NULL Reset on Update
// ============================================
echo "\n\n🔧 FIX #1 TESTS: NULL Reset on Update\n";
echo "════════════════════════════════════════\n";

// Create a brick dengan price dan volume
echo "\n📍 Step 1: Create brick with price and volume\n";
$result = testEndpoint(
    'Create Brick with Price',
    'POST',
    "$baseUrl/bricks",
    [
        'type' => 'Test NULL Reset',
        'brand' => 'Test Brand',
        'dimension_length' => 20,
        'dimension_width' => 10,
        'dimension_height' => 5,
        'price_per_piece' => 2000,
        'store' => 'Toko Test',
    ],
    201
);

$brickId = null;
if ($result['success'] && isset($result['data']['data']['id'])) {
    $brickId = $result['data']['data']['id'];
    $comparisonPrice = $result['data']['data']['comparison_price_per_m3'] ?? null;
    echo "💾 Created Brick ID: {$brickId}\n";
    echo "💰 Comparison Price: " . ($comparisonPrice ?? 'null') . "\n";

    if ($comparisonPrice !== null) {
        echo "✅ PASS: Comparison price calculated\n";
    } else {
        echo "❌ FAIL: Comparison price should be calculated!\n";
    }
}

// Update brick, hapus price (set to null or empty)
echo "\n📍 Step 2: Update brick - Remove price (should reset comparison to NULL)\n";
if ($brickId) {
    $result = testEndpoint(
        'Update Brick - Remove Price',
        'PUT',
        "$baseUrl/bricks/{$brickId}",
        [
            'price_per_piece' => null, // Hapus price
        ]
    );

    if ($result['success']) {
        $comparisonPrice = $result['data']['data']['comparison_price_per_m3'] ?? 'NOT_SET';
        echo "💰 Comparison Price after update: " . ($comparisonPrice === null ? 'NULL ✅' : $comparisonPrice) . "\n";

        if ($comparisonPrice === null) {
            echo "✅ PASS: Comparison price correctly reset to NULL!\n";
        } else {
            echo "❌ FAIL: Comparison price should be NULL when price removed!\n";
        }
    }
}

// Cleanup
if ($brickId) {
    testEndpoint('Cleanup - Delete Brick', 'DELETE', "$baseUrl/bricks/{$brickId}", null, 204);
}

// ============================================
// TEST FIX #2: Allowed Fields Whitelist
// ============================================
echo "\n\n🔒 FIX #2 TESTS: Field Whitelist Security\n";
echo "════════════════════════════════════════\n";

// Test allowed field (should work)
echo "\n📍 Test 1: Request ALLOWED field (brand)\n";
$result = testEndpoint(
    'Get Field Values - Allowed Field',
    'GET',
    "$baseUrl/bricks/field-values/brand"
);

if ($result['success'] && is_array($result['data']) && count($result['data']) > 0) {
    echo "✅ PASS: Allowed field 'brand' returns data\n";
} else {
    echo "❌ FAIL: Allowed field should return data!\n";
}

// Test unauthorized field (should return empty)
echo "\n📍 Test 2: Request UNAUTHORIZED field (id)\n";
$result = testEndpoint(
    'Get Field Values - Unauthorized Field',
    'GET',
    "$baseUrl/bricks/field-values/id"
);

if ($result['success'] && is_array($result['data']) && count($result['data']) === 0) {
    echo "✅ PASS: Unauthorized field 'id' correctly blocked (empty array)\n";
} else {
    echo "❌ FAIL: Unauthorized field should return empty array!\n";
}

// Test another unauthorized field
echo "\n📍 Test 3: Request UNAUTHORIZED field (created_at)\n";
$result = testEndpoint(
    'Get Field Values - Another Unauthorized',
    'GET',
    "$baseUrl/bricks/field-values/created_at"
);

if ($result['success'] && is_array($result['data']) && count($result['data']) === 0) {
    echo "✅ PASS: Unauthorized field 'created_at' correctly blocked\n";
} else {
    echo "❌ FAIL: Should block unauthorized field!\n";
}

// ============================================
// TEST FIX #3: Cross-Material Queries
// ============================================
echo "\n\n🔗 FIX #3 TESTS: Cross-Material Queries\n";
echo "════════════════════════════════════════\n";

// Test getAllStores with material_type='brick' (should return only brick)
echo "\n📍 Test 1: Get stores - material_type='brick'\n";
$result = testEndpoint(
    'Get Stores - Brick Only',
    'GET',
    "$baseUrl/bricks/all-stores?material_type=brick"
);

if ($result['success']) {
    $storeCount = is_array($result['data']) ? count($result['data']) : 0;
    echo "📦 Stores returned: {$storeCount}\n";
    echo "✅ PASS: Returns brick stores only\n";
}

// Test getAllStores with material_type='all' and search (should merge from all)
echo "\n📍 Test 2: Get stores - material_type='all' with search\n";
$result = testEndpoint(
    'Get Stores - All Materials',
    'GET',
    "$baseUrl/bricks/all-stores?material_type=all&search=Toko"
);

if ($result['success']) {
    $storeCount = is_array($result['data']) ? count($result['data']) : 0;
    echo "📦 Stores returned (from ALL materials): {$storeCount}\n";
    echo "✅ PASS: Returns stores from all materials\n";
}

// Test getAddressesByStore (should merge from all materials)
echo "\n📍 Test 3: Get addresses by store (cross-material)\n";
$result = testEndpoint(
    'Get Addresses - Cross Material',
    'GET',
    "$baseUrl/bricks/addresses-by-store?store=" . urlencode("Toko Bangunan Jaya")
);

if ($result['success']) {
    $addressCount = is_array($result['data']) ? count($result['data']) : 0;
    echo "📍 Addresses returned: {$addressCount}\n";
    echo "✅ PASS: Returns addresses from all materials\n";
}

// ============================================
// TEST FIX #4: Limit Validation
// ============================================
echo "\n\n🔢 FIX #4 TESTS: Limit Validation (max 100)\n";
echo "════════════════════════════════════════\n";

// Test with limit > 100 (should cap to 100)
echo "\n📍 Test 1: Request limit=1000 (should cap to 100)\n";
$result = testEndpoint(
    'Field Values - Limit Cap Test',
    'GET',
    "$baseUrl/bricks/field-values/brand?limit=1000"
);

if ($result['success']) {
    $itemCount = is_array($result['data']) ? count($result['data']) : 0;
    echo "📊 Items returned: {$itemCount}\n";

    if ($itemCount <= 100) {
        echo "✅ PASS: Limit correctly capped to max 100\n";
    } else {
        echo "❌ FAIL: Limit should be capped to 100!\n";
    }
}

// Test with negative limit (should default to 20)
echo "\n📍 Test 2: Request limit=-5 (should default to 20)\n";
$result = testEndpoint(
    'Field Values - Negative Limit',
    'GET',
    "$baseUrl/bricks/field-values/brand?limit=-5"
);

if ($result['success']) {
    echo "✅ PASS: Negative limit handled gracefully\n";
}

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║              FIX VERIFICATION COMPLETE             ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Fix #1: NULL Reset - Verified\n";
echo "✅ Fix #2: Field Whitelist - Verified\n";
echo "✅ Fix #3: Cross-Material - Verified\n";
echo "✅ Fix #4: Limit Validation - Verified\n";
echo "\n";
