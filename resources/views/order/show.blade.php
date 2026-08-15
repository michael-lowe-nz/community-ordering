<x-public-layout>
    <x-slot name="header">
        <div class="flex flex-col leading-tight">
            <h1 class="text-xl font-semibold text-gray-800 sm:text-2xl">Order Details</h1>
            <a href="{{ route('restaurants.show', $restaurant) }}" class="mt-1 text-xl font-semibold text-orange-600 transition hover:text-orange-700 sm:text-2xl">
                {{ $restaurant->name }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <!-- Order Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.2em] text-orange-600">Order</p>
                        <h2 class="mt-2 text-3xl font-semibold text-slate-900">{{ $order->title }}</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ $order->formattedCreatedAt }} · {{ $order->shortFormattedCreatedAt }}</p>
                    </div>
                    <a href="{{ route('restaurants.show', $restaurant) }}" class="inline-flex items-center justify-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 transition-colors duration-200 hover:bg-gray-300">
                        Back
                    </a>
                </div>
            </div>

            <!-- Order Items -->
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-slate-900">Items</h3>
                
                @if ($orderItems->count() > 0)
                    <div class="space-y-4">
                        @foreach ($orderItems as $item)
                            <div class="flex items-center justify-between border-b border-gray-100 pb-4 last:border-b-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $item->name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">x{{ $item->quantity }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No items added to this order yet.</p>
                @endif
            </div>

            <!-- Add Items Section -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-slate-900">Add Items</h3>
                <form action="{{ route('restaurants.orders.addToOrder', [$restaurant, $order]) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Item Name</label>
                        <input type="text" id="name" name="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 border px-3 py-2">
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 border px-3 py-2">
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        Add Item
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-public-layout>
