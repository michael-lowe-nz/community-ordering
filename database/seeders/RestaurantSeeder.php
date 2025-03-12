<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Restaurant;
use Carbon\Carbon;

class RestaurantSeeder extends Seeder
{
    /**
     * Get restaurants from Google Places API and seed the database.
     *
     * @return void
     */
    public function run()
    {
        $apiKey = env('GOOGLE_PLACES_API_KEY');
        
        if (!$apiKey) {
            $this->command->error('Google Places API key not found in environment variables');
            return;
        }
        
        $cities = [
            [
                'name' => 'Wellington',
                // 'location' => '37.7937,-122.3965',
                'location' => '-41.2865,174.7762',
                'radius' => 20000, // 20km radius
            ],
            // Add more cities as needed
        ];
        
        foreach ($cities as $city) {
            $this->seedRestaurantsForCity($city, $apiKey);
        }
        
        $this->command->info('Restaurant seeding completed successfully!');
    }
    
    
    /**
     * Seed restaurants for a specific city
     * 
     * @param array $city
     * @param string $apiKey
     * @return void
     */
    private function seedRestaurantsForCity($city, $apiKey)
    {
        $this->command->info("Fetching restaurants for {$city['name']}...");
        
        $pageToken = null;
        $count = 0;
        
        do {
            // Build the API request
            // $endpoint = 'https://places.googleapis.com/v1/places:searchNearby';
            $endpoint = 'https://places.googleapis.com/v1/places:searchText';
            
            [$lat, $lng] = explode(',', $city['location']);
            
            $requestBody = [
                'maxResultCount' => 20,
                'textQuery' => sprintf('chinese restaurants in %s', $city['name']),
                // 'fieldMask' => 'places.displayName,places.formattedAddress,places.priceLevel',
                // 'locationRestriction' => [
                //     'circle' => [
                //         'center' => [
                //             'latitude' => (float)$lat,
                //             'longitude' => (float)$lng
                //         ],
                //         'radius' => (float)$city['radius']
                //     ]
                // ]
            ];
            
            // Make the API request
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => implode(',', [
                    'places.displayName',
                    'places.formattedAddress', 
                    'places.priceLevel',
                    'places.id',
                    'places.nationalPhoneNumber',
                    'places.websiteUri'
                ]),
            ])->post($endpoint, $requestBody);
            
            if (!$response->successful()) {
                $this->command->error("API request failed: {$response->status()} - {$response->body()}");
                break;
            }
            
            $data = $response->json();
            // Print data in a readable format
            $this->command->info(print_r($data, true));            
            // Process and save each restaurant
            foreach ($data['places'] as $place) {
                $this->processAndSaveRestaurant($place, $city['name']);
                $count++;
            }
            
            // Check if there are more results to fetch
            $pageToken = $data['next_page_token'] ?? null;
            
        } while ($pageToken);
        
        $this->command->info("Added {$count} restaurants for {$city['name']}");
    }
    
    /**
     * Process a restaurant from Google Places and save to database
     * 
     * @param array $place
     * @param string $cityName
     * @return void
     */
    private function processAndSaveRestaurant($place, $cityName)
    {
        // Check if restaurant already exists
        $existingRestaurant = Restaurant::where('google_place_id', $place['id'])->first();
        // $existingRestaurant = null;

        if ($existingRestaurant) {
            // Update existing restaurant
            $existingRestaurant->update([
                'name' => $place['displayName']['text'],
                'google_place_id' => $place['id'],
                'address' => $place['formattedAddress'],
                'phone' => $place['nationalPhoneNumber'] ?? null,
                'website' => $place['websiteUri'] ?? null,
                'updated_at' => Carbon::now(),
            ]);
        } else {
            // Create new restaurant
            Restaurant::create([
                'name' => $place['displayName']['text'],
                'google_place_id' => $place['id'],
                'address' => $place['formattedAddress'],
                'phone' => $place['nationalPhoneNumber'] ?? null,
                'website' => $place['websiteUri'] ?? null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}