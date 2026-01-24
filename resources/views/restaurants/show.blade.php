<x-public-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $restaurant->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-8 mt-4">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between mb-6">
                    <div>
                        <h1 class="text-4xl font-bold mb-2 text-gray-900">{{ $restaurant->name }}</h1>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Previous Orders</h2>
                        @foreach ($orders as $order)
                        <a href="{{ route('restaurants.orders.show', [$restaurant, $order]) }}" class="border border-gray-200 rounded-lg p-4 hover:shadow-lg transition-shadow duration-200">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">Order #{{ $order->id }}</h3>
                            <p>Placed on {{$order->created_at}}</p>
                        </a>
                        @endforeach                        
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <div class="mb-12">
        <form action="{{ route('restaurants.orders.store', $restaurant) }}" method="POST" class="inline">
        @csrf
            <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Add New Order</button>
        </form>
    </div>
</x-public-layout>