<x-public-layout>
    <x-slot name="header">
    <h1 class="font-semibold text-xxl text-gray-800 leading-tight">
        {{ $restaurant->name }}
    </h1>
    <h2 class="font-semibold text-md text-gray-800 leading-tight">{{ $order->formatted_created_at }}</h2>
</x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
        </div>
    </div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1>Add to order</h1>
            <form action="{{ route('restaurants.orders.addToOrder', [$restaurant, $order]) }}" method="POST">
                @csrf
                <label>Item Name:</label>
                <input type="text" name="name" placeholder="Item Name" class="border border-gray-300 rounded-md p-2 w-full mb-4" autofocus>
                <label>Quantity:</label>
                <input type="number" name="quantity" placeholder="quantity" value="1" class="border border-gray-300 rounded-md p-2 w-full mb-4">
                <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Add</button>
            </form>
        </div>
    </div>
</x-public-layout>