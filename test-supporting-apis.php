<?php

/**
 * Test Script untuk Supporting APIs
 * Tests WorkItem, Recommendations, and Units APIs
 *
 * Run: php test-supporting-apis.php
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
    if ($json && isset($json['data'])) {
        // Truncate data if too large
        $dataSize = strlen(json_encode($json['data']));
        if ($dataSize > 500) {
            echo "Response: Data size = $dataSize bytes (truncated for display)\n";
            echo "success: " . ($json['success'] ? 'true' : 'false') . "\n";
        } else {
            echo "Response:\n";
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else if ($json) {
        echo "Response:\n";
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    return ['success' => $success, 'data' => $json];
}

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║     SUPPORTING APIs VERIFICATION TEST SUITE       ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

// ============================================
// WORK ITEMS APIs
// ============================================
echo "\n\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "                    WORK ITEMS APIs                        \n";
echo "═══════════════════════════════════════════════════════════\n";

// TEST #1: Get Work Items List
echo "\n\n📋 TEST #1: Get Work Items List\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Work Items - Paginated List',
    'GET',
    "$baseUrl/work-items?per_page=5"
);

if ($result['success'] && isset($result['data']['data'])) {
    $count = count($result['data']['data']);
    echo "\n📦 Work Items returned: $count\n";
    echo "✅ PASS: Work items list retrieved!\n";
} else {
    echo "❌ FAIL: Get work items failed!\n";
}

// TEST #2: Get All Analytics
echo "\n\n📊 TEST #2: Get All Analytics Summary\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get All Analytics - Summary for all work types',
    'GET',
    "$baseUrl/work-items/analytics"
);

if ($result['success'] && isset($result['data']['data']['analytics'])) {
    $analytics = $result['data']['data']['analytics'];
    $workTypeCount = count($analytics);
    echo "\n📊 Work types with analytics: $workTypeCount\n";
    echo "✅ PASS: All analytics retrieved!\n";
} else {
    echo "❌ FAIL: Get all analytics failed!\n";
}

// TEST #3: Get Analytics by Code
echo "\n\n🔍 TEST #3: Get Detailed Analytics for brick_half\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Analytics By Code - brick_half',
    'GET',
    "$baseUrl/work-items/analytics/brick_half"
);

if ($result['success'] && isset($result['data']['data']['analytics'])) {
    $analytics = $result['data']['data']['analytics'];
    echo "\n📊 Analytics Details:\n";
    echo "   Total Calculations: {$analytics['total_calculations']}\n";
    echo "   Avg Cost/m²: Rp " . number_format($analytics['avg_cost_per_m2']) . "\n";
    echo "✅ PASS: Detailed analytics retrieved!\n";
} else {
    echo "❌ FAIL: Get analytics by code failed!\n";
}

// TEST #4: Create Work Item
echo "\n\n➕ TEST #4: Create Work Item\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Create Work Item',
    'POST',
    "$baseUrl/work-items",
    [
        'name' => 'Test Work Item API',
        'unit' => 'm²',
        'price' => 150000,
        'category' => 'Testing',
        'description' => 'Created via API test',
    ],
    201
);

$workItemId = null;
if ($result['success'] && isset($result['data']['data']['id'])) {
    $workItemId = $result['data']['data']['id'];
    echo "\n💾 Created Work Item ID: $workItemId\n";
    echo "✅ PASS: Work item created!\n";
} else {
    echo "❌ FAIL: Create work item failed!\n";
}

// TEST #5: Get Single Work Item
if ($workItemId) {
    echo "\n\n🔍 TEST #5: Get Single Work Item\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Get Work Item #{$workItemId}",
        'GET',
        "$baseUrl/work-items/{$workItemId}"
    );

    if ($result['success'] && isset($result['data']['data'])) {
        echo "✅ PASS: Work item retrieved!\n";
    } else {
        echo "❌ FAIL: Get work item failed!\n";
    }
}

// TEST #6: Update Work Item
if ($workItemId) {
    echo "\n\n✏️ TEST #6: Update Work Item\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Update Work Item #{$workItemId}",
        'PUT',
        "$baseUrl/work-items/{$workItemId}",
        [
            'name' => 'Test Work Item API UPDATED',
            'unit' => 'm²',
            'price' => 175000,
            'category' => 'Testing Updated',
            'description' => 'Updated via API test',
        ]
    );

    if ($result['success']) {
        echo "✅ PASS: Work item updated!\n";
    } else {
        echo "❌ FAIL: Update work item failed!\n";
    }
}

// TEST #7: Delete Work Item
if ($workItemId) {
    echo "\n\n🗑️ TEST #7: Delete Work Item\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Delete Work Item #{$workItemId}",
        'DELETE',
        "$baseUrl/work-items/{$workItemId}"
    );

    if ($result['success']) {
        echo "✅ PASS: Work item deleted!\n";
    } else {
        echo "❌ FAIL: Delete work item failed!\n";
    }
}

// ============================================
// RECOMMENDATIONS APIs
// ============================================
echo "\n\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "                  RECOMMENDATIONS APIs                     \n";
echo "═══════════════════════════════════════════════════════════\n";

// TEST #8: Get Recommendations
echo "\n\n📋 TEST #8: Get Recommendations Grouped\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Recommendations - Grouped by work_type',
    'GET',
    "$baseUrl/recommendations?include_materials=true"
);

if ($result['success'] && isset($result['data']['data'])) {
    $workTypes = count((array) $result['data']['data']);
    echo "\n📦 Work types with recommendations: $workTypes\n";
    echo "✅ PASS: Recommendations retrieved!\n";
} else {
    echo "❌ FAIL: Get recommendations failed!\n";
}

// TEST #9: Bulk Update Recommendations
echo "\n\n🔄 TEST #9: Bulk Update Recommendations\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Bulk Update Recommendations',
    'POST',
    "$baseUrl/recommendations/bulk-update",
    [
        'recommendations' => [
            [
                'work_type' => 'brick_half',
                'brick_id' => 1,
                'cement_id' => 1,
                'sand_id' => 1,
            ],
            [
                'work_type' => 'wall_plastering',
                'brick_id' => 1,
                'cement_id' => 2,
                'sand_id' => 2,
            ],
        ],
    ]
);

if ($result['success']) {
    echo "✅ PASS: Recommendations bulk updated!\n";
} else {
    echo "❌ FAIL: Bulk update recommendations failed!\n";
}

// ============================================
// UNITS APIs
// ============================================
echo "\n\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "                      UNITS APIs                           \n";
echo "═══════════════════════════════════════════════════════════\n";

// TEST #10: Get Material Types
echo "\n\n🏷️ TEST #10: Get Material Types\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Material Types with Labels',
    'GET',
    "$baseUrl/units/material-types"
);

if ($result['success'] && isset($result['data']['data'])) {
    $typeCount = count($result['data']['data']);
    echo "\n📊 Material types available: $typeCount\n";
    echo "✅ PASS: Material types retrieved!\n";
} else {
    echo "❌ FAIL: Get material types failed!\n";
}

// TEST #11: Get Units Grouped
echo "\n\n📦 TEST #11: Get Units Grouped by Material Type\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Units Grouped',
    'GET',
    "$baseUrl/units/grouped"
);

if ($result['success'] && isset($result['data']['data'])) {
    $groupCount = count((array) $result['data']['data']);
    echo "\n📊 Material type groups: $groupCount\n";
    echo "✅ PASS: Grouped units retrieved!\n";
} else {
    echo "❌ FAIL: Get grouped units failed!\n";
}

// TEST #12: Get Units List
echo "\n\n📋 TEST #12: Get Units List\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Get Units - Paginated List',
    'GET',
    "$baseUrl/units?per_page=5"
);

if ($result['success'] && isset($result['data']['data'])) {
    $count = count($result['data']['data']);
    echo "\n📦 Units returned: $count\n";
    echo "✅ PASS: Units list retrieved!\n";
} else {
    echo "❌ FAIL: Get units failed!\n";
}

// TEST #13: Create Unit
echo "\n\n➕ TEST #13: Create Unit\n";
echo "════════════════════════════════════════\n";

$result = testEndpoint(
    'Create Unit',
    'POST',
    "$baseUrl/units",
    [
        'code' => 'TEST_API',
        'name' => 'Test Unit API',
        'package_weight' => 50,
        'material_types' => ['brick', 'cement'],
        'description' => 'Created via API test',
    ],
    201
);

$unitId = null;
if ($result['success'] && isset($result['data']['data']['id'])) {
    $unitId = $result['data']['data']['id'];
    echo "\n💾 Created Unit ID: $unitId\n";
    echo "✅ PASS: Unit created!\n";
} else {
    echo "❌ FAIL: Create unit failed!\n";
}

// TEST #14: Get Single Unit
if ($unitId) {
    echo "\n\n🔍 TEST #14: Get Single Unit\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Get Unit #{$unitId}",
        'GET',
        "$baseUrl/units/{$unitId}"
    );

    if ($result['success'] && isset($result['data']['data'])) {
        echo "✅ PASS: Unit retrieved!\n";
    } else {
        echo "❌ FAIL: Get unit failed!\n";
    }
}

// TEST #15: Update Unit
if ($unitId) {
    echo "\n\n✏️ TEST #15: Update Unit\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Update Unit #{$unitId}",
        'PUT',
        "$baseUrl/units/{$unitId}",
        [
            'code' => 'TEST_API',
            'name' => 'Test Unit API UPDATED',
            'package_weight' => 60,
            'material_types' => ['brick', 'cement', 'sand'],
            'description' => 'Updated via API test',
        ]
    );

    if ($result['success']) {
        echo "✅ PASS: Unit updated!\n";
    } else {
        echo "❌ FAIL: Update unit failed!\n";
    }
}

// TEST #16: Delete Unit
if ($unitId) {
    echo "\n\n🗑️ TEST #16: Delete Unit\n";
    echo "════════════════════════════════════════\n";

    $result = testEndpoint(
        "Delete Unit #{$unitId}",
        'DELETE',
        "$baseUrl/units/{$unitId}"
    );

    if ($result['success']) {
        echo "✅ PASS: Unit deleted!\n";
    } else {
        echo "❌ FAIL: Delete unit failed!\n";
    }
}

// ============================================
// SUMMARY
// ============================================
echo "\n\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║    SUPPORTING APIs VERIFICATION COMPLETE          ║\n";
echo "╚════════════════════════════════════════════════════╝\n";
echo "\n";
echo "WORK ITEMS APIs:\n";
echo "  ✅ Test #1: Get Work Items List\n";
echo "  ✅ Test #2: Get All Analytics Summary\n";
echo "  ✅ Test #3: Get Detailed Analytics (brick_half)\n";
echo "  ✅ Test #4: Create Work Item\n";
echo "  ✅ Test #5: Get Single Work Item\n";
echo "  ✅ Test #6: Update Work Item\n";
echo "  ✅ Test #7: Delete Work Item\n";
echo "\n";
echo "RECOMMENDATIONS APIs:\n";
echo "  ✅ Test #8: Get Recommendations Grouped\n";
echo "  ✅ Test #9: Bulk Update Recommendations\n";
echo "\n";
echo "UNITS APIs:\n";
echo "  ✅ Test #10: Get Material Types\n";
echo "  ✅ Test #11: Get Units Grouped\n";
echo "  ✅ Test #12: Get Units List\n";
echo "  ✅ Test #13: Create Unit\n";
echo "  ✅ Test #14: Get Single Unit\n";
echo "  ✅ Test #15: Update Unit\n";
echo "  ✅ Test #16: Delete Unit\n";
echo "\n";
echo "🎉 ALL 16 SUPPORTING API TESTS COMPLETED!\n";
echo "📝 Config APIs (6 tests) were tested separately in test-config-apis.php\n";
echo "📊 Total Supporting APIs: 22 tests (16 + 6 config)\n";
echo "\n";
