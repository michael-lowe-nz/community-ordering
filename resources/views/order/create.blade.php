<x-public-layout>
    <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $restaurant->name }}
    </h2>
</x-slot>
    <h1>Add the new order</h1>
    <form>
        <label>Item Name:</label>
        <input type="text" name="item" placeholder="Item Name" class="border border-gray-300 rounded-md p-2 w-full mb-4">
        <label>Quantity:</label>
        <input type="number" name="quantity" placeholder="Quantity" class="border border-gray-300 rounded-md p-2 w-full mb-4">
        <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Submit Order</button>
    </form>
</x-public-layout>