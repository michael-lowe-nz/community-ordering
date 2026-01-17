<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Restaurant Summary</h3>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600">{{ $totalRestaurants }}</p>
                        <p class="text-gray-600">Total Restaurants</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>