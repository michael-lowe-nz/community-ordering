<?php

namespace Tests\Unit\Models;

use App\Models\Restaurant;
use Tests\TestCase;

class RestaurantValidationTest extends TestCase
{
    public function test_it_has_correct_validation_rules(): void
    {
        $rules = Restaurant::validationRules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('required', $rules['name']);
    }
}
