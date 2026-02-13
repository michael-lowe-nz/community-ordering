<x-public-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex py-8">
        <div class="flex items-center mb-4">
            <flux:avatar badge="{{ $restaurant->orders()->count() }}" name="{{ $restaurant->name }}" color="auto"
                color:seed="{{ $restaurant->id }}" />
        </div>
        <h1 class="pl-2 text-xl">{{ $restaurant->name }}</h1>
    </div>

    <form class="max-w-7xl mx-auto px-4 mb-4 cursor-pointer" action="{{ route('restaurants.orders.store', $restaurant) }}"
        method="POST" class="inline">
        @csrf
        <button type="submit"
            class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Add
            New Order</button>
    </form>

    <div class="min-h-screen">
        <div class="md:grid md:grid-cols-4 gap-4">
            @foreach ($orders as $order)
                <x-order-receipt :order="$order" />
            @endforeach
        </div>
    </div>
</x-public-layout>
