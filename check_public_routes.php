<?php

/**
 * Diagnostic script to check if public routes are properly set up
 * Run on server: php check_public_routes.php
 */

echo "=== Public Routes Diagnostic ===\n\n";

// Check if controllers exist
$controllers = [
    'PublicKidsEventsController' => 'app/Http/Controllers/API/PublicKidsEventsController.php',
    'PublicAdvertisementsController' => 'app/Http/Controllers/API/PublicAdvertisementsController.php',
    'PublicProgramsController' => 'app/Http/Controllers/API/PublicProgramsController.php',
];

echo "1. Checking Controllers:\n";
foreach ($controllers as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ $name exists\n";
    } else {
        echo "   ✗ $name NOT FOUND at $path\n";
    }
}

// Check routes file
echo "\n2. Checking Routes File:\n";
$routesFile = 'routes/api.php';
if (file_exists($routesFile)) {
    $content = file_get_contents($routesFile);
    
    $checks = [
        'PublicKidsEventsController import' => strpos($content, 'PublicKidsEventsController') !== false,
        'PublicAdvertisementsController import' => strpos($content, 'PublicAdvertisementsController') !== false,
        'PublicProgramsController import' => strpos($content, 'PublicProgramsController') !== false,
        'public/kids-events route' => strpos($content, "public/kids-events") !== false,
        'public/advertisements route' => strpos($content, "public/advertisements") !== false,
        'public/programs route' => strpos($content, "public/programs") !== false,
    ];
    
    foreach ($checks as $check => $exists) {
        if ($exists) {
            echo "   ✓ $check found\n";
        } else {
            echo "   ✗ $check NOT FOUND\n";
        }
    }
} else {
    echo "   ✗ Routes file not found\n";
}

// Check route cache
echo "\n3. Checking Route Cache:\n";
$cacheFile = 'bootstrap/cache/routes-v7.php';
if (file_exists($cacheFile)) {
    echo "   ⚠ Route cache exists (may be stale)\n";
    echo "   Run: php artisan route:clear\n";
} else {
    echo "   ✓ No route cache (good for debugging)\n";
}

// Check if we can load Laravel
echo "\n4. Testing Laravel Bootstrap:\n";
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "   ✓ Laravel can be bootstrapped\n";
    
    // Try to get routes
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "   ✓ Kernel loaded\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "\nIf controllers and routes exist but still getting 404:\n";
echo "1. Run: php artisan route:clear\n";
echo "2. Run: composer dump-autoload\n";
echo "3. Run: php artisan route:cache\n";
echo "4. Check web server configuration (.htaccess or nginx)\n";

