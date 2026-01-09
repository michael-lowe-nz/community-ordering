<?php

namespace App\Console\Commands;

use App\Services\PdfParsingService;
use Illuminate\Console\Command;

class TestPdfParsing extends Command
{
    protected $signature = 'pdf:test-parsing {text?}';
    protected $description = 'Test PDF parsing functionality with sample menu text';

    public function handle(PdfParsingService $pdfParsingService)
    {
        $sampleText = $this->argument('text') ?? $this->getDefaultSampleText();
        
        $this->info('Testing PDF parsing with sample menu text...');
        $this->line('');
        
        $result = $pdfParsingService->parseMenuStructure($sampleText);
        
        $this->info("Found {$result['total_items']} menu items:");
        $this->line('');
        
        foreach ($result['items'] as $index => $item) {
            $this->line(sprintf(
                "%d. %s - $%.2f",
                $index + 1,
                $item['name'],
                $item['price']
            ));
        }
        
        $this->line('');
        $this->info('PDF parsing test completed successfully!');
        
        return 0;
    }
    
    private function getDefaultSampleText(): string
    {
        return "
        APPETIZERS
        
        Buffalo Wings $12.99
        Mozzarella Sticks - $8.50
        Nachos Supreme 14.00
        
        MAIN COURSES
        
        Grilled Salmon $24.99
        Ribeye Steak - $32.00
        Chicken Parmesan 18.50
        Vegetarian Pasta $16.99
        
        DESSERTS
        
        Chocolate Cake $7.99
        Ice Cream Sundae - $5.50
        ";
    }
}