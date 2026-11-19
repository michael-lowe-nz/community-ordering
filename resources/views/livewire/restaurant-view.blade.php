<div class="mx-auto max-w-2xl px-4 pb-10 pt-8 sm:px-6">

    {{-- Header --}}
    <header class="mb-6">
        <div class="mb-4 flex h-24 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-xs uppercase tracking-widest text-zinc-400" aria-hidden="true">Image</div>

        <h1 class="text-2xl font-bold text-zinc-900">
            {{ $restaurant->name }}
            @if ($restaurant->cuisine_type || $restaurant->location)
                <span class="ml-2 text-base font-normal text-zinc-500">
                    {{ implode(' · ', array_filter([$restaurant->cuisine_type, $restaurant->location])) }}
                </span>
            @endif
        </h1>

        <div class="mt-4">
            @auth
                @php($lastOrder = $this->lastOrder)
                <div
                    x-data="{ open: @js($start), people: {{ $lastOrder?->participant_count ?: 4 }} }"
                    x-on:keydown.escape.window="open = false"
                >
                    <button type="button" x-on:click="open = true" class="w-full cursor-pointer rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 sm:w-auto">Start an order</button>

                    {{-- Start sheet: pick a head count, then start blank or from your last order here. --}}
                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="start-sheet-title">
                        <div class="absolute inset-0 bg-zinc-900/30" x-on:click="open = false"></div>

                        <div class="relative w-full max-w-md rounded-t-2xl border-t-2 border-zinc-900 bg-white p-5 sm:rounded-2xl sm:border-2">
                            <div class="flex items-center justify-between">
                                <h2 id="start-sheet-title" class="text-lg font-bold text-zinc-900">Start an order</h2>
                                <button type="button" x-on:click="open = false" class="cursor-pointer rounded-md px-2 py-1 text-sm text-zinc-500 hover:text-zinc-900">Close</button>
                            </div>

                            <div class="mt-3 flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2">
                                <span class="text-sm text-zinc-600">How many people?</span>
                                <div class="flex items-center gap-2" role="group" aria-label="Number of people">
                                    <button type="button" x-on:click="people = Math.max(1, people - 1)" class="h-8 w-8 cursor-pointer rounded-full bg-zinc-100 font-bold" aria-label="Fewer people">−</button>
                                    <span class="w-8 text-center font-bold" x-text="people">{{ $lastOrder?->participant_count ?: 4 }}</span>
                                    <button type="button" x-on:click="people = Math.min(99, people + 1)" class="h-8 w-8 cursor-pointer rounded-full bg-zinc-100 font-bold" aria-label="More people">+</button>
                                </div>
                            </div>

                            <div class="mt-3 divide-y divide-zinc-100">
                                <form method="POST" action="{{ route('restaurants.orders.store', $restaurant) }}">
                                    @csrf
                                    <input type="hidden" name="participant_count" x-bind:value="people">
                                    <button type="submit" class="flex w-full cursor-pointer items-center justify-between gap-3 py-3 text-left">
                                        <span>
                                            <span class="block text-sm font-semibold text-zinc-900">Blank order</span>
                                            <span class="block text-xs text-zinc-500">Pick dishes from the menu</span>
                                        </span>
                                        <span class="text-zinc-400" aria-hidden="true">›</span>
                                    </button>
                                </form>

                                @if ($lastOrder)
                                    <form method="POST" action="{{ route('orders.duplicate', $lastOrder) }}">
                                        @csrf
                                        <input type="hidden" name="participant_count" x-bind:value="people">
                                        <button type="submit" class="flex w-full cursor-pointer items-center justify-between gap-3 py-3 text-left">
                                            <span>
                                                <span class="block text-sm font-semibold text-zinc-900">Your last order here</span>
                                                <span class="block text-xs text-zinc-500">{{ $lastOrder->title }} · {{ $lastOrder->items_count }} {{ Str::plural('dish', $lastOrder->items_count) }}</span>
                                            </span>
                                            <span class="text-zinc-400" aria-hidden="true">›</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('restaurants.start', $restaurant) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 sm:w-auto">Log in to start an order</a>
            @endauth
        </div>
    </header>

    {{-- Orders --}}
    <p class="mb-3 text-sm text-zinc-500">{{ $this->orders->total() }} {{ $this->orders->total() === 1 ? 'order' : 'orders' }}</p>

    @if ($this->orders->isEmpty())
        <p class="py-8 text-center text-sm text-zinc-500">No orders found.</p>
    @else
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
            @foreach ($this->orders as $order)
                <div wire:key="order-{{ $order->id }}" class="flex flex-col gap-2">
                    <x-order-receipt :order="$order" :href="route('orders.show', $order)" :item-limit="6" />

                    @auth
                        <form method="POST" action="{{ route('orders.duplicate', $order) }}" class="flex justify-end">
                            @csrf
                            <button type="submit" class="cursor-pointer rounded-md px-2 py-1 text-sm font-semibold text-zinc-900 underline-offset-4 hover:underline">Reuse &rarr;</button>
                        </form>
                    @else
                        <a href="{{ route('orders.reuse', $order) }}" class="self-end rounded-md px-2 py-1 text-sm font-semibold text-zinc-900 underline-offset-4 hover:underline">Reuse &rarr;</a>
                    @endauth
                </div>
            @endforeach
        </div>

        @if ($this->orders->hasPages())
            <div class="pt-4">{{ $this->orders->links() }}</div>
        @endif
    @endif

</div>
