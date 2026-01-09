<?php

require_once 'vendor/autoload.php';

use App\Services\MenuStorageService;

$service = new MenuStorageService();

// Use reflection to access private methods
$reflection = new ReflectionClass($service);

$normalizeMethod = $reflection->getMethod('normalizeItemName');
$normalizeMethod->setAccessible(true);

$similarityMethod = $reflection->getMethod('calculateSimilarity');
$similarityMethod->setAccessible(true);

$name1 = 'Chicken Burger';
$name2 = 'Chicken Burger Deluxe';

$normalized1 = $normalizeMethod->invoke($service, $name1);
$normalized2 = $normalizeMethod->invoke($service, $name2);

echo "Original 1: $name1\n";
echo "Normalized 1: $normalized1\n";
echo "Original 2: $name2\n";
echo "Normalized 2: $normalized2\n";

$similarity = $similarityMethod->invoke($service, $normalized1, $normalized2);
echo "Similarity: $similarity\n";

echo "Contains check 1: " . (str_contains($normalized2, $normalized1) ? 'true' : 'false') . "\n";
echo "Contains check 2: " . (str_contains($normalized1, $normalized2) ? 'true' : 'false') . "\n";