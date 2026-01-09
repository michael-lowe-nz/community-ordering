<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\MenuStorageService;
use App\Models\Restaurant;

try {
    $service = new MenuStorageService();
    echo "MenuStorageService instantiated successfully\n";
    
    // Test the normalize method
    $reflection = new ReflectionClass($service);
    $normalizeMethod = $reflection->getMethod('normalizeItemName');
    $normalizeMethod->setAccessible(true);
    
    $result = $normalizeMethod->invoke($service, 'Chicken Burger Deluxe');
    echo "Normalized 'Chicken Burger Deluxe': '$result'\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}