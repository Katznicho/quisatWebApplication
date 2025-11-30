#!/bin/bash

echo "=== Testing Deployed Public APIs ==="
echo ""

BASE_URL="https://app.quisat.com/api/v1/public"

echo "1. Testing Kids Events API..."
echo "   URL: ${BASE_URL}/kids-events"
response=$(curl -s -w "\nHTTP_CODE:%{http_code}" "${BASE_URL}/kids-events?per_page=2")
http_code=$(echo "$response" | grep "HTTP_CODE" | cut -d: -f2)
body=$(echo "$response" | sed '/HTTP_CODE/d')

echo "   HTTP Status: $http_code"
if [ "$http_code" = "200" ]; then
    echo "   ✓ Status OK"
    if echo "$body" | grep -q "success"; then
        echo "   ✓ Response contains 'success'"
        if echo "$body" | python3 -m json.tool > /dev/null 2>&1; then
            echo "   ✓ Valid JSON"
            echo "   Response preview:"
            echo "$body" | python3 -m json.tool | head -20
        else
            echo "   ✗ Invalid JSON"
            echo "   Response: ${body:0:200}"
        fi
    else
        echo "   ⚠ Response doesn't contain 'success'"
        echo "   Response: ${body:0:200}"
    fi
else
    echo "   ✗ HTTP Error: $http_code"
    echo "   Response: ${body:0:200}"
fi

echo ""
echo "2. Testing Advertisements API..."
echo "   URL: ${BASE_URL}/advertisements"
response=$(curl -s -w "\nHTTP_CODE:%{http_code}" "${BASE_URL}/advertisements?per_page=2")
http_code=$(echo "$response" | grep "HTTP_CODE" | cut -d: -f2)
body=$(echo "$response" | sed '/HTTP_CODE/d')

echo "   HTTP Status: $http_code"
if [ "$http_code" = "200" ]; then
    echo "   ✓ Status OK"
    if echo "$body" | grep -q "success"; then
        echo "   ✓ Response contains 'success'"
        if echo "$body" | python3 -m json.tool > /dev/null 2>&1; then
            echo "   ✓ Valid JSON"
            echo "   Response preview:"
            echo "$body" | python3 -m json.tool | head -20
        else
            echo "   ✗ Invalid JSON"
            echo "   Response: ${body:0:200}"
        fi
    else
        echo "   ⚠ Response doesn't contain 'success'"
        echo "   Response: ${body:0:200}"
    fi
else
    echo "   ✗ HTTP Error: $http_code"
    echo "   Response: ${body:0:200}"
fi

echo ""
echo "3. Testing Programs API..."
echo "   URL: ${BASE_URL}/programs"
response=$(curl -s -w "\nHTTP_CODE:%{http_code}" "${BASE_URL}/programs?per_page=2")
http_code=$(echo "$response" | grep "HTTP_CODE" | cut -d: -f2)
body=$(echo "$response" | sed '/HTTP_CODE/d')

echo "   HTTP Status: $http_code"
if [ "$http_code" = "200" ]; then
    echo "   ✓ Status OK"
    if echo "$body" | grep -q "success"; then
        echo "   ✓ Response contains 'success'"
        if echo "$body" | python3 -m json.tool > /dev/null 2>&1; then
            echo "   ✓ Valid JSON"
            echo "   Response preview:"
            echo "$body" | python3 -m json.tool | head -20
        else
            echo "   ✗ Invalid JSON"
            echo "   Response: ${body:0:200}"
        fi
    else
        echo "   ⚠ Response doesn't contain 'success'"
        echo "   Response: ${body:0:200}"
    fi
else
    echo "   ✗ HTTP Error: $http_code"
    echo "   Response: ${body:0:200}"
fi

echo ""
echo "=== Test Complete ==="

