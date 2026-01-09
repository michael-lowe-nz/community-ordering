<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Exception;

class MenuParsingService implements MenuParsingServiceInterface
{
    protected PdfParsingService $pdfParsingService;
    protected array $siteSpecificParsers = [];
    
    // Maximum allowed length for text fields
    protected const MAX_NAME_LENGTH = 255;
    protected const MAX_DESCRIPTION_LENGTH = 1000;
    protected const MAX_SECTION_LENGTH = 100;

    public function __construct(PdfParsingService $pdfParsingService)
    {
        $this->pdfParsingService = $pdfParsingService;
        $this->initializeSiteSpecificParsers();
    }

    public function parseMenuContent(string $content, string $url, string $contentType = 'html'): array
    {
        try {
            if ($contentType === 'pdf') {
                return $this->parsePdfMenu($content);
            }

            $format = $this->detectMenuFormat($content, $contentType);

            Log::info('Parsing menu content', [
                'url' => $url,
                'content_type' => $contentType,
                'detected_format' => $format,
                'content_length' => strlen($content)
            ]);

            // Try site-specific parser first
            if (isset($this->siteSpecificParsers[$format])) {
                $parser = $this->siteSpecificParsers[$format];
                $result = $parser($content, $url);

                if (!empty($result['items'])) {
                    Log::info('Site-specific parser successful', [
                        'format' => $format,
                        'items_found' => count($result['items'])
                    ]);
                    return $result;
                }
            }

            // Fall back to generic HTML parsing
            return $this->parseGenericHtml($content, $url);
        } catch (Exception $e) {
            Log::error('Menu parsing failed', [
                'url' => $url,
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);

            return [
                'items' => [],
                'sections' => [],
                'total_items' => 0,
                'parsing_errors' => [$e->getMessage()]
            ];
        }
    }

    public function detectMenuFormat(string $content, string $contentType): string
    {
        if ($contentType === 'pdf') {
            return 'pdf';
        }

        // Check for common menu platforms and formats
        $formatIndicators = [
            'squarespace' => ['squarespace-commerce', 'squarespace.com', 'sqs-'],
            'wix' => ['wix.com', 'wixstatic.com', 'wix-'],
            'wordpress' => ['wp-content', 'wordpress', 'wp-'],
            'shopify' => ['shopify.com', 'shopifycdn.com', 'shopify-'],
            'toast' => ['toasttab.com', 'toast-pos', 'toast-menu-container', 'toast-item'],
            'resy' => ['resy.com', 'resy-'],
            'opentable' => ['opentable.com', 'ot-'],
            'generic_menu' => ['menu-item', 'food-item', 'dish-'],
            'price_list' => ['price-list', 'pricing'],
        ];

        $contentLower = strtolower($content);

        foreach ($formatIndicators as $format => $indicators) {
            foreach ($indicators as $indicator) {
                if (strpos($contentLower, $indicator) !== false) {
                    return $format;
                }
            }
        }

        return 'generic';
    }

    public function getSupportedFormats(): array
    {
        return ['html', 'pdf'];
    }

    public function parsePdfMenu(string $pdfPath): array
    {
        try {
            $text = $this->pdfParsingService->extractTextFromPdf($pdfPath);
            return $this->parseMenuFromText($text);
        } catch (Exception $e) {
            Log::error('PDF menu parsing failed', [
                'pdf_path' => $pdfPath,
                'error' => $e->getMessage()
            ]);

            return [
                'items' => [],
                'sections' => [],
                'total_items' => 0,
                'parsing_errors' => [$e->getMessage()]
            ];
        }
    }

    protected function initializeSiteSpecificParsers(): void
    {
        $this->siteSpecificParsers = [
            'squarespace' => [$this, 'parseSquarespace'],
            'wix' => [$this, 'parseWix'],
            'wordpress' => [$this, 'parseWordPress'],
            'toast' => [$this, 'parseToast'],
            'generic_menu' => [$this, 'parseGenericMenuStructure'],
        ];
    }

    protected function parseGenericHtml(string $content, string $url): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $menuItems = [];
        $sections = [];

        // Common selectors for menu items
        $itemSelectors = [
            '//div[contains(@class, "menu-item")]',
            '//div[contains(@class, "food-item")]',
            '//div[contains(@class, "dish")]',
            '//article[contains(@class, "dish")]',
            '//li[contains(@class, "menu")]',
            '//article[contains(@class, "menu")]',
            '//*[contains(@class, "item") and contains(@class, "price")]',
        ];

        foreach ($itemSelectors as $selector) {
            $nodes = $xpath->query($selector);

            foreach ($nodes as $node) {
                $item = $this->extractMenuItemFromNode($node, $xpath);
                if ($item && !empty($item['name'])) {
                    $menuItems[] = $item;
                }
            }

            if (!empty($menuItems)) {
                break; // Found items with this selector, no need to try others
            }
        }

        // If no structured items found, try text-based parsing
        if (empty($menuItems)) {
            $textContent = $dom->textContent;
            $textResult = $this->parseMenuFromText($textContent);
            $menuItems = $textResult['items'];
            $sections = $textResult['sections'];
        } else {
            $sections = $this->extractSectionsFromItems($menuItems);
        }

        return [
            'items' => $menuItems,
            'sections' => $sections,
            'total_items' => count($menuItems),
            'parsing_method' => 'generic_html'
        ];
    }

    /**
     * Extract menu item data from a DOM node with sanitization
     *
     * @param \DOMNode $node
     * @param \DOMXPath $xpath
     * @return array|null
     */
    protected function extractMenuItemFromNode($node, DOMXPath $xpath): ?array
    {
        $item = [
            'name' => null,
            'description' => null,
            'price' => null,
            'section' => null,
        ];

        // Extract name
        $nameSelectors = [
            './/h1 | .//h2 | .//h3 | .//h4',
            './/*[contains(@class, "name")]',
            './/*[contains(@class, "title")]',
            './/strong',
            './/b',
        ];

        foreach ($nameSelectors as $selector) {
            $nameNodes = $xpath->query($selector, $node);
            if ($nameNodes->length > 0) {
                $item['name'] = $this->sanitizeText(trim($nameNodes->item(0)->textContent));
                break;
            }
        }

        // Extract price - be more specific to avoid cross-contamination
        $priceSelectors = [
            './/*[contains(@class, "price")]',
            './/*[contains(@class, "cost")]',
            './/div[contains(@class, "dish-price")]',
            './/span[contains(text(), "$") and not(ancestor::*[contains(@class, "description")])]',
            './/div[contains(text(), "$") and not(ancestor::*[contains(@class, "description")])]',
        ];

        foreach ($priceSelectors as $selector) {
            $priceNodes = $xpath->query($selector, $node);
            if ($priceNodes->length > 0) {
                // Make sure the price node is actually within this menu item node
                $priceNode = $priceNodes->item(0);
                $priceText = $priceNode->textContent;

                // Only extract price if it contains a valid price pattern
                if ($this->containsPrice($priceText)) {
                    $item['price'] = $this->sanitizePrice($priceText);
                    break;
                }
            }
        }

        // Extract description
        $descSelectors = [
            './/*[contains(@class, "description")]',
            './/*[contains(@class, "desc")]',
            './/p',
            './/span[not(contains(@class, "price"))]',
        ];

        foreach ($descSelectors as $selector) {
            $descNodes = $xpath->query($selector, $node);
            if ($descNodes->length > 0) {
                $desc = trim($descNodes->item(0)->textContent);
                if (!empty($desc) && $desc !== $item['name'] && !$this->containsPrice($desc)) {
                    $item['description'] = $this->sanitizeText($desc);
                    break;
                }
            }
        }

        // Try to extract section from parent elements
        $sectionSelectors = [
            'ancestor::*[contains(@class, "section")]//*[contains(@class, "title")]',
            'ancestor::*[contains(@class, "category")]//*[contains(@class, "title")]',
            'preceding::h1[1] | preceding::h2[1] | preceding::h3[1]',
        ];

        foreach ($sectionSelectors as $selector) {
            $sectionNodes = $xpath->query($selector, $node);
            if ($sectionNodes->length > 0) {
                $item['section'] = $this->sanitizeText(trim($sectionNodes->item(0)->textContent));
                break;
            }
        }

        // Validate the extracted item
        if (empty($item['name']) || strlen($item['name']) < 2) {
            return null; // Invalid item, name is required
        }

        // Check for suspicious content
        if ($this->containsSuspiciousContent($item['name']) || 
            $this->containsSuspiciousContent($item['description'])) {
            Log::warning('Suspicious content detected in menu item', [
                'name' => $item['name'],
                'description' => $item['description']
            ]);
            
            // Further sanitize suspicious content
            $item['name'] = $this->sanitizeSuspiciousContent($item['name']);
            $item['description'] = $this->sanitizeSuspiciousContent($item['description']);
        }

        return $item;
    }

    protected function parseMenuFromText(string $text): array
    {
        $lines = explode("\n", $text);
        $menuItems = [];
        $sections = [];
        $currentSection = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check if line is a section header
            if ($this->isSectionHeader($line)) {
                $currentSection = $this->sanitizeText($line);
                if (!in_array($currentSection, $sections)) {
                    $sections[] = $currentSection;
                }
                continue;
            }

            // Try to parse as menu item
            $item = $this->parseMenuItemFromText($line);
            if ($item) {
                $item['section'] = $currentSection;
                $menuItems[] = $item;
            }
        }

        return [
            'items' => $menuItems,
            'sections' => $sections,
            'total_items' => count($menuItems)
        ];
    }

    protected function parseMenuItemFromText(string $line): ?array
    {
        // Patterns to match menu items with prices
        $patterns = [
            '/^(.+?)\s*[\-\.\s]+\s*\$?(\d+\.?\d*)\s*$/',  // Name ... $12.99
            '/^(.+?)\s+\$(\d+\.?\d*)\s*$/',               // Name $12.99
            '/^(.+?)\s+(\d+\.?\d*)\s*$/',                 // Name 12.99
            '/^(.+?)\s*\$?(\d+\.?\d*)\s+(.+)$/',          // Name $12.99 Description
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $name = $this->sanitizeText(trim($matches[1]));
                $price = $this->sanitizePrice($matches[2]);
                $description = isset($matches[3]) ? $this->sanitizeText(trim($matches[3])) : null;

                // Skip if name is too short or price is 0
                if (strlen($name) < 3 || $price <= 0) {
                    continue;
                }

                return [
                    'name' => $name,
                    'price' => $price,
                    'description' => $description,
                    'section' => null,
                ];
            }
        }

        return null;
    }

    protected function isSectionHeader(string $line): bool
    {
        // First check if line contains a price - if it does, it's likely a menu item, not a section
        if ($this->containsPrice($line)) {
            return false;
        }

        // Check if line looks like a section header
        $sectionIndicators = [
            'appetizers',
            'starters',
            'salads',
            'soups',
            'entrees',
            'mains',
            'desserts',
            'beverages',
            'drinks',
            'wine',
            'beer',
            'cocktails',
            'breakfast',
            'lunch',
            'dinner',
            'brunch',
            'sides',
            'pasta',
            'pizza',
            'burgers',
            'sandwiches',
            'seafood',
            'meat',
            'vegetarian'
        ];

        $lineLower = strtolower($line);

        // Check if line contains section keywords
        foreach ($sectionIndicators as $indicator) {
            if (strpos($lineLower, $indicator) !== false) {
                return true;
            }
        }

        // Check if line is all caps (common for section headers)
        if (strlen($line) > 3 && $line === strtoupper($line)) {
            return true;
        }

        return false;
    }

    protected function extractPriceFromText(string $text): ?float
    {
        if (preg_match('/\$?(\d+\.?\d*)/', $text, $matches)) {
            return floatval($matches[1]);
        }
        return null;
    }

    protected function containsPrice(string $text): bool
    {
        return preg_match('/\$\d+\.?\d*|\d+\.?\d*\s*\$/', $text) === 1;
    }

    protected function extractSectionsFromItems(array $items): array
    {
        $sections = [];
        foreach ($items as $item) {
            if (!empty($item['section']) && !in_array($item['section'], $sections)) {
                $sections[] = $item['section'];
            }
        }
        return $sections;
    }

    /**
     * Sanitize text content by removing HTML tags and controlling length
     *
     * @param string|null $text
     * @return string|null
     */
    protected function sanitizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Remove any HTML tags
        $text = strip_tags($text);
        
        // Remove control characters
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim whitespace
        $text = trim($text);
        
        return $text;
    }

    /**
     * Sanitize price value
     *
     * @param mixed $price
     * @return float|null
     */
    protected function sanitizePrice($price): ?float
    {
        // If it's already a numeric value
        if (is_numeric($price)) {
            $value = (float) $price;
            return ($value >= 0 && $value <= 999999.99) ? $value : null;
        }
        
        // If it's a string, try to extract a price
        if (is_string($price)) {
            // Remove currency symbols and non-numeric characters except decimal point
            $price = preg_replace('/[^0-9.]/', '', $price);
            
            if (is_numeric($price)) {
                $value = (float) $price;
                return ($value >= 0 && $value <= 999999.99) ? $value : null;
            }
        }
        
        return null;
    }

    /**
     * Check if content contains suspicious patterns
     *
     * @param string|null $content
     * @return bool
     */
    protected function containsSuspiciousContent(?string $content): bool
    {
        if ($content === null) {
            return false;
        }
        
        // Check for potential XSS
        if (preg_match('/<script|javascript:|on\w+=/i', $content)) {
            return true;
        }
        
        // Check for SQL injection patterns
        if (preg_match('/(\%27)|(\')|(\-\-)|(\%23)|(#)/i', $content)) {
            return true;
        }
        
        // Check for excessive special characters
        $specialCharCount = preg_match_all('/[^\w\s]/', $content);
        if ($specialCharCount > strlen($content) * 0.3) {
            return true;
        }
        
        return false;
    }

    /**
     * Sanitize suspicious content with more aggressive cleaning
     *
     * @param string|null $content
     * @return string|null
     */
    protected function sanitizeSuspiciousContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }
        
        // First apply normal sanitization
        $content = $this->sanitizeText($content);
        
        // Remove all special characters
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', '', $content);
        
        // Normalize whitespace again
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Trim whitespace
        $content = trim($content);
        
        return $content;
    }

    // Site-specific parsers
    protected function parseSquarespace(string $content, string $url): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $menuItems = [];

        // Squarespace-specific selectors
        $itemNodes = $xpath->query('//div[contains(@class, "ProductList-item")] | //div[contains(@class, "menu-item")]');

        foreach ($itemNodes as $node) {
            $item = $this->extractMenuItemFromNode($node, $xpath);
            if ($item && !empty($item['name'])) {
                $menuItems[] = $item;
            }
        }

        return [
            'items' => $menuItems,
            'sections' => $this->extractSectionsFromItems($menuItems),
            'total_items' => count($menuItems),
            'parsing_method' => 'squarespace'
        ];
    }

    protected function parseWix(string $content, string $url): array
    {
        // Wix sites often use specific class patterns
        return $this->parseGenericHtml($content, $url);
    }

    protected function parseWordPress(string $content, string $url): array
    {
        // WordPress sites with menu plugins
        return $this->parseGenericHtml($content, $url);
    }

    protected function parseToast(string $content, string $url): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $menuItems = [];

        // Toast-specific selectors - be more specific to avoid duplicates
        $itemNodes = $xpath->query('//div[@class="toast-item"]');

        foreach ($itemNodes as $node) {
            $item = [
                'name' => null,
                'description' => null,
                'price' => null,
                'section' => null,
            ];

            // Extract name from toast-item-name
            $nameNodes = $xpath->query('.//span[@class="toast-item-name"]', $node);
            if ($nameNodes->length > 0) {
                $item['name'] = $this->sanitizeText(trim($nameNodes->item(0)->textContent));
            }

            // Extract price from toast-item-price
            $priceNodes = $xpath->query('.//span[@class="toast-item-price"]', $node);
            if ($priceNodes->length > 0) {
                $priceText = $priceNodes->item(0)->textContent;
                $item['price'] = $this->sanitizePrice($priceText);
            }

            // Extract description from toast-item-description
            $descNodes = $xpath->query('.//div[contains(@class, "toast-item-description")]', $node);
            if ($descNodes->length > 0) {
                $item['description'] = $this->sanitizeText(trim($descNodes->item(0)->textContent));
            }

            // Extract section from parent category
            $sectionNodes = $xpath->query('ancestor::*[contains(@class, "toast-category")]//h2[contains(@class, "toast-category-name")]', $node);
            if ($sectionNodes->length > 0) {
                $item['section'] = $this->sanitizeText(trim($sectionNodes->item(0)->textContent));
            }

            if ($item['name'] && !empty($item['name'])) {
                $menuItems[] = $item;
            }
        }

        return [
            'items' => $menuItems,
            'sections' => $this->extractSectionsFromItems($menuItems),
            'total_items' => count($menuItems),
            'parsing_method' => 'toast'
        ];
    }

    protected function parseGenericMenuStructure(string $content, string $url): array
    {
        return $this->parseGenericHtml($content, $url);
    }
}