<x-public-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex py-8">
        <div class="flex items-center mb-4">
            <flux:avatar badge="{{$restaurant->orders()->count()}}" name="{{$restaurant->name}}" color="auto" color:seed="{{ $restaurant->id }}" />
        </div>
        <h1 class="pl-2 text-xl">{{ $restaurant->name }}</h1>
    </div>

    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @foreach ($orders as $order)
            <a href="{{ route('restaurants.orders.show', [$restaurant, $order]) }}" >
                <flux:card class="hover:bg-zinc-50 dark:hover:bg-zinc-700 mb-4">
                    <flux:heading size="m">
                        {{$order->created_at}}
                    </flux:heading>
                    <flux:subheading size="md">{{$restaurant->location}}</flux:subheading>
                </flux:card>
            </a>
            @endforeach                        
        </div>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form class="py-12" action="{{ route('restaurants.orders.store', $restaurant) }}" method="POST" class="inline">
            @csrf
                <button type="submit" class="text-white bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded-md font-semibold transition-colors duration-200">Add New Order</button>
            </form>
        </div>
    </div>
</x-public-layout>