<?php

namespace Tests\Feature;

use App\Jobs\MenuScrapingJob;
use App\Models\Restaurant;
use App\Services\MenuScrapingServiceInterface;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MenuScrapingJobTest extends TestCase
{
    /**
     * Test that the job can be queued.
     */
    public function test_job_can_be_queued()
    {
        // Fake the queue
        Queue::fake();
        
        // Create a job with null parameters (for frequency-based scraping)
        MenuScrapingJob::dispatch(null, 'daily');
        
        // Assert the job was pushed to the queue
        Queue::assertPushed(MenuScrapingJob::class);
    }
    
    /**
     * Test that the job can be scheduled with different frequencies.
     */
    public function test_job_can_be_scheduled_with_different_frequencies()
    {
        // Verify that the job can be instantiated with different frequencies
        $dailyJob = new MenuScrapingJob(null, 'daily');
        $weeklyJob = new MenuScrapingJob(null, 'weekly');
        $monthlyJob = new MenuScrapingJob(null, 'monthly');
        
        // Simple assertion to verify the jobs were created
        $this->assertInstanceOf(MenuScrapingJob::class, $dailyJob);
        $this->assertInstanceOf(MenuScrapingJob::class, $weeklyJob);
        $this->assertInstanceOf(MenuScrapingJob::class, $monthlyJob);
    }
}