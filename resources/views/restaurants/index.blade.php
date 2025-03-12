<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurants</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Restaurants</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($restaurants as $restaurant)
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-2"><a href="/restaurant/{{ $restaurant->id }}" class="text-blue-600 hover:text-blue-800">{{ $restaurant->name }}</a></h2>
                    <p class="text-gray-600 mb-2">{{ $restaurant->address }}</p>
                    @if($restaurant->phone)
                        <p class="text-gray-600 mb-2">📞 {{ $restaurant->phone }}</p>
                    @endif
                    @if($restaurant->website)
                        <p class="text-gray-600 mb-2">🍽️ {{ $restaurant->website }}</p>
                    @endif
                    @if($restaurant->cuisine_type)
                        <p class="text-gray-600 mb-2">🍽️ {{ $restaurant->cuisine_type }}</p>
                    @endif
                    @if($restaurant->price_range)
                        <p class="text-gray-600 mb-2">💰 {{ $restaurant->price_range }}</p>
                    @endif
                    @if($restaurant->opening_hours)
                        <p class="text-gray-600 mb-2">🕒 {{ $restaurant->opening_hours }}</p>
                    @endif
                    @if($restaurant->description)
                        <p class="text-gray-700 mt-4">{{ $restaurant->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>