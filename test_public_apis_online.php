<?php

/**
 * Test script for deployed public APIs
 * Run: php test_public_apis_online.php
 */

$baseUrl = 'https://app.quisat.com/api/v1/public';

$endpoints = [
    'Kids Events' => $baseUrl . '/kids-events?per_page=2',
    'Advertisements' => $baseUrl . '/advertisements?per_page=2',
    'Programs' => $baseUrl . '/programs?per_page=2',
];

echo "=== Testing Deployed Public APIs ===\n\n";

foreach ($endpoints as $name => $url) {
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Content-Type: $contentType\n";
    
    if ($error) {
        echo "✗ cURL Error: $error\n";
    } elseif ($httpCode === 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✓ Valid JSON response\n";
            if (isset($data['success'])) {
                echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
                if (isset($data['message'])) {
                    echo "  Message: " . $data['message'] . "\n";
                }
                if (isset($data['data'])) {
                    $dataKeys = array_keys($data['data']);
                    echo "  Data keys: " . implode(', ', $dataKeys) . "\n";
                    
                    // Show count of items
                    foreach ($dataKeys as $key) {
                        if (is_array($data['data'][$key])) {
                            $count = count($data['data'][$key]);
                            if ($count > 0 && !isset($data['data'][$key][0]) || isset($data['data'][$key][0])) {
                                echo "  $key count: " . (isset($data['data'][$key][0]) ? count($data['data'][$key]) : 'N/A') . "\n";
                            }
                        }
                    }
                }
                echo "  Response preview:\n";
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo "⚠ Response doesn't have 'success' field\n";
                echo "Response: " . substr($response, 0, 500) . "\n";
            }
        } else {
            echo "✗ Invalid JSON: " . json_last_error_msg() . "\n";
            echo "Response preview: " . substr($response, 0, 500) . "...\n";
        }
    } else {
        echo "✗ HTTP Error: $httpCode\n";
        echo "Response preview: " . substr($response, 0, 500) . "...\n";
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

echo "=== Test Complete ===\n";
echo "\nIf all tests pass, the APIs are working correctly!\n";
echo "If you see errors, check:\n";
echo "  1. Server is accessible\n";
echo "  2. Routes are properly deployed\n";
echo "  3. Database has data\n";
echo "  4. No server errors in logs\n";

