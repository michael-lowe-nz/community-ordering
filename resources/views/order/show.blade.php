<x-public-layout>
    <x-slot name="header">
        <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6">
            <div class="flex flex-col leading-tight">
                <h1 class="text-xl font-semibold text-gray-800 sm:text-2xl">Order Details</h1>
                <a href="{{ route('restaurants.show', $restaurant) }}" class="mt-1 text-xl font-semibold text-orange-600 transition hover:text-orange-700 sm:text-2xl">
                    {{ $restaurant->name }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="pb-72 md:pb-0">
        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.2em] text-orange-600">Order</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">{{ $order->title }}</h2>
                            <p class="mt-2 text-sm text-gray-600">{{ $order->formattedCreatedAt }} · {{ $order->shortFormattedCreatedAt }}</p>
                        </div>
                        <a href="{{ route('restaurants.show', $restaurant) }}" class="inline-flex items-center justify-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 transition hover:bg-gray-300">
                            Back
                        </a>
                    </div>
                </div>

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

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    @livewire('order.create', ['restaurant' => $restaurant, 'order' => $order])
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur-sm shadow-[0_-12px_28px_rgba(15,23,42,0.08)] md:static md:z-auto md:border-0 md:bg-transparent md:shadow-none">
        <div class="mx-auto max-w-2xl px-4 py-3 sm:px-6 md:px-0 md:py-0">
            <div class="rounded-t-2xl border border-gray-200 bg-white shadow-sm md:rounded-none md:border-0 md:shadow-none">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 md:hidden">
                    <h2 class="text-sm font-semibold text-gray-700">Current items</h2>
                    <span class="text-xs text-gray-500">{{ $orderItems->count() }} {{ $orderItems->count() === 1 ? 'item' : 'items' }}</span>
                </div>
                <div class="max-h-[28vh] overflow-y-auto px-3 py-3 md:max-h-none md:px-0 md:py-0">
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
                        <p class="text-sm text-gray-500">No items added to this order yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
