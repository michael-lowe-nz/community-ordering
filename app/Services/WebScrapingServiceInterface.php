<?php

namespace App\Services;

interface WebScrapingServiceInterface
{
    /**
     * Fetch menu content from a URL
     * 
     * @param string $url
     * @return array|null ['content' => string, 'type' => 'html|pdf']
     */
    public function fetchMenuContent(string $url): ?array;

    /**
     * Download PDF file and return local file path
     * 
     * @param string $url
     * @return string|null
     */
    public function downloadPdf(string $url): ?string;

    /**
     * Check if URL is accessible
     * 
     * @param string $url
     * @return bool
     */
    public function isUrlAccessible(string $url): bool;

    /**
     * Check if URL respects robots.txt
     * 
     * @param string $url
     * @return bool
     */
    public function respectsRobotsTxt(string $url): bool;

    /**
     * Get content type of URL
     * 
     * @param string $url
     * @return string
     */
    public function getContentType(string $url): string;
}