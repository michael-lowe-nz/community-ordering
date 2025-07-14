<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Restaurants
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($restaurants as $restaurant)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold mb-2"><a href="/restaurant/{{ $restaurant->id }}" class="text-blue-600 hover:text-blue-800">{{ $restaurant->name }}</a></h2>
                        <p class="text-gray-600 mb-2">{{ $restaurant->address }}</p>
                        @if($restaurant->phone)
                            <p class="text-gray-600 mb-2">📞 {{ $restaurant->phone }}</p>
                        @endif
                        @if($restaurant->website)
                            <p class="text-gray-600 mb-2">🌐 {{ $restaurant->website }}</p>
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
                            <p class="text-gray-700 mt-4">{{ $restaurant->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>