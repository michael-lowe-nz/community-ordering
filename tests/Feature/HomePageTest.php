<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_includes_search_copy_and_search_input(): void
    {
        Restaurant::factory()->count(6)->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('find your favourite spots')
            ->assertSee('Search for a spot');
    }
}
