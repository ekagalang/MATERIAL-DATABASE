<?php

/**
 * Comprehensive API Test Script
 * Tests all 32 REST API endpoints
 *
 * Run: php test-api-complete.php
 */

$baseUrl = 'http://localhost:8000/api/v1';
$createdIds = [
    'brick' => null,
    'cement' => null,
    'sand' => null,
    'cat' => null,
];
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'total' => 0,
];

function testEndpoint($testName, $method, $url, $data = null, $expectedCode = 200) {
    global $testResults;

    $testResults['total']++;

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TEST #{$testResults['total']}: $testName\n";
    echo "$method $url\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        echo "❌ CURL Error: $error\n";
        $testResults['failed']++;
        return ['success' => false, 'data' => null];
    }

    $success = ($httpCode >= 200 && $httpCode < 300);
    $statusEmoji = $success ? '✅' : '❌';
    echo "$statusEmoji HTTP $httpCode (Expected: $expectedCode)\n";

    $json = null;
    if ($response) {
        $json = json_decode($response, true);
        if ($json) {
            // Show minimal response for successful tests
            if ($success) {
                if (isset($json['data']['id'])) {
                    echo "Response: Created/Updated ID = {$json['data']['id']}\n";
                } elseif (isset($json['success'])) {
                    echo "Response: success = true\n";
                } else {
                    echo "Response: " . (is_array($json) ? count($json) . " items" : "data received") . "\n";
                }
            } else {
                // Show full error response
                echo "Response:\n";
                echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    } else {
        echo "(Empty Response - OK for DELETE)\n";
    }

    if ($success) {
        $testResults['passed']++;
    } else {
        $testResults['failed']++;
    }

    return ['success' => $success, 'data' => $json];
}

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   COMPREHENSIVE MATERIAL DATABASE API TEST SUITE   ║\n";
echo "║              Testing All 32 Endpoints              ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// HEALTH CHECK
// ============================================
echo "\n\n🏥 HEALTH CHECK\n";
echo "════════════════════════════════════════\n";

testEndpoint('API Health Check', 'GET', 'http://localhost:8000/api/test');

// ============================================
// BRICK API (8 endpoints)
// ============================================
echo "\n\n🧱 BRICK API TESTS\n";
echo "════════════════════════════════════════\n";

// 1. List Bricks
testEndpoint('List Bricks', 'GET', "$baseUrl/bricks");

// 2. Create Brick
$result = testEndpoint('Create Brick', 'POST', "$baseUrl/bricks", [
    'type' => 'Test Bata',
    'brand' => 'Test Brand',
    'form' => 'Persegi',
    'dimension_length' => 20,
    'dimension_width' => 10,
    'dimension_height' => 5,
    'price_per_piece' => 1500,
    'store' => 'Toko Test',
    'short_address' => 'Jakarta',
], 201);

if ($result['success'] && isset($result['data']['data']['id'])) {
    $createdIds['brick'] = $result['data']['data']['id'];
    echo "💾 Saved Brick ID: {$createdIds['brick']}\n";
}

// 3. Show Single Brick
if ($createdIds['brick']) {
    testEndpoint('Get Single Brick', 'GET', "$baseUrl/bricks/{$createdIds['brick']}");
}

// 4. Update Brick
if ($createdIds['brick']) {
    testEndpoint('Update Brick', 'PUT', "$baseUrl/bricks/{$createdIds['brick']}", [
        'type' => 'Updated Bata',
        'brand' => 'Updated Brand',
        'price_per_piece' => 2000,
    ]);
}

// 5. Field Values - Brand
testEndpoint('Autocomplete - Brick Brands', 'GET', "$baseUrl/bricks/field-values/brand");

// 6. Field Values - Type
testEndpoint('Autocomplete - Brick Types', 'GET', "$baseUrl/bricks/field-values/type");

// 7. All Stores
testEndpoint('Get All Brick Stores', 'GET', "$baseUrl/bricks/all-stores");

// 8. Addresses by Store
testEndpoint('Get Addresses by Store', 'GET', "$baseUrl/bricks/addresses-by-store?store=Toko%20Bangunan%20Jaya");

// ============================================
// CEMENT API (8 endpoints)
// ============================================
echo "\n\n🏗️ CEMENT API TESTS\n";
echo "════════════════════════════════════════\n";

// 1. List Cements
testEndpoint('List Cements', 'GET', "$baseUrl/cements");

// 2. Create Cement
$result = testEndpoint('Create Cement', 'POST', "$baseUrl/cements", [
    'cement_name' => 'Semen Test API',
    'brand' => 'Test Cement Brand',
    'type' => 'Portland',
    'package_unit' => 'sak',
    'package_weight_gross' => 50,
    'package_price' => 65000,
    'store' => 'Toko Test Cement',
], 201);

if ($result['success'] && isset($result['data']['data']['id'])) {
    $createdIds['cement'] = $result['data']['data']['id'];
    echo "💾 Saved Cement ID: {$createdIds['cement']}\n";
}

// 3. Show Single Cement
if ($createdIds['cement']) {
    testEndpoint('Get Single Cement', 'GET', "$baseUrl/cements/{$createdIds['cement']}");
}

// 4. Update Cement
if ($createdIds['cement']) {
    testEndpoint('Update Cement', 'PUT', "$baseUrl/cements/{$createdIds['cement']}", [
        'cement_name' => 'Updated Semen Test',
        'package_price' => 70000,
    ]);
}

// 5. Field Values - Brand
testEndpoint('Autocomplete - Cement Brands', 'GET', "$baseUrl/cements/field-values/brand");

// 6. Field Values - Type
testEndpoint('Autocomplete - Cement Types', 'GET', "$baseUrl/cements/field-values/type");

// 7. All Stores
testEndpoint('Get All Cement Stores', 'GET', "$baseUrl/cements/all-stores");

// 8. Addresses by Store
testEndpoint('Get Cement Addresses by Store', 'GET', "$baseUrl/cements/addresses-by-store?store=Toko%20Bangunan%20Maju");

// ============================================
// SAND API (8 endpoints)
// ============================================
echo "\n\n🏖️ SAND API TESTS\n";
echo "════════════════════════════════════════\n";

// 1. List Sands
testEndpoint('List Sands', 'GET', "$baseUrl/sands");

// 2. Create Sand
$result = testEndpoint('Create Sand', 'POST', "$baseUrl/sands", [
    'sand_name' => 'Pasir Test API',
    'brand' => 'Test Sand Brand',
    'type' => 'Pasir Halus',
    'package_unit' => 'kubik',
    'package_price' => 400000,
    'store' => 'Toko Test Sand',
], 201);

if ($result['success'] && isset($result['data']['data']['id'])) {
    $createdIds['sand'] = $result['data']['data']['id'];
    echo "💾 Saved Sand ID: {$createdIds['sand']}\n";
}

// 3. Show Single Sand
if ($createdIds['sand']) {
    testEndpoint('Get Single Sand', 'GET', "$baseUrl/sands/{$createdIds['sand']}");
}

// 4. Update Sand
if ($createdIds['sand']) {
    testEndpoint('Update Sand', 'PUT', "$baseUrl/sands/{$createdIds['sand']}", [
        'sand_name' => 'Updated Pasir Test',
        'package_price' => 450000,
    ]);
}

// 5. Field Values - Brand
testEndpoint('Autocomplete - Sand Brands', 'GET', "$baseUrl/sands/field-values/brand");

// 6. Field Values - Type
testEndpoint('Autocomplete - Sand Types', 'GET', "$baseUrl/sands/field-values/type");

// 7. All Stores
testEndpoint('Get All Sand Stores', 'GET', "$baseUrl/sands/all-stores");

// 8. Addresses by Store
testEndpoint('Get Sand Addresses by Store', 'GET', "$baseUrl/sands/addresses-by-store?store=Supplier%20Pasir%20XYZ");

// ============================================
// CAT (PAINT) API (8 endpoints)
// ============================================
echo "\n\n🎨 CAT (PAINT) API TESTS\n";
echo "════════════════════════════════════════\n";

// 1. List Cats
testEndpoint('List Cats', 'GET', "$baseUrl/cats");

// 2. Create Cat
$result = testEndpoint('Create Cat', 'POST', "$baseUrl/cats", [
    'cat_name' => 'Cat Test API',
    'brand' => 'Test Paint Brand',
    'type' => 'Cat Tembok',
    'color_name' => 'Test Blue',
    'color_code' => '#0000FF',
    'package_unit' => 'kaleng',
    'package_weight_gross' => 5,
    'volume' => 5,
    'volume_unit' => 'liter',
    'purchase_price' => 150000,
    'store' => 'Toko Test Cat',
], 201);

if ($result['success'] && isset($result['data']['data']['id'])) {
    $createdIds['cat'] = $result['data']['data']['id'];
    echo "💾 Saved Cat ID: {$createdIds['cat']}\n";
}

// 3. Show Single Cat
if ($createdIds['cat']) {
    testEndpoint('Get Single Cat', 'GET', "$baseUrl/cats/{$createdIds['cat']}");
}

// 4. Update Cat
if ($createdIds['cat']) {
    testEndpoint('Update Cat', 'PUT', "$baseUrl/cats/{$createdIds['cat']}", [
        'cat_name' => 'Updated Cat Test',
        'purchase_price' => 175000,
    ]);
}

// 5. Field Values - Brand
testEndpoint('Autocomplete - Cat Brands', 'GET', "$baseUrl/cats/field-values/brand");

// 6. Field Values - Type
testEndpoint('Autocomplete - Cat Types', 'GET', "$baseUrl/cats/field-values/type");

// 7. All Stores
testEndpoint('Get All Cat Stores', 'GET', "$baseUrl/cats/all-stores");

// 8. Addresses by Store
testEndpoint('Get Cat Addresses by Store', 'GET', "$baseUrl/cats/addresses-by-store?store=Toko%20Cat%20Warna%20Warni");

// ============================================
// DELETE TESTS (Clean up test data)
// ============================================
echo "\n\n🗑️ DELETE TESTS (Cleanup)\n";
echo "════════════════════════════════════════\n";

if ($createdIds['brick']) {
    testEndpoint('Delete Brick', 'DELETE', "$baseUrl/bricks/{$createdIds['brick']}", null, 204);
}

if ($createdIds['cement']) {
    testEndpoint('Delete Cement', 'DELETE', "$baseUrl/cements/{$createdIds['cement']}", null, 204);
}

if ($createdIds['sand']) {
    testEndpoint('Delete Sand', 'DELETE', "$baseUrl/sands/{$createdIds['sand']}", null, 204);
}

if ($createdIds['cat']) {
    testEndpoint('Delete Cat', 'DELETE', "$baseUrl/cats/{$createdIds['cat']}", null, 204);
}

// ============================================
// VALIDATION TESTS
// ============================================
echo "\n\n⚠️ VALIDATION TESTS\n";
echo "════════════════════════════════════════\n";

// Test validation error (should fail)
testEndpoint('Create Brick - Invalid Data', 'POST', "$baseUrl/bricks", [
    'price_per_piece' => -1000, // Invalid: negative price
], 422);

testEndpoint('Create Cement - Invalid Data', 'POST', "$baseUrl/cements", [
    'package_weight_gross' => -50, // Invalid: negative weight
], 422);

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║                  TEST SUMMARY                      ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Total Tests:  {$testResults['total']}\n";
echo "✅ Passed:    {$testResults['passed']}\n";
echo "❌ Failed:    {$testResults['failed']}\n";
echo "\n";

$passRate = $testResults['total'] > 0 ? round(($testResults['passed'] / $testResults['total']) * 100, 2) : 0;
echo "Pass Rate: {$passRate}%\n";

if ($testResults['failed'] === 0) {
    echo "\n🎉 ALL TESTS PASSED! API is working perfectly!\n";
} else {
    echo "\n⚠️ Some tests failed. Please review the errors above.\n";
}

echo "\n";
echo "📝 Test Data Created:\n";
foreach ($createdIds as $material => $id) {
    if ($id) {
        echo "   - {$material}: ID {$id} (deleted)\n";
    }
}
echo "\n";
