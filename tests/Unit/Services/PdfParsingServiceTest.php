<?php

namespace Tests\Unit\Services;

use App\Services\PdfParsingService;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class PdfParsingServiceTest extends TestCase
{
    private PdfParsingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PdfParsingService();
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(PdfParsingService::class, $this->service);
    }

    public function test_parse_menu_structure_with_empty_text()
    {
        $result = $this->service->parseMenuStructure('');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('raw_text', $result);
        $this->assertArrayHasKey('total_items', $result);
        $this->assertEquals(0, $result['total_items']);
        $this->assertEmpty($result['items']);
    }

    public function test_parse_menu_structure_with_sample_text()
    {
        $sampleText = "Burger $12.99\nPizza Margherita - $15.50\nCaesar Salad 8.99\n\nInvalid line\nShort $0";
        
        $result = $this->service->parseMenuStructure($sampleText);
        
        $this->assertIsArray($result);
        $this->assertEquals(3, $result['total_items']);
        $this->assertCount(3, $result['items']);
        
        // Check first item
        $firstItem = $result['items'][0];
        $this->assertEquals('Burger', $firstItem['name']);
        $this->assertEquals(12.99, $firstItem['price']);
        $this->assertNull($firstItem['description']);
        $this->assertNull($firstItem['category']);
        
        // Check second item
        $secondItem = $result['items'][1];
        $this->assertEquals('Pizza Margherita', $secondItem['name']);
        $this->assertEquals(15.50, $secondItem['price']);
        
        // Check third item
        $thirdItem = $result['items'][2];
        $this->assertEquals('Caesar Salad', $thirdItem['name']);
        $this->assertEquals(8.99, $thirdItem['price']);
    }

    public function test_parse_menu_item_line_with_dollar_sign()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Cheeseburger $14.99');
        
        $this->assertIsArray($result);
        $this->assertEquals('Cheeseburger', $result['name']);
        $this->assertEquals(14.99, $result['price']);
        $this->assertEquals('Cheeseburger $14.99', $result['raw_line']);
    }

    public function test_parse_menu_item_line_without_dollar_sign()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Fish and Chips 16.50');
        
        $this->assertIsArray($result);
        $this->assertEquals('Fish and Chips', $result['name']);
        $this->assertEquals(16.50, $result['price']);
    }

    public function test_parse_menu_item_line_with_dash_separator()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Grilled Chicken - $18.00');
        
        $this->assertIsArray($result);
        $this->assertEquals('Grilled Chicken', $result['name']);
        $this->assertEquals(18.00, $result['price']);
    }

    public function test_parse_menu_item_line_invalid_format()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Just some text without price');
        
        $this->assertNull($result);
    }

    public function test_parse_menu_item_line_short_name()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'AB $10.00');
        
        $this->assertNull($result);
    }

    public function test_parse_menu_item_line_zero_price()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseMenuItemLine');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->service, 'Free Item $0.00');
        
        $this->assertNull($result);
    }

    public function test_is_valid_pdf_with_nonexistent_file()
    {
        $result = $this->service->isValidPdf('/nonexistent/file.pdf');
        
        $this->assertFalse($result);
    }

    public function test_cleanup_temp_file()
    {
        // Create a temporary file
        $tempPath = storage_path('app/temp/test_cleanup.txt');
        
        // Ensure temp directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        file_put_contents($tempPath, 'test content');
        $this->assertTrue(file_exists($tempPath));
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('cleanupTempFile');
        $method->setAccessible(true);
        
        $method->invoke($this->service, $tempPath);
        
        $this->assertFalse(file_exists($tempPath));
    }

    public function test_extract_text_from_pdf_with_invalid_file()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Failed to parse PDF/');
        
        $this->service->extractTextFromPdf('/nonexistent/file.pdf');
    }

    public function test_download_pdf_to_temp_with_invalid_url()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('downloadPdfToTemp');
        $method->setAccessible(true);
        
        $this->expectException(Exception::class);
        
        $method->invoke($this->service, 'http://invalid-url-that-does-not-exist.com/file.pdf');
    }
}