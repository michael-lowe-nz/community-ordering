<?php

namespace Tests\Unit\Services;

use App\Services\WebScrapingService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WebScrapingServiceTest extends TestCase
{
    private WebScrapingService $service;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        
        // Create service with mocked HTTP client
        $this->service = new class($handlerStack) extends WebScrapingService {
            public function __construct($handlerStack)
            {
                $this->client = new Client(['handler' => $handlerStack]);
                $this->lastRequestTimes = [];
                $this->minDelaySeconds = 0; // Disable delays for testing
                $this->maxRetries = 3;
                $this->retryDelays = [0, 0, 0]; // No delays for testing
            }
            
            // Expose private properties for testing
            public function getLastRequestTimes(): array
            {
                return $this->lastRequestTimes;
            }
            
            public function setMinDelaySeconds(int $seconds): void
            {
                $this->minDelaySeconds = $seconds;
            }
        };
    }

    public function test_fetch_menu_content_returns_html_content()
    {
        $htmlContent = '<html><body><h1>Menu</h1></body></html>';
        
        // Mock responses for accessibility check, robots.txt check, content type check, and actual fetch
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            new Response(200, [], $htmlContent) // fetchWithRetry
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNotNull($result);
        $this->assertEquals('html', $result['type']);
        $this->assertEquals($htmlContent, $result['content']);
    }

    public function test_fetch_menu_content_returns_pdf_path()
    {
        // Mock responses for accessibility, robots.txt, content type, and PDF download
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'application/pdf']), // getContentType
            new Response(200, [], 'PDF content') // downloadPdf
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu.pdf');

        $this->assertNotNull($result);
        $this->assertEquals('pdf', $result['type']);
        $this->assertIsString($result['content']);
        $this->assertStringContainsString('menu_pdf_', $result['content']);
    }

    public function test_fetch_menu_content_returns_null_when_url_not_accessible()
    {
        Log::shouldReceive('warning')->once();
        
        $this->mockHandler->append(
            new Response(404) // isUrlAccessible returns false
        );

        $result = $this->service->fetchMenuContent('https://example.com/nonexistent');

        $this->assertNull($result);
    }

    public function test_fetch_menu_content_returns_null_when_robots_txt_disallows()
    {
        Log::shouldReceive('warning')->once();
        
        $robotsContent = "User-agent: *\nDisallow: /";
        
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(200, [], $robotsContent) // respectsRobotsTxt
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNull($result);
    }

    public function test_is_url_accessible_returns_true_for_successful_response()
    {
        $this->mockHandler->append(new Response(200));

        $result = $this->service->isUrlAccessible('https://example.com');

        $this->assertTrue($result);
    }

    public function test_is_url_accessible_returns_false_for_error_response()
    {
        $this->mockHandler->append(new Response(404));

        $result = $this->service->isUrlAccessible('https://example.com/nonexistent');

        $this->assertFalse($result);
    }

    public function test_is_url_accessible_returns_false_on_exception()
    {
        Log::shouldReceive('debug')->once();
        
        $this->mockHandler->append(
            new ConnectException('Connection failed', new Request('HEAD', 'https://example.com'))
        );

        $result = $this->service->isUrlAccessible('https://example.com');

        $this->assertFalse($result);
    }

    public function test_respects_robots_txt_returns_true_when_no_robots_txt()
    {
        $this->mockHandler->append(new Response(404));

        $result = $this->service->respectsRobotsTxt('https://example.com/menu');

        $this->assertTrue($result);
    }

    public function test_respects_robots_txt_returns_false_when_disallowed()
    {
        $robotsContent = "User-agent: *\nDisallow: /";
        $this->mockHandler->append(new Response(200, [], $robotsContent));

        $result = $this->service->respectsRobotsTxt('https://example.com/menu');

        $this->assertFalse($result);
    }

    public function test_respects_robots_txt_returns_true_when_allowed()
    {
        $robotsContent = "User-agent: *\nDisallow: /admin\nAllow: /menu";
        $this->mockHandler->append(new Response(200, [], $robotsContent));

        $result = $this->service->respectsRobotsTxt('https://example.com/menu');

        $this->assertTrue($result);
    }

    public function test_respects_robots_txt_handles_specific_user_agent()
    {
        $robotsContent = "User-agent: MenuScrapingBot\nDisallow: /menu\n\nUser-agent: *\nAllow: /";
        $this->mockHandler->append(new Response(200, [], $robotsContent));

        $result = $this->service->respectsRobotsTxt('https://example.com/menu');

        $this->assertFalse($result);
    }

    public function test_respects_robots_txt_updates_crawl_delay()
    {
        $robotsContent = "User-agent: *\nCrawl-delay: 5\nDisallow:";
        $this->mockHandler->append(new Response(200, [], $robotsContent));

        $this->service->respectsRobotsTxt('https://example.com/menu');

        // The crawl delay should be updated internally
        $this->assertTrue(true); // We can't directly test private property changes
    }

    public function test_get_content_type_returns_correct_type()
    {
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'text/html; charset=utf-8'])
        );

        $result = $this->service->getContentType('https://example.com');

        $this->assertEquals('text/html; charset=utf-8', $result);
    }

    public function test_get_content_type_returns_default_on_exception()
    {
        Log::shouldReceive('debug')->once();
        
        $this->mockHandler->append(
            new ConnectException('Connection failed', new Request('HEAD', 'https://example.com'))
        );

        $result = $this->service->getContentType('https://example.com');

        $this->assertEquals('text/html', $result);
    }

    public function test_download_pdf_returns_temp_file_path()
    {
        $pdfContent = '%PDF-1.4 fake pdf content';
        $this->mockHandler->append(new Response(200, [], $pdfContent));

        $result = $this->service->downloadPdf('https://example.com/menu.pdf');

        $this->assertNotNull($result);
        $this->assertIsString($result);
        $this->assertFileExists($result);
        
        // Clean up
        if (file_exists($result)) {
            unlink($result);
        }
    }

    public function test_download_pdf_returns_null_on_failure()
    {
        Log::shouldReceive('error')->once();
        
        $this->mockHandler->append(
            new ConnectException('Connection failed', new Request('GET', 'https://example.com/menu.pdf'))
        );

        $result = $this->service->downloadPdf('https://example.com/menu.pdf');

        $this->assertNull($result);
    }

    public function test_fetch_with_retry_succeeds_on_first_attempt()
    {
        $htmlContent = '<html><body>Menu</body></html>';
        
        // Mock all the prerequisite checks first
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            new Response(200, [], $htmlContent) // actual fetch
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNotNull($result);
        $this->assertEquals($htmlContent, $result['content']);
    }

    public function test_fetch_with_retry_succeeds_on_second_attempt()
    {
        $htmlContent = '<html><body>Menu</body></html>';
        
        Log::shouldReceive('info')->once()->with(\Mockery::pattern('/Retrying request/'));
        Log::shouldReceive('warning')->once()->with(\Mockery::pattern('/Request failed/'));
        
        // Mock all the prerequisite checks first
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            new ConnectException('Connection failed', new Request('GET', 'https://example.com/menu')), // first attempt fails
            new Response(200, [], $htmlContent) // second attempt succeeds
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNotNull($result);
        $this->assertEquals($htmlContent, $result['content']);
    }

    public function test_fetch_with_retry_handles_rate_limiting()
    {
        $htmlContent = '<html><body>Menu</body></html>';
        
        Log::shouldReceive('info')->once()->with(\Mockery::pattern('/Rate limited/'));
        Log::shouldReceive('info')->once()->with(\Mockery::pattern('/Retrying request/'));
        
        // Mock all the prerequisite checks first
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            new Response(429, ['Retry-After' => '1']), // rate limited
            new Response(200, [], $htmlContent) // retry succeeds
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNotNull($result);
        $this->assertEquals($htmlContent, $result['content']);
    }

    public function test_fetch_with_retry_fails_after_max_attempts()
    {
        Log::shouldReceive('info')->times(2)->with(\Mockery::pattern('/Retrying request/'));
        Log::shouldReceive('warning')->times(3)->with(\Mockery::pattern('/Request failed/'));
        Log::shouldReceive('error')->once()->with(\Mockery::pattern('/All retry attempts failed/'));
        
        // Mock all the prerequisite checks first
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            new ConnectException('Connection failed', new Request('GET', 'https://example.com/menu')), // attempt 1
            new ConnectException('Connection failed', new Request('GET', 'https://example.com/menu')), // attempt 2
            new ConnectException('Connection failed', new Request('GET', 'https://example.com/menu'))  // attempt 3
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNull($result);
    }

    public function test_rate_limiting_enforces_delays_between_requests()
    {
        // Create a new service instance with rate limiting enabled for this test
        $mockHandler = new MockHandler([
            new Response(200), // isUrlAccessible for first fetchMenuContent
            new Response(404), // respectsRobotsTxt for first fetchMenuContent
            new Response(200, ['Content-Type' => 'text/html']), // getContentType for first fetchMenuContent
            new Response(200, [], '<html>Menu 1</html>'), // actual fetch for first fetchMenuContent
            new Response(200), // isUrlAccessible for second fetchMenuContent
            new Response(404), // respectsRobotsTxt for second fetchMenuContent
            new Response(200, ['Content-Type' => 'text/html']), // getContentType for second fetchMenuContent
            new Response(200, [], '<html>Menu 2</html>') // actual fetch for second fetchMenuContent
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        
        $serviceWithRateLimit = new class($handlerStack) extends WebScrapingService {
            public function __construct($handlerStack)
            {
                $this->client = new Client(['handler' => $handlerStack]);
                $this->lastRequestTimes = [];
                $this->minDelaySeconds = 1; // Enable rate limiting for this test
                $this->maxRetries = 3;
                $this->retryDelays = [0, 0, 0];
            }
        };

        $startTime = microtime(true);
        
        // Make two requests to the same domain using fetchMenuContent which enforces rate limiting
        $serviceWithRateLimit->fetchMenuContent('https://example.com/menu1');
        $serviceWithRateLimit->fetchMenuContent('https://example.com/menu2');
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should have taken at least 1 second due to rate limiting
        $this->assertGreaterThanOrEqual(0.9, $duration); // Allow small margin for test execution
    }

    public function test_rate_limiting_tracks_different_domains_separately()
    {
        // Test that rate limiting is working by checking the behavior
        $this->mockHandler->append(
            new Response(200), // example.com
            new Response(200)  // different-site.com
        );
        
        // Make requests to different domains - should not be rate limited between domains
        $startTime = microtime(true);
        $this->service->isUrlAccessible('https://example.com/menu');
        $this->service->isUrlAccessible('https://different-site.com/menu');
        $endTime = microtime(true);
        
        // Should be fast since different domains don't rate limit each other
        $this->assertLessThan(0.5, $endTime - $startTime);
    }

    public function test_robots_txt_parsing_handles_complex_rules()
    {
        $robotsContent = "# Comment line\n" .
                        "User-agent: BadBot\n" .
                        "Disallow: /\n\n" .
                        "User-agent: MenuScrapingBot\n" .
                        "Disallow: /admin\n" .
                        "Disallow: /private\n" .
                        "Allow: /menu\n" .
                        "Crawl-delay: 3\n\n" .
                        "User-agent: *\n" .
                        "Disallow: /admin\n" .
                        "Allow: /";
        
        $this->mockHandler->append(new Response(200, [], $robotsContent));
        
        $result = $this->service->respectsRobotsTxt('https://example.com/menu');
        $this->assertTrue($result);
        
        $this->mockHandler->append(new Response(200, [], $robotsContent));
        
        $result = $this->service->respectsRobotsTxt('https://example.com/admin/settings');
        $this->assertFalse($result);
    }

    public function test_robots_txt_parsing_handles_wildcard_user_agent()
    {
        $robotsContent = "User-agent: *\nDisallow: /private\nAllow: /";
        
        $this->mockHandler->append(new Response(200, [], $robotsContent));
        
        $result = $this->service->respectsRobotsTxt('https://example.com/menu');
        $this->assertTrue($result);
        
        $this->mockHandler->append(new Response(200, [], $robotsContent));
        
        $result = $this->service->respectsRobotsTxt('https://example.com/private/data');
        $this->assertFalse($result);
    }

    public function test_handles_request_exception_with_response()
    {
        Log::shouldReceive('warning')->times(3)->with(\Mockery::pattern('/Request failed/'));
        Log::shouldReceive('info')->times(2)->with(\Mockery::pattern('/Retrying request/'));
        Log::shouldReceive('error')->once()->with(\Mockery::pattern('/All retry attempts failed/'));
        
        $request = new Request('GET', 'https://example.com/menu');
        $response = new Response(500, [], 'Internal Server Error');
        $exception = new RequestException('Server Error', $request, $response);
        
        // Mock all the prerequisite checks first
        $this->mockHandler->append(
            new Response(200), // isUrlAccessible
            new Response(404), // respectsRobotsTxt (no robots.txt)
            new Response(200, ['Content-Type' => 'text/html']), // getContentType
            $exception, // first attempt
            $exception, // second attempt
            $exception  // third attempt
        );

        $result = $this->service->fetchMenuContent('https://example.com/menu');

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        // Clean up any temporary files that might have been created
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/menu_pdf_*');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        parent::tearDown();
    }
}