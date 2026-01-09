<?php

namespace Tests\Unit\Services;

use App\Services\PdfParsingService;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class PdfParsingServiceIntegrationTest extends TestCase
{
    private PdfParsingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PdfParsingService();
    }

    public function test_complete_menu_parsing_workflow()
    {
        // Sample menu text that would be extracted from a PDF
        $sampleMenuText = "
        APPETIZERS
        
        Buffalo Wings $12.99
        Mozzarella Sticks - $8.50
        Nachos Supreme 14.00
        
        MAIN COURSES
        
        Grilled Salmon $24.99
        Ribeye Steak - $32.00
        Chicken Parmesan 18.50
        Vegetarian Pasta $16.99
        
        DESSERTS
        
        Chocolate Cake $7.99
        Ice Cream Sundae - $5.50
        ";

        $result = $this->service->parseMenuStructure($sampleMenuText);

        // Verify the structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('raw_text', $result);
        $this->assertArrayHasKey('total_items', $result);

        // Should find 9 menu items (3 appetizers + 4 main courses + 2 desserts)
        $this->assertEquals(9, $result['total_items']);
        $this->assertCount(9, $result['items']);

        // Verify specific items
        $items = $result['items'];

        // Check Buffalo Wings
        $buffaloWings = collect($items)->firstWhere('name', 'Buffalo Wings');
        $this->assertNotNull($buffaloWings);
        $this->assertEquals(12.99, $buffaloWings['price']);

        // Check Ribeye Steak
        $ribeyeSteak = collect($items)->firstWhere('name', 'Ribeye Steak');
        $this->assertNotNull($ribeyeSteak);
        $this->assertEquals(32.00, $ribeyeSteak['price']);

        // Check Vegetarian Pasta
        $vegPasta = collect($items)->firstWhere('name', 'Vegetarian Pasta');
        $this->assertNotNull($vegPasta);
        $this->assertEquals(16.99, $vegPasta['price']);

        // Verify all items have required fields
        foreach ($items as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('price', $item);
            $this->assertArrayHasKey('description', $item);
            $this->assertArrayHasKey('category', $item);
            $this->assertArrayHasKey('raw_line', $item);

            $this->assertIsString($item['name']);
            $this->assertIsFloat($item['price']);
            $this->assertGreaterThan(0, $item['price']);
            $this->assertGreaterThan(2, strlen($item['name']));
        }
    }

    public function test_edge_cases_in_menu_parsing()
    {
        $edgeCaseText = "
        Valid Item $10.99
        
        AB $15.00
        Free Item $0.00
        No Price Item
        Just text here
        Another Valid Item - $25.50
        Item with decimal 12.75
        ";

        $result = $this->service->parseMenuStructure($edgeCaseText);

        // Should only find valid items
        $this->assertEquals(3, $result['total_items']);

        $itemNames = collect($result['items'])->pluck('name')->toArray();
        $this->assertContains('Valid Item', $itemNames);
        $this->assertContains('Another Valid Item', $itemNames);
        $this->assertContains('Item with decimal', $itemNames);

        // Should not contain invalid items
        $this->assertNotContains('Too Short', $itemNames);
        $this->assertNotContains('AB', $itemNames);
        $this->assertNotContains('Free Item', $itemNames);
    }

    public function test_price_format_variations()
    {
        $priceVariationsText = "
        Item One $12.99
        Item Two - $15.50
        Item Three 18.00
        Item Four - 22.75
        Item Five $8
        Item Six 10
        ";

        $result = $this->service->parseMenuStructure($priceVariationsText);

        $this->assertEquals(6, $result['total_items']);

        $items = collect($result['items']);

        $this->assertEquals(12.99, $items->firstWhere('name', 'Item One')['price']);
        $this->assertEquals(15.50, $items->firstWhere('name', 'Item Two')['price']);
        $this->assertEquals(18.00, $items->firstWhere('name', 'Item Three')['price']);
        $this->assertEquals(22.75, $items->firstWhere('name', 'Item Four')['price']);
        $this->assertEquals(8.00, $items->firstWhere('name', 'Item Five')['price']);
        $this->assertEquals(10.00, $items->firstWhere('name', 'Item Six')['price']);
    }
}
