<x-public-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Restaurants
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($restaurants as $restaurant)
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                    <h2 class="text-xl font-semibold mb-2">
                        <a href="/restaurant/{{ $restaurant->id }}" class="text-orange-600 hover:text-orange-800 transition-colors duration-200">
                            {{ $restaurant->name }}
                        </a>
                    </h2>
                    <p class="text-gray-600 mb-2">📍 {{ $restaurant->address }}</p>
                    @if($restaurant->phone)
                    <p class="text-gray-600 mb-2">📞 {{ $restaurant->phone }}</p>
                    @endif
                    @if($restaurant->website)
                    <p class="text-gray-600 mb-2">🌐
                        <a href="{{ $restaurant->website }}" target="_blank" class="text-orange-600 hover:text-orange-800 underline">
                            {{ $restaurant->website }}
                        </a>
                    </p>
                    @endif
                    @if($restaurant->cuisine_type)
                    <p class="text-gray-600 mb-2">🍽️ {{ $restaurant->cuisine_type }}</p>
                    @endif
                    @if($restaurant->price_range)
                    <p class="text-gray-600 mb-2">💰 {{ $restaurant->price_range }}</p>
                    @endif
                    @if($restaurant->rating)
                    <p class="text-gray-600 mb-2">⭐ {{ $restaurant->rating }}/5</p>
                    @endif
                    @if($restaurant->opening_hours)
                    <p class="text-gray-600 mb-2">🕒 {{ $restaurant->opening_hours }}</p>
                    @endif
                    @if($restaurant->description)
                    <p class="text-gray-700 mt-4">{{ Str::limit($restaurant->description, 150) }}</p>
                    @endif
                </div>
                @endforeach
            </div>

            @if($restaurants->isEmpty())
            <div class="text-center py-12">
                <div class="flex flex-col items-center">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No restaurants found</h3>
                    <p class="text-gray-500">Check back later for new restaurant listings.</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-public-layout>