<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WebScrapingService implements WebScrapingServiceInterface
{
    protected Client $client;
    protected array $lastRequestTimes = [];
    protected int $minDelaySeconds = 2;
    protected int $maxRetries = 3;
    protected array $retryDelays = [1, 2, 4]; // exponential backoff in seconds
    
    // HTTP status codes that should be retried
    protected array $retryStatusCodes = [408, 429, 500, 502, 503, 504];
    
    // Error types and their descriptions for better error reporting
    protected array $errorTypes = [
        'connection' => 'Connection error',
        'timeout' => 'Request timed out',
        'rate_limit' => 'Rate limited by server',
        'access_denied' => 'Access denied',
        'not_found' => 'Resource not found',
        'server_error' => 'Server error',
        'invalid_content' => 'Invalid content received',
        'robots_txt' => 'Blocked by robots.txt',
        'unknown' => 'Unknown error'
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'MenuScrapingBot/1.0 (+https://example.com/bot)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml,application/pdf;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ]);
    }

    /**
     * Fetch menu content from a URL with enhanced error handling
     * 
     * @param string $url
     * @return array|null ['content' => string, 'type' => 'html|pdf', 'warnings' => array]
     */
    public function fetchMenuContent(string $url): ?array
    {
        $warnings = [];
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::error("Invalid URL format: {$url}");
            return null;
        }
        
        // Check if URL is accessible
        if (!$this->isUrlAccessible($url)) {
            Log::warning("URL not accessible: {$url}");
            return null;
        }

        // Check robots.txt compliance
        if (!$this->respectsRobotsTxt($url)) {
            Log::warning("Robots.txt disallows scraping: {$url}");
            return null;
        }

        // Apply rate limiting
        $this->enforceRateLimit($url);

        // Determine content type
        try {
            $contentType = $this->getContentType($url);
        } catch (\Exception $e) {
            Log::warning("Failed to determine content type for {$url}: {$e->getMessage()}");
            // Default to HTML if we can't determine content type
            $contentType = 'text/html';
            $warnings[] = "Could not determine content type, defaulting to HTML";
        }
        
        // Handle PDF content
        if (str_contains($contentType, 'pdf')) {
            try {
                $pdfPath = $this->downloadPdf($url);
                if ($pdfPath) {
                    return [
                        'content' => $pdfPath,
                        'type' => 'pdf',
                        'warnings' => $warnings
                    ];
                }
                Log::warning("Failed to download PDF from {$url}");
                return null;
            } catch (\Exception $e) {
                Log::error("Exception downloading PDF from {$url}: {$e->getMessage()}");
                return null;
            }
        }

        // Handle HTML content
        try {
            $content = $this->fetchWithRetry($url);
            if ($content) {
                // Check for minimal HTML structure
                if (!$this->isValidHtml($content)) {
                    $warnings[] = "Content may not be valid HTML";
                    Log::warning("Content from {$url} may not be valid HTML");
                }
                
                return [
                    'content' => $content,
                    'type' => 'html',
                    'warnings' => $warnings
                ];
            }
        } catch (\Exception $e) {
            Log::error("Exception fetching content from {$url}: {$e->getMessage()}");
        }

        return null;
    }
    
    /**
     * Check if content appears to be valid HTML
     * 
     * @param string $content
     * @return bool
     */
    private function isValidHtml(string $content): bool
    {
        // Check for basic HTML structure
        $hasHtmlTag = stripos($content, '<html') !== false;
        $hasBodyTag = stripos($content, '<body') !== false;
        
        // If it has neither HTML nor BODY tags, it might not be HTML
        if (!$hasHtmlTag && !$hasBodyTag) {
            // Check if it at least has some HTML tags
            return preg_match('/<[a-z][a-z0-9]*[^<>]*>/i', $content) === 1;
        }
        
        return true;
    }

    public function downloadPdf(string $url): ?string
    {
        try {
            $this->enforceRateLimit($url);
            
            $response = $this->client->get($url, [
                'sink' => $tempFile = tempnam(sys_get_temp_dir(), 'menu_pdf_')
            ]);

            if ($response->getStatusCode() === 200) {
                return $tempFile;
            }
        } catch (GuzzleException $e) {
            Log::error("Failed to download PDF from {$url}: " . $e->getMessage());
        }

        return null;
    }

    public function isUrlAccessible(string $url): bool
    {
        try {
            $response = $this->client->head($url, [
                'timeout' => 10,
                'http_errors' => false
            ]);
            
            return $response->getStatusCode() < 400;
        } catch (GuzzleException $e) {
            Log::debug("URL accessibility check failed for {$url}: " . $e->getMessage());
            return false;
        }
    }

    public function respectsRobotsTxt(string $url): bool
    {
        try {
            $parsedUrl = parse_url($url);
            $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
            
            $response = $this->client->get($robotsUrl, [
                'timeout' => 5,
                'http_errors' => false
            ]);

            if ($response->getStatusCode() !== 200) {
                // If robots.txt doesn't exist, assume scraping is allowed
                return true;
            }

            $robotsContent = $response->getBody()->getContents();
            $userAgent = 'MenuScrapingBot';
            
            return $this->parseRobotsTxt($robotsContent, $userAgent, $url);
        } catch (GuzzleException $e) {
            Log::debug("Robots.txt check failed for {$url}: " . $e->getMessage());
            // If we can't check robots.txt, assume scraping is allowed
            return true;
        }
    }

    public function getContentType(string $url): string
    {
        try {
            $response = $this->client->head($url, [
                'timeout' => 10,
                'http_errors' => false
            ]);
            
            $contentType = $response->getHeaderLine('Content-Type');
            return strtolower($contentType);
        } catch (GuzzleException $e) {
            Log::debug("Content type check failed for {$url}: " . $e->getMessage());
            return 'text/html';
        }
    }

    /**
     * Fetch content with retry logic and improved error handling
     *
     * @param string $url
     * @return string|null
     */
    private function fetchWithRetry(string $url): ?string
    {
        $lastException = null;
        $errorDetails = [];

        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                if ($attempt > 0) {
                    $delay = $this->retryDelays[$attempt - 1] ?? 4;
                    Log::info("Retrying request to {$url} after {$delay} seconds (attempt " . ($attempt + 1) . ")");
                    sleep($delay);
                }

                $response = $this->client->get($url, [
                    'timeout' => 30 + ($attempt * 10), // Increase timeout with each retry
                    'connect_timeout' => 10 + ($attempt * 5),
                    'http_errors' => false // Handle HTTP errors manually for better control
                ]);
                
                $statusCode = $response->getStatusCode();
                
                // Success case
                if ($statusCode === 200) {
                    $content = $response->getBody()->getContents();
                    
                    // Validate content is not empty
                    if (empty($content)) {
                        Log::warning("Empty content received from {$url}");
                        $errorDetails = [
                            'type' => 'invalid_content',
                            'message' => 'Empty content received',
                            'status_code' => $statusCode
                        ];
                        continue;
                    }
                    
                    return $content;
                }
                
                // Handle different status codes
                if ($statusCode === 429) {
                    // Rate limited - respect Retry-After header if present
                    $retryAfter = $response->getHeaderLine('Retry-After');
                    $waitTime = is_numeric($retryAfter) ? (int)$retryAfter : $this->retryDelays[$attempt] ?? 4;
                    Log::info("Rate limited (429). Waiting {$waitTime} seconds before retry.");
                    sleep($waitTime);
                    $errorDetails = [
                        'type' => 'rate_limit',
                        'message' => 'Rate limited by server',
                        'status_code' => $statusCode,
                        'retry_after' => $retryAfter
                    ];
                    continue;
                }
                
                if ($statusCode === 403 || $statusCode === 401) {
                    // Access denied - might not be worth retrying
                    Log::warning("Access denied for {$url} (status code: {$statusCode})");
                    $errorDetails = [
                        'type' => 'access_denied',
                        'message' => 'Access denied or authentication required',
                        'status_code' => $statusCode
                    ];
                    // Only retry once for access denied errors
                    if ($attempt > 0) {
                        break;
                    }
                    continue;
                }
                
                if ($statusCode === 404) {
                    // Not found - no point retrying
                    Log::warning("Resource not found at {$url} (status code: 404)");
                    $errorDetails = [
                        'type' => 'not_found',
                        'message' => 'Resource not found',
                        'status_code' => 404
                    ];
                    break;
                }
                
                if ($statusCode >= 500) {
                    // Server error - worth retrying
                    Log::warning("Server error for {$url} (status code: {$statusCode})");
                    $errorDetails = [
                        'type' => 'server_error',
                        'message' => 'Server error',
                        'status_code' => $statusCode
                    ];
                    continue;
                }
                
                // Other status codes
                Log::warning("Unexpected status code {$statusCode} for {$url}");
                $errorDetails = [
                    'type' => 'unknown',
                    'message' => "Unexpected status code: {$statusCode}",
                    'status_code' => $statusCode
                ];

            } catch (RequestException $e) {
                $lastException = $e;
                
                // Handle specific request exceptions
                if ($e->hasResponse()) {
                    $statusCode = $e->getResponse()->getStatusCode();
                    
                    if ($statusCode === 429) {
                        // Rate limited
                        $retryAfter = $e->getResponse()->getHeaderLine('Retry-After');
                        $waitTime = is_numeric($retryAfter) ? (int)$retryAfter : $this->retryDelays[$attempt] ?? 4;
                        Log::info("Rate limited. Waiting {$waitTime} seconds before retry.");
                        sleep($waitTime);
                        $errorDetails = [
                            'type' => 'rate_limit',
                            'message' => 'Rate limited by server',
                            'status_code' => $statusCode,
                            'retry_after' => $retryAfter
                        ];
                        continue;
                    }
                    
                    if (in_array($statusCode, $this->retryStatusCodes)) {
                        // Other retryable status codes
                        $errorDetails = [
                            'type' => 'server_error',
                            'message' => "Server error with status code: {$statusCode}",
                            'status_code' => $statusCode
                        ];
                        continue;
                    }
                }
                
                // Connection errors, timeouts, etc.
                $errorMessage = $e->getMessage();
                $errorDetails = $this->categorizeRequestError($errorMessage);
                
                Log::warning("Request failed for {$url} (attempt " . ($attempt + 1) . "): " . $errorMessage, [
                    'error_type' => $errorDetails['type'],
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Only retry certain types of errors
                if (in_array($errorDetails['type'], ['connection', 'timeout', 'server_error'])) {
                    continue;
                }
                
            } catch (GuzzleException $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();
                $errorDetails = $this->categorizeRequestError($errorMessage);
                
                Log::warning("Request failed for {$url} (attempt " . ($attempt + 1) . "): " . $errorMessage, [
                    'error_type' => $errorDetails['type'],
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Log detailed error information after all retries have failed
        $errorType = $errorDetails['type'] ?? 'unknown';
        $errorMessage = $errorDetails['message'] ?? ($lastException ? $lastException->getMessage() : 'Unknown error');
        $statusCode = $errorDetails['status_code'] ?? null;
        
        Log::error("All retry attempts failed for {$url}", [
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'status_code' => $statusCode,
            'last_exception' => $lastException ? get_class($lastException) : null
        ]);
        
        return null;
    }
    
    /**
     * Categorize request errors for better error handling
     *
     * @param string $errorMessage
     * @return array
     */
    private function categorizeRequestError(string $errorMessage): array
    {
        $errorMessage = strtolower($errorMessage);
        
        if (strpos($errorMessage, 'connection') !== false || 
            strpos($errorMessage, 'connect') !== false) {
            return [
                'type' => 'connection',
                'message' => 'Connection error'
            ];
        }
        
        if (strpos($errorMessage, 'timeout') !== false || 
            strpos($errorMessage, 'timed out') !== false) {
            return [
                'type' => 'timeout',
                'message' => 'Request timed out'
            ];
        }
        
        if (strpos($errorMessage, 'ssl') !== false || 
            strpos($errorMessage, 'certificate') !== false) {
            return [
                'type' => 'ssl',
                'message' => 'SSL/TLS error'
            ];
        }
        
        if (strpos($errorMessage, '429') !== false || 
            strpos($errorMessage, 'rate limit') !== false || 
            strpos($errorMessage, 'too many requests') !== false) {
            return [
                'type' => 'rate_limit',
                'message' => 'Rate limited by server'
            ];
        }
        
        if (strpos($errorMessage, '403') !== false || 
            strpos($errorMessage, '401') !== false || 
            strpos($errorMessage, 'forbidden') !== false || 
            strpos($errorMessage, 'unauthorized') !== false) {
            return [
                'type' => 'access_denied',
                'message' => 'Access denied or authentication required'
            ];
        }
        
        if (strpos($errorMessage, '404') !== false || 
            strpos($errorMessage, 'not found') !== false) {
            return [
                'type' => 'not_found',
                'message' => 'Resource not found'
            ];
        }
        
        if (strpos($errorMessage, '5') !== false && 
            preg_match('/5\d\d/', $errorMessage)) {
            return [
                'type' => 'server_error',
                'message' => 'Server error'
            ];
        }
        
        return [
            'type' => 'unknown',
            'message' => $errorMessage
        ];
    }

    private function enforceRateLimit(string $url): void
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $now = time();
        
        if (isset($this->lastRequestTimes[$domain])) {
            $timeSinceLastRequest = $now - $this->lastRequestTimes[$domain];
            
            if ($timeSinceLastRequest < $this->minDelaySeconds) {
                $sleepTime = $this->minDelaySeconds - $timeSinceLastRequest;
                Log::debug("Rate limiting: sleeping for {$sleepTime} seconds before request to {$domain}");
                sleep($sleepTime);
            }
        }
        
        $this->lastRequestTimes[$domain] = time();
    }

    private function parseRobotsTxt(string $robotsContent, string $userAgent, string $url): bool
    {
        $lines = explode("\n", $robotsContent);
        $currentUserAgent = null;
        $isRelevantSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            if (str_starts_with(strtolower($line), 'user-agent:')) {
                $currentUserAgent = trim(substr($line, 11));
                $isRelevantSection = ($currentUserAgent === '*' || 
                                   str_contains(strtolower($currentUserAgent), strtolower($userAgent)));
                continue;
            }
            
            if ($isRelevantSection && str_starts_with(strtolower($line), 'disallow:')) {
                $disallowedPath = trim(substr($line, 9));
                
                if ($disallowedPath === '/') {
                    return false; // Entire site is disallowed
                }
                
                if (!empty($disallowedPath)) {
                    $urlPath = parse_url($url, PHP_URL_PATH) ?? '/';
                    if (str_starts_with($urlPath, $disallowedPath)) {
                        return false;
                    }
                }
            }
            
            if ($isRelevantSection && str_starts_with(strtolower($line), 'crawl-delay:')) {
                $crawlDelay = (int)trim(substr($line, 12));
                if ($crawlDelay > $this->minDelaySeconds) {
                    $this->minDelaySeconds = $crawlDelay;
                }
            }
        }
        
        return true;
    }
}