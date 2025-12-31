<?php

/**
 * Quick API Test Script
 *
 * Run: php test-api.php
 */

$baseUrl = 'http://localhost:8000/api/v1';

function testEndpoint($method, $url, $data = null) {
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing: $method $url\n";
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
        return false;
    }

    $statusEmoji = $httpCode >= 200 && $httpCode < 300 ? '✅' : '❌';
    echo "$statusEmoji HTTP $httpCode\n";

    if ($response) {
        $json = json_decode($response, true);
        if ($json) {
            echo "Response:\n";
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "Raw Response:\n$response\n";
        }
    } else {
        echo "(Empty Response)\n";
    }

    return $httpCode >= 200 && $httpCode < 300;
}

echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║   MATERIAL DATABASE API TEST SUITE      ║\n";
echo "╚══════════════════════════════════════════╝\n";

// Test 1: Health Check
echo "\n📍 TEST 1: Health Check\n";
testEndpoint('GET', 'http://localhost:8000/api/test');

// Test 2: List Bricks
echo "\n📍 TEST 2: List Bricks\n";
testEndpoint('GET', "$baseUrl/bricks");

// Test 3: List Bricks with Pagination
echo "\n📍 TEST 3: List Bricks (Page 1, 5 per page)\n";
testEndpoint('GET', "$baseUrl/bricks?per_page=5");

// Test 4: Search Bricks
echo "\n📍 TEST 4: Search Bricks\n";
testEndpoint('GET', "$baseUrl/bricks?search=merah");

// Test 5: Get Single Brick (if exists)
echo "\n📍 TEST 5: Get Single Brick (ID: 1)\n";
testEndpoint('GET', "$baseUrl/bricks/1");

// Test 6: Autocomplete - Brand
echo "\n📍 TEST 6: Autocomplete - Brick Brands\n";
testEndpoint('GET', "$baseUrl/bricks/field-values/brand");

// Test 7: Get All Stores
echo "\n📍 TEST 7: Get All Stores\n";
testEndpoint('GET', "$baseUrl/bricks/all-stores");

// Test 8: List Cements
echo "\n📍 TEST 8: List Cements\n";
testEndpoint('GET', "$baseUrl/cements");

// Test 9: List Sands
echo "\n📍 TEST 9: List Sands\n";
testEndpoint('GET', "$baseUrl/sands");

// Test 10: List Cats
echo "\n📍 TEST 10: List Cats\n";
testEndpoint('GET', "$baseUrl/cats");

// Summary
echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║         TESTING COMPLETE                 ║\n";
echo "╚══════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Check results above\n";
echo "📝 For detailed testing, use Postman or see API-TESTING-GUIDE.md\n";
echo "\n";
