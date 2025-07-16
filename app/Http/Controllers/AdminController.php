<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRestaurants = Restaurant::count();
        return view('admin.dashboard', compact('totalRestaurants'));
    }

    public function addRestaurantsFromLocation(Request $request)
    {
        $request->validate([
            'location' => 'required|string|max:255',
        ]);

        $apiKey = config('services.google_places.api_key');
        if (!$apiKey) {
            return back()->with('error', 'Google Places API key not configured');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
            'query' => 'restaurants in ' . $request->location,
            'key' => $apiKey,
        ]);

        if (!$response->successful()) {
            return back()->with('error', 'Failed to fetch restaurants from Google Places API');
        }

        $data = $response->json();
        $addedCount = 0;
        $addedRestaurants = [];

        foreach ($data['results'] ?? [] as $place) {
            $existing = Restaurant::where('google_place_id', $place['place_id'])->first();
            if ($existing) continue;

            $restaurant = Restaurant::create([
                'name' => $place['name'],
                'address' => $place['formatted_address'] ?? '',
                'google_place_id' => $place['place_id'],
                'rating' => $place['rating'] ?? null,
                'price_range' => isset($place['price_level']) ? str_repeat('$', $place['price_level']) : null,
            ]);
            
            $addedRestaurants[] = [
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'rating' => $restaurant->rating
            ];
            $addedCount++;
        }

        $totalRestaurants = Restaurant::count();
        return back()->with([
            'success' => "Added {$addedCount} restaurants from {$request->location}",
            'addedCount' => $addedCount,
            'totalRestaurants' => $totalRestaurants,
            'addedRestaurants' => $addedRestaurants
        ]);
    }
}