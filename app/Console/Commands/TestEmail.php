<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'test:email {email=test@example.com}';
    protected $description = 'Send a test email to verify Mailpit setup';

    public function handle()
    {
        $email = $this->argument('email');
        
        Mail::raw('This is a test email from Laravel to verify Mailpit is working!', function ($message) use ($email) {
            $message->to($email)
                    ->subject('Mailpit Test Email');
        });

        $this->info("Test email sent to: {$email}");
        $this->info("Check Mailpit UI at: http://localhost:8025");
    }
}