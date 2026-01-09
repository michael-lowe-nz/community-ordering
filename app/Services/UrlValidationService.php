<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class UrlValidationService
{
    protected Client $client;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
            'http_errors' => false
        ]);
    }
    
    /**
     * Validate a URL with comprehensive checks
     *
     * @param string $url
     * @return array [is_valid, errors, warnings]
     */
    public function validateUrl(string $url): array
    {
        $errors = [];
        $warnings = [];
        
        // Basic format validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }
        
        // Check URL components
        $urlParts = parse_url($url);
        
        // Scheme validation
        if (!isset($urlParts['scheme']) || !in_array($urlParts['scheme'], ['http', 'https'])) {
            $errors[] = 'URL must use HTTP or HTTPS protocol';
            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }
        
        // Security warning for non-HTTPS
        if ($urlParts['scheme'] === 'http') {
            $warnings[] = 'Non-secure HTTP URL detected. HTTPS is recommended for security.';
        }
        
        // Host validation
        if (!isset($urlParts['host']) || empty($urlParts['host'])) {
            $errors[] = 'URL must contain a valid host';
            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }
        
        // Check for localhost or private IPs
        if ($this->isLocalOrPrivateHost($urlParts['host'])) {
            $errors[] = 'URL must not point to localhost or private network addresses';
            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }
        
        // Check URL accessibility
        try {
            $response = $this->client->head($url);
            $statusCode = $response->getStatusCode();
            
            if ($statusCode >= 400) {
                if ($statusCode === 404) {
                    $errors[] = 'URL returns a 404 Not Found response';
                } elseif ($statusCode === 403 || $statusCode === 401) {
                    $warnings[] = 'URL may require authentication or is forbidden (status code: ' . $statusCode . ')';
                } elseif ($statusCode >= 500) {
                    $warnings[] = 'URL server error detected (status code: ' . $statusCode . ')';
                } else {
                    $warnings[] = 'URL returns an error status code: ' . $statusCode;
                }
            }
        } catch (GuzzleException $e) {
            $warnings[] = 'Could not verify URL accessibility: ' . $e->getMessage();
        }
        
        // Check content type if accessible
        try {
            $response = $this->client->head($url);
            $contentType = $response->getHeaderLine('Content-Type');
            
            if (!$this->isSupportedContentType($contentType)) {
                $warnings[] = 'URL content type "' . $contentType . '" may not be supported for menu scraping';
            }
        } catch (GuzzleException $e) {
            // Already handled above
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
    
    /**
     * Check if host is localhost or a private network address
     *
     * @param string $host
     * @return bool
     */
    private function isLocalOrPrivateHost(string $host): bool
    {
        // Check for localhost
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }
        
        // Check for private IP ranges
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            // Check IPv4 private ranges
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if content type is supported for menu scraping
     *
     * @param string $contentType
     * @return bool
     */
    private function isSupportedContentType(string $contentType): bool
    {
        $supportedTypes = [
            'text/html',
            'application/xhtml+xml',
            'application/pdf',
            'application/json'
        ];
        
        foreach ($supportedTypes as $type) {
            if (stripos($contentType, $type) !== false) {
                return true;
            }
        }
        
        return false;
    }
}