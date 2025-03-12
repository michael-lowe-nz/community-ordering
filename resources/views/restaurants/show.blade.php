<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $restaurant->name }} - Restaurant Details</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <a href="/restaurant" class="text-blue-500 hover:text-blue-700 mb-4 inline-block">← Back to Restaurants</a>
        
        <div class="bg-white rounded-lg shadow-md p-8 mt-4">
            <h1 class="text-4xl font-bold mb-4">{{ $restaurant->name }}</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600 mb-3">📍 {{ $restaurant->address }}</p>
                    @if($restaurant->phone)
                        <p class="text-gray-600 mb-3">📞 {{ $restaurant->phone }}</p>
                    @endif
                    @if($restaurant->cuisine_type)
                        <p class="text-gray-600 mb-3">🍽️ {{ $restaurant->cuisine_type }}</p>
                    @endif
                    @if($restaurant->price_range)
                        <p class="text-gray-600 mb-3">💰 {{ $restaurant->price_range }}</p>
                    @endif
                    @if($restaurant->opening_hours)
                        <p class="text-gray-600 mb-3">🕒 {{ $restaurant->opening_hours }}</p>
                    @endif
                </div>
                
                @if($restaurant->description)
                    <div class="md:col-span-2">
                        <h2 class="text-2xl font-semibold mb-3">About</h2>
                        <p class="text-gray-700">{{ $restaurant->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>