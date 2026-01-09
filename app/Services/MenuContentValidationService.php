<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MenuContentValidationService
{
    // Maximum allowed lengths for different fields
    protected const MAX_NAME_LENGTH = 255;
    protected const MAX_DESCRIPTION_LENGTH = 1000;
    protected const MAX_SECTION_LENGTH = 100;
    protected const MIN_NAME_LENGTH = 2;
    protected const MAX_PRICE = 999999.99;
    protected const MIN_PRICE = 0;
    
    // Patterns for detecting suspicious content
    protected const SUSPICIOUS_PATTERNS = [
        'xss' => '/<script|javascript:|on\w+=/i',
        'sql_injection' => '/(\%27)|(\')|(\-\-)|(\%23)|(#)|(\bunion\b)|(\bselect\b)|(\binsert\b)|(\bupdate\b)|(\bdelete\b)|(\bdrop\b)/i',
        'path_traversal' => '/\.\.\/|\.\.\\\\/',
        'command_injection' => '/[;&|`$(){}]/i',
        'html_entities' => '/&[a-zA-Z0-9#]+;/',
    ];
    
    // Common spam/invalid menu item patterns
    protected const SPAM_PATTERNS = [
        '/^(test|sample|example|lorem|ipsum)$/i',
        '/^[0-9]+$/', // Only numbers
        '/^[^a-zA-Z0-9\s]+$/', // Only special characters
        '/(.)\1{10,}/', // Repeated characters (10+ times)
    ];

    /**
     * Validate and sanitize menu item data with comprehensive error handling
     *
     * @param array $menuItem
     * @return array|null Sanitized menu item or null if invalid
     */
    public function validateAndSanitizeMenuItem(array $menuItem): ?array
    {
        try {
            // Pre-validation sanitization to prevent validation bypass
            $menuItem = $this->preValidationSanitization($menuItem);
            
            // Basic validation rules with enhanced constraints
            $validator = Validator::make($menuItem, [
                'name' => [
                    'required',
                    'string',
                    'min:' . self::MIN_NAME_LENGTH,
                    'max:' . self::MAX_NAME_LENGTH,
                    function ($attribute, $value, $fail) {
                        if ($this->isSpamContent($value)) {
                            $fail('The ' . $attribute . ' appears to be spam or invalid content.');
                        }
                    }
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:' . self::MAX_DESCRIPTION_LENGTH,
                    function ($attribute, $value, $fail) {
                        if ($value && $this->isSpamContent($value)) {
                            $fail('The ' . $attribute . ' appears to be spam or invalid content.');
                        }
                    }
                ],
                'price' => [
                    'nullable',
                    'numeric',
                    'min:' . self::MIN_PRICE,
                    'max:' . self::MAX_PRICE,
                    function ($attribute, $value, $fail) {
                        if ($value !== null && !$this->isValidPrice($value)) {
                            $fail('The ' . $attribute . ' is not a valid price format.');
                        }
                    }
                ],
                'section' => [
                    'nullable',
                    'string',
                    'max:' . self::MAX_SECTION_LENGTH,
                    function ($attribute, $value, $fail) {
                        if ($value && $this->isSpamContent($value)) {
                            $fail('The ' . $attribute . ' appears to be spam or invalid content.');
                        }
                    }
                ],
            ]);

            if ($validator->fails()) {
                Log::warning('Menu item validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'item' => $this->sanitizeForLogging($menuItem)
                ]);
                return null;
            }

            // Deep sanitization after validation
            $sanitized = [
                'name' => $this->sanitizeText($menuItem['name']),
                'description' => isset($menuItem['description']) ? $this->sanitizeText($menuItem['description']) : null,
                'price' => isset($menuItem['price']) ? $this->sanitizePrice($menuItem['price']) : null,
                'section' => isset($menuItem['section']) ? $this->sanitizeText($menuItem['section']) : null,
            ];

            // Post-sanitization validation
            if (!$this->isValidSanitizedItem($sanitized)) {
                Log::warning('Menu item failed post-sanitization validation', [
                    'original' => $this->sanitizeForLogging($menuItem),
                    'sanitized' => $sanitized
                ]);
                return null;
            }

            // Security validation
            $securityCheck = $this->performSecurityValidation($sanitized);
            if (!$securityCheck['is_safe']) {
                Log::warning('Menu item failed security validation', [
                    'item' => $sanitized,
                    'security_issues' => $securityCheck['issues']
                ]);
                return null;
            }

            return $sanitized;
            
        } catch (\Exception $e) {
            Log::error('Exception during menu item validation', [
                'error' => $e->getMessage(),
                'item' => $this->sanitizeForLogging($menuItem),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Validate and sanitize an array of menu items
     *
     * @param array $menuItems
     * @return array [sanitized_items, validation_errors]
     */
    public function validateAndSanitizeMenuItems(array $menuItems): array
    {
        $sanitizedItems = [];
        $errors = [];

        foreach ($menuItems as $index => $item) {
            $sanitized = $this->validateAndSanitizeMenuItem($item);
            
            if ($sanitized) {
                $sanitizedItems[] = $sanitized;
            } else {
                $errors[] = "Item at index {$index} failed validation";
            }
        }

        return [
            'items' => $sanitizedItems,
            'errors' => $errors,
            'total_valid' => count($sanitizedItems),
            'total_invalid' => count($errors),
        ];
    }

    /**
     * Validate menu structure
     *
     * @param array $menuData
     * @return array [is_valid, errors]
     */
    public function validateMenuStructure(array $menuData): array
    {
        $errors = [];

        // Check if items array exists
        if (!isset($menuData['items']) || !is_array($menuData['items'])) {
            $errors[] = 'Menu data must contain an items array';
            return ['is_valid' => false, 'errors' => $errors];
        }

        // Check if there are any items
        if (count($menuData['items']) === 0) {
            $errors[] = 'Menu data contains no items';
            return ['is_valid' => false, 'errors' => $errors];
        }

        // Check for required fields in each item
        foreach ($menuData['items'] as $index => $item) {
            if (!isset($item['name']) || empty($item['name'])) {
                $errors[] = "Item at index {$index} is missing a name";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize text content
     *
     * @param string|null $text
     * @return string|null
     */
    public function sanitizeText(?string $text): ?string
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
        
        // Limit length
        if (strlen($text) > 1000) {
            $text = substr($text, 0, 997) . '...';
        }
        
        return $text;
    }

    /**
     * Sanitize price value
     *
     * @param mixed $price
     * @return float|null
     */
    public function sanitizePrice($price): ?float
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
     * @param string $content
     * @return array [is_suspicious, reasons]
     */
    public function detectSuspiciousContent(string $content): array
    {
        $reasons = [];
        
        // Check for potential XSS
        if (preg_match('/<script|javascript:|on\w+=/i', $content)) {
            $reasons[] = 'Potential XSS detected';
        }
        
        // Check for SQL injection patterns
        if (preg_match('/(\%27)|(\')|(\-\-)|(\%23)|(#)/i', $content)) {
            $reasons[] = 'Potential SQL injection pattern detected';
        }
        
        // Check for excessive special characters
        $specialCharCount = preg_match_all('/[^\w\s]/', $content);
        if ($specialCharCount > strlen($content) * 0.3) {
            $reasons[] = 'Excessive special characters detected';
        }
        
        return [
            'is_suspicious' => !empty($reasons),
            'reasons' => $reasons
        ];
    }
}