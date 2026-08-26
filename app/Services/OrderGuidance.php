<?php

namespace App\Services;

final readonly class OrderGuidance
{
    /**
     * @param  array<int, array{category: string, count: int}>  $categoryBreakdown
     */
    public function __construct(
        public int $partySize,
        public array $categoryBreakdown,
        public int $totalDishes,
    ) {}
}
