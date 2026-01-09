<?php

namespace Tests\Unit\Services;

use App\Services\MenuContentValidationService;
use Tests\TestCase;

class MenuContentValidationServiceTest extends TestCase
{
    protected MenuContentValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MenuContentValidationService();
    }

    /** @test */
    public function it_validates_and_sanitizes_valid_menu_item()
    {
        $menuItem = [
            'name' => 'Cheeseburger',
            'description' => 'Delicious burger with cheese',
            'price' => 9.99,
            'section' => 'Burgers'
        ];

        $result = $this->validationService->validateAndSanitizeMenuItem($menuItem);

        $this->assertNotNull($result);
        $this->assertEquals('Cheeseburger', $result['name']);
        $this->assertEquals('Delicious burger with cheese', $result['description']);
        $this->assertEquals(9.99, $result['price']);
        $this->assertEquals('Burgers', $result['section']);
    }

    /** @test */
    public function it_sanitizes_html_in_menu_item()
    {
        $menuItem = [
            'name' => '<b>Cheeseburger</b>',
            'description' => '<script>alert("XSS")</script>Delicious burger with cheese',
            'price' => 9.99,
            'section' => '<div>Burgers</div>'
        ];

        $result = $this->validationService->validateAndSanitizeMenuItem($menuItem);

        $this->assertNotNull($result);
        $this->assertEquals('Cheeseburger', $result['name']);
        $this->assertEquals('Delicious burger with cheese', $result['description']);
        $this->assertEquals('Burgers', $result['section']);
    }

    /** @test */
    public function it_rejects_invalid_menu_item()
    {
        $menuItem = [
            'name' => '', // Empty name should be rejected
            'description' => 'Delicious burger with cheese',
            'price' => 9.99,
            'section' => 'Burgers'
        ];

        $result = $this->validationService->validateAndSanitizeMenuItem($menuItem);

        $this->assertNull($result);
    }

    /** @test */
    public function it_sanitizes_price_values()
    {
        $testCases = [
            ['input' => '$9.99', 'expected' => 9.99],
            ['input' => '10.50', 'expected' => 10.50],
            ['input' => '$12', 'expected' => 12.0],
            ['input' => 'Price: $15.99', 'expected' => 15.99],
            ['input' => 'invalid', 'expected' => null],
            ['input' => -5, 'expected' => null], // Negative prices should be rejected
            ['input' => 1000000, 'expected' => null], // Too large prices should be rejected
        ];

        foreach ($testCases as $case) {
            $result = $this->validationService->sanitizePrice($case['input']);
            $this->assertEquals($case['expected'], $result, "Failed for input: {$case['input']}");
        }
    }

    /** @test */
    public function it_validates_menu_structure()
    {
        $validMenu = [
            'items' => [
                ['name' => 'Item 1', 'price' => 9.99],
                ['name' => 'Item 2', 'price' => 12.99]
            ],
            'sections' => ['Main', 'Desserts']
        ];

        $invalidMenu = [
            'items' => []
        ];

        $missingItemsMenu = [
            'sections' => ['Main', 'Desserts']
        ];

        $invalidItemsMenu = [
            'items' => [
                ['price' => 9.99], // Missing name
                ['name' => 'Item 2', 'price' => 12.99]
            ]
        ];

        $validResult = $this->validationService->validateMenuStructure($validMenu);
        $invalidResult = $this->validationService->validateMenuStructure($invalidMenu);
        $missingItemsResult = $this->validationService->validateMenuStructure($missingItemsMenu);
        $invalidItemsResult = $this->validationService->validateMenuStructure($invalidItemsMenu);

        $this->assertTrue($validResult['is_valid']);
        $this->assertFalse($invalidResult['is_valid']);
        $this->assertFalse($missingItemsResult['is_valid']);
        $this->assertFalse($invalidItemsResult['is_valid']);
    }

    /** @test */
    public function it_detects_suspicious_content()
    {
        $normalContent = "This is a normal menu item description";
        $xssContent = "Burger <script>alert('XSS')</script>";
        $sqlInjectionContent = "Burger'; DROP TABLE users; --";
        $excessiveSpecialCharsContent = "!@#$%^&*()_+{}|:<>?~`-=[]\\;',./!@#$%^&*()_+{}|:<>?~";

        $normalResult = $this->validationService->detectSuspiciousContent($normalContent);
        $xssResult = $this->validationService->detectSuspiciousContent($xssContent);
        $sqlResult = $this->validationService->detectSuspiciousContent($sqlInjectionContent);
        $specialCharsResult = $this->validationService->detectSuspiciousContent($excessiveSpecialCharsContent);

        $this->assertFalse($normalResult['is_suspicious']);
        $this->assertTrue($xssResult['is_suspicious']);
        $this->assertTrue($sqlResult['is_suspicious']);
        $this->assertTrue($specialCharsResult['is_suspicious']);
    }

    /** @test */
    public function it_validates_and_sanitizes_multiple_menu_items()
    {
        $menuItems = [
            [
                'name' => 'Cheeseburger',
                'description' => 'Delicious burger with cheese',
                'price' => 9.99,
                'section' => 'Burgers'
            ],
            [
                'name' => '<b>Fries</b>',
                'description' => '<script>alert("XSS")</script>Crispy fries',
                'price' => 4.99,
                'section' => 'Sides'
            ],
            [
                'name' => '', // Invalid item
                'description' => 'Missing name',
                'price' => 2.99,
                'section' => 'Sides'
            ]
        ];

        $result = $this->validationService->validateAndSanitizeMenuItems($menuItems);

        $this->assertEquals(2, $result['total_valid']);
        $this->assertEquals(1, $result['total_invalid']);
        $this->assertEquals('Cheeseburger', $result['items'][0]['name']);
        $this->assertEquals('Fries', $result['items'][1]['name']);
        $this->assertEquals('Crispy fries', $result['items'][1]['description']);
    }
}