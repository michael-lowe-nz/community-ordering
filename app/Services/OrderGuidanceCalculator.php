<?php

namespace App\Services;

use App\Models\Restaurant;

interface OrderGuidanceCalculator
{
    public function calculate(Restaurant $restaurant, int $partySize): OrderGuidance;
}
