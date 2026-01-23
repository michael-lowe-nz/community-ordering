<x-public-layout>
    <x-slot name="header">
    <h1 class="font-semibold text-xxl text-gray-800 leading-tight">
        {{ $restaurant->name }}
    </h1>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order #{{ $order->id }} Details</h2>
    <ul>
        @foreach ($orderItems as $item)
            <li>{{ $item->name }} x {{ $item->quantity }}</li>
        @endforeach
    </ul>
</x-slot>
    <h1>Add to order</h1>
    <form action="{{ route('restaurants.orders.addToOrder', [$restaurant, $order]) }}" method="POST">
        @csrf
        <label>Item Name:</label>
        <input type="text" name="name" placeholder="Item Name" class="border border-gray-300 rounded-md p-2 w-full mb-4">
        <label>Quantity:</label>
        <input type="number" name="quantity" placeholder="Quantity" class="border border-gray-300 rounded-md p-2 w-full mb-4">
        <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Submit Order</button>
    </form>
</x-public-layout>