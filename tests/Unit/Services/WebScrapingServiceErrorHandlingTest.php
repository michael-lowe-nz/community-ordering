<?php

namespace Tests\Unit\Services;

use App\Services\WebScrapingService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Tests\TestCase;
use ReflectionClass;

class WebScrapingServiceErrorHandlingTest extends TestCase
{
    protected WebScrapingService $webScrapingService;
    protected MockHandler $mockHandler;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock handler
        $this->mockHandler = new MockHandler();
        
        // Create a handler stack with the mock handler
        $handlerStack = HandlerStack::create($this->mockHandler);
        
        // Create a client with the handler stack
        $client = new Client(['handler' => $handlerStack]);
        
        // Create the web scraping service
        $this->webScrapingService = new WebScrapingService();
        
        // Use reflection to replace the client with our mock client
        $reflection = new ReflectionClass($this->webScrapingService);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->webScrapingService, $client);
    }
    
    /** @test */
    public function it_handles_rate_limiting_with_retry_after_header()
    {
        // Queue responses: first a 429 with Retry-After, then a 200
        $this->mockHandler->append(
            new Response(429, ['Retry-After' => '1']),
            new Response(200, [], 'Success content')
        );
        
        // Call the fetchMenuContent method
        $result = $this->webScrapingService->fetchMenuContent('https://example.com/menu');
        
        // Assert that we got a successful result after retrying
        $this->assertNotNull($result);
        $this->assertEquals('html', $result['type']);
        $this->assertEquals('Success content', $result['content']);
    }
    
    /** @test */
    public function it_handles_server_errors_with_retry()
    {
        // Queue responses: first a 500, then a 200
        $this->mockHandler->append(
            new Response(500),
            new Response(200, [], 'Success after server error')
        );
        
        // Call the fetchMenuContent method
        $result = $this->webScrapingService->fetchMenuContent('https://example.com/menu');
        
        // Assert that we got a successful result after retrying
        $this->assertNotNull($result);
        $this->assertEquals('html', $result['type']);
        $this->assertEquals('Success after server error', $result['content']);
    }
    
    /** @test */
    public function it_handles_connection_exceptions()
    {
        // Create a connection exception
        $request = new Request('GET', 'https://example.com/menu');
        $exception = new ConnectException('Connection error', $request);
        
        // Queue the exception, then a successful response
        $this->mockHandler->append(
            $exception,
            new Response(200, [], 'Success after connection error')
        );
        
        // Call the fetchMenuContent method
        $result = $this->webScrapingService->fetchMenuContent('https://example.com/menu');
        
        // Assert that we got a successful result after retrying
        $this->assertNotNull($result);
        $this->assertEquals('html', $result['type']);
        $this->assertEquals('Success after connection error', $result['content']);
    }
    
    /** @test */
    public function it_gives_up_after_max_retries()
    {
        // Queue multiple 500 responses (more than max retries)
        $this->mockHandler->append(
            new Response(500),
            new Response(500),
            new Response(500),
            new Response(500) // One more than the default max retries (3)
        );
        
        // Call the fetchMenuContent method
        $result = $this->webScrapingService->fetchMenuContent('https://example.com/menu');
        
        // Assert that we got null after all retries failed
        $this->assertNull($result);
    }
    
    /** @test */
    public function it_does_not_retry_for_client_errors()
    {
        // Queue a 404 response
        $this->mockHandler->append(
            new Response(404)
        );
        
        // Call the fetchMenuContent method
        $result = $this->webScrapingService->fetchMenuContent('https://example.com/menu');
        
        // Assert that we got null and didn't retry
        $this->assertNull($result);
        $this->assertEquals(0, $this->mockHandler->count()); // No more responses in queue
    }
    
    /** @test */
    public function it_validates_html_content()
    {
        // Use reflection to access the private isValidHtml method
        $reflection = new ReflectionClass($this->webScrapingService);
        $method = $reflection->getMethod('isValidHtml');
        $method->setAccessible(true);
        
        // Test cases
        $validHtml = '<html><body><div>Menu content</div></body></html>';
        $partialHtml = '<div>Menu content</div>';
        $nonHtml = 'Plain text without any HTML tags';
        
        // Assert results
        $this->assertTrue($method->invoke($this->webScrapingService, $validHtml));
        $this->assertTrue($method->invoke($this->webScrapingService, $partialHtml));
        $this->assertFalse($method->invoke($this->webScrapingService, $nonHtml));
    }
    
    /** @test */
    public function it_categorizes_request_errors_correctly()
    {
        // Use reflection to access the private categorizeRequestError method
        $reflection = new ReflectionClass($this->webScrapingService);
        $method = $reflection->getMethod('categorizeRequestError');
        $method->setAccessible(true);
        
        // Test cases
        $connectionError = 'Failed to connect to example.com';
        $timeoutError = 'Operation timed out';
        $sslError = 'SSL certificate problem';
        $rateLimitError = 'Rate limit exceeded (429)';
        $accessDeniedError = 'Access denied (403 Forbidden)';
        $notFoundError = 'Not found (404)';
        $serverError = 'Server error (500)';
        $unknownError = 'Something went wrong';
        
        // Assert results
        $this->assertEquals('connection', $method->invoke($this->webScrapingService, $connectionError)['type']);
        $this->assertEquals('timeout', $method->invoke($this->webScrapingService, $timeoutError)['type']);
        $this->assertEquals('ssl', $method->invoke($this->webScrapingService, $sslError)['type']);
        $this->assertEquals('rate_limit', $method->invoke($this->webScrapingService, $rateLimitError)['type']);
        $this->assertEquals('access_denied', $method->invoke($this->webScrapingService, $accessDeniedError)['type']);
        $this->assertEquals('not_found', $method->invoke($this->webScrapingService, $notFoundError)['type']);
        $this->assertEquals('server_error', $method->invoke($this->webScrapingService, $serverError)['type']);
        $this->assertEquals('unknown', $method->invoke($this->webScrapingService, $unknownError)['type']);
    }
}