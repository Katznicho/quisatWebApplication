#!/bin/bash

# Script to fix route issues on deployed server
# Run this on your server via SSH

echo "=== Clearing Laravel Caches ==="
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "=== Caching Routes for Production ==="
php artisan route:cache
php artisan config:cache

echo ""
echo "=== Listing Public Routes ==="
php artisan route:list | grep -E "(public|test|diagnostic|advertisements|kids-events|programs)"

echo ""
echo "=== Testing Routes Locally ==="
echo "Test route:"
curl -s http://localhost/api/v1/public/test | head -5

echo ""
echo "Diagnostic route:"
curl -s http://localhost/api/v1/public/diagnostic | head -5

echo ""
echo "=== Done ==="









