<?php

namespace App\Services;

interface MenuParsingServiceInterface
{
    /**
     * Parse menu content from HTML or PDF
     * 
     * @param string $content Content to parse (HTML string or PDF file path)
     * @param string $url Source URL for context
     * @param string $contentType Content type ('html' or 'pdf')
     * @return array Parsed menu data
     */
    public function parseMenuContent(string $content, string $url, string $contentType = 'html'): array;

    /**
     * Detect menu format from content
     * 
     * @param string $content Content to analyze
     * @param string $contentType Content type ('html' or 'pdf')
     * @return string Detected format identifier
     */
    public function detectMenuFormat(string $content, string $contentType): string;

    /**
     * Get list of supported content formats
     * 
     * @return array List of supported formats
     */
    public function getSupportedFormats(): array;

    /**
     * Parse PDF menu content
     * 
     * @param string $pdfPath Path to PDF file
     * @return array Parsed menu data
     */
    public function parsePdfMenu(string $pdfPath): array;
}