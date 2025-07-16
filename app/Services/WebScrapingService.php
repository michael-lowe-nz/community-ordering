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

    public function fetchMenuContent(string $url): ?array
    {
        if (!$this->isUrlAccessible($url)) {
            Log::warning("URL not accessible: {$url}");
            return null;
        }

        if (!$this->respectsRobotsTxt($url)) {
            Log::warning("Robots.txt disallows scraping: {$url}");
            return null;
        }

        $this->enforceRateLimit($url);

        $contentType = $this->getContentType($url);
        
        if (str_contains($contentType, 'pdf')) {
            $pdfPath = $this->downloadPdf($url);
            if ($pdfPath) {
                return [
                    'content' => $pdfPath,
                    'type' => 'pdf'
                ];
            }
            return null;
        }

        // Fetch HTML content
        $content = $this->fetchWithRetry($url);
        if ($content) {
            return [
                'content' => $content,
                'type' => 'html'
            ];
        }

        return null;
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

    private function fetchWithRetry(string $url): ?string
    {
        $lastException = null;

        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                if ($attempt > 0) {
                    $delay = $this->retryDelays[$attempt - 1] ?? 4;
                    Log::info("Retrying request to {$url} after {$delay} seconds (attempt " . ($attempt + 1) . ")");
                    sleep($delay);
                }

                $response = $this->client->get($url);
                
                if ($response->getStatusCode() === 200) {
                    return $response->getBody()->getContents();
                }
                
                if ($response->getStatusCode() === 429) {
                    // Rate limited - wait longer
                    $retryAfter = $response->getHeaderLine('Retry-After');
                    $waitTime = is_numeric($retryAfter) ? (int)$retryAfter : $this->retryDelays[$attempt] ?? 4;
                    Log::info("Rate limited. Waiting {$waitTime} seconds before retry.");
                    sleep($waitTime);
                    continue;
                }

            } catch (RequestException $e) {
                $lastException = $e;
                
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 429) {
                    // Rate limited
                    $retryAfter = $e->getResponse()->getHeaderLine('Retry-After');
                    $waitTime = is_numeric($retryAfter) ? (int)$retryAfter : $this->retryDelays[$attempt] ?? 4;
                    Log::info("Rate limited. Waiting {$waitTime} seconds before retry.");
                    sleep($waitTime);
                    continue;
                }
                
                Log::warning("Request failed for {$url} (attempt " . ($attempt + 1) . "): " . $e->getMessage());
            } catch (GuzzleException $e) {
                $lastException = $e;
                Log::warning("Request failed for {$url} (attempt " . ($attempt + 1) . "): " . $e->getMessage());
            }
        }

        Log::error("All retry attempts failed for {$url}. Last error: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        return null;
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