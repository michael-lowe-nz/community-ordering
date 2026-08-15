<x-public-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center">
                <flux:avatar badge="{{ $restaurant->orders()->count() }}" name="{{ $restaurant->name }}" color="auto"
                    color:seed="{{ $restaurant->id }}" />
                <h1 class="pl-2 text-xl font-semibold text-slate-900">{{ $restaurant->name }}</h1>
            </div>

            <form action="{{ route('restaurants.orders.store', $restaurant) }}" method="POST" class="inline-block">
                @csrf
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    Add New Order
                </button>
            </form>
        </div>
    </div>

    <div class="min-h-screen pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 xl:gap-5 items-stretch">
                @foreach ($orders as $order)
                    <div class="h-full">
                        <x-order-receipt :order="$order" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-public-layout>
