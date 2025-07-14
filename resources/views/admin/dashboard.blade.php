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

                <div class="border-t pt-8">
                    <h3 class="text-lg font-semibold mb-4">Add Restaurants by Location</h3>
                    
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <p class="font-semibold">{{ session('success') }}</p>
                            @if(session('addedCount') !== null)
                                <p class="mt-2">Restaurants added: <strong>{{ session('addedCount') }}</strong></p>
                                <p>New total: <strong>{{ session('totalRestaurants') }}</strong></p>
                            @endif
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.add-restaurants') }}" class="flex gap-4">
                        @csrf
                        <input 
                            type="text" 
                            name="location" 
                            placeholder="Enter location (e.g., Wellington)" 
                            class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <button 
                            type="submit" 
                            class="!bg-blue-600 hover:!bg-blue-700 !text-white font-bold py-2 px-4 rounded"
                        >
                            Add Restaurants
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>