<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;
use Exception;

class PdfParsingService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extract text from a PDF file
     *
     * @param string $pdfPath Path to the PDF file
     * @return string Extracted text content
     * @throws Exception
     */
    public function extractTextFromPdf(string $pdfPath): string
    {
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $text = $pdf->getText();
            
            Log::info('PDF text extracted successfully', [
                'file' => $pdfPath,
                'text_length' => strlen($text)
            ]);
            
            return $text;
        } catch (Exception $e) {
            Log::error('Failed to extract text from PDF', [
                'file' => $pdfPath,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Failed to parse PDF: " . $e->getMessage());
        }
    }

    /**
     * Download PDF from URL and extract text
     *
     * @param string $url URL to download PDF from
     * @return string Extracted text content
     * @throws Exception
     */
    public function extractTextFromUrl(string $url): string
    {
        $tempFilePath = $this->downloadPdfToTemp($url);
        
        try {
            $text = $this->extractTextFromPdf($tempFilePath);
            return $text;
        } finally {
            $this->cleanupTempFile($tempFilePath);
        }
    }

    /**
     * Process PDF content from WebScrapingService
     *
     * @param array $scrapedContent Content array from WebScrapingService
     * @return array Parsed menu structure
     * @throws Exception
     */
    public function processScrapedPdfContent(array $scrapedContent): array
    {
        if ($scrapedContent['type'] !== 'pdf') {
            throw new Exception('Content type must be PDF');
        }

        $pdfPath = $scrapedContent['content'];
        
        try {
            $text = $this->extractTextFromPdf($pdfPath);
            $menuStructure = $this->parseMenuStructure($text);
            
            Log::info('PDF menu processed successfully', [
                'file' => $pdfPath,
                'items_found' => $menuStructure['total_items']
            ]);
            
            return $menuStructure;
        } finally {
            // Clean up the temporary PDF file
            $this->cleanupTempFile($pdfPath);
        }
    }

    /**
     * Parse menu structure from extracted PDF text
     *
     * @param string $text Raw text extracted from PDF
     * @return array Structured menu data
     */
    public function parseMenuStructure(string $text): array
    {
        $menuItems = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Try to detect menu items with prices
            $menuItem = $this->parseMenuItemLine($line);
            if ($menuItem) {
                $menuItems[] = $menuItem;
            }
        }
        
        return [
            'items' => $menuItems,
            'raw_text' => $text,
            'total_items' => count($menuItems)
        ];
    }

    /**
     * Parse a single line to extract menu item information
     *
     * @param string $line Text line to parse
     * @return array|null Menu item data or null if not a menu item
     */
    private function parseMenuItemLine(string $line): ?array
    {
        // Pattern to match menu items with prices
        // Examples: "Burger $12.99", "Pizza Margherita - $15.50", "Salad 8.99"
        $patterns = [
            '/^(.+?)\s*[\-\s]*\$?(\d+\.?\d*)\s*$/',  // Name followed by price
            '/^(.+?)\s+(\d+\.?\d*)\s*$/',            // Name followed by price (no $)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $name = trim($matches[1]);
                $price = floatval($matches[2]);
                
                // Skip if name is too short or price is 0
                if (strlen($name) < 3 || $price <= 0) {
                    continue;
                }
                
                return [
                    'name' => $name,
                    'price' => $price,
                    'description' => null,
                    'category' => null,
                    'raw_line' => $line
                ];
            }
        }
        
        return null;
    }

    /**
     * Download PDF from URL to temporary file
     *
     * @param string $url URL to download from
     * @return string Path to temporary file
     * @throws Exception
     */
    private function downloadPdfToTemp(string $url): string
    {
        try {
            $content = file_get_contents($url);
            
            if ($content === false) {
                throw new Exception("Failed to download PDF from URL: $url");
            }
            
            $tempFileName = 'pdf_' . uniqid() . '.pdf';
            $tempPath = storage_path('app/temp/' . $tempFileName);
            
            // Ensure temp directory exists
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            if (file_put_contents($tempPath, $content) === false) {
                throw new Exception("Failed to save PDF to temporary file");
            }
            
            Log::info('PDF downloaded to temporary file', [
                'url' => $url,
                'temp_path' => $tempPath,
                'size' => strlen($content)
            ]);
            
            return $tempPath;
        } catch (Exception $e) {
            Log::error('Failed to download PDF', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up temporary file
     *
     * @param string $filePath Path to temporary file
     */
    private function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
            Log::info('Temporary PDF file cleaned up', ['file' => $filePath]);
        }
    }

    /**
     * Validate if file is a valid PDF
     *
     * @param string $filePath Path to file
     * @return bool
     */
    public function isValidPdf(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 4);
        fclose($handle);
        
        return $header === '%PDF';
    }
}