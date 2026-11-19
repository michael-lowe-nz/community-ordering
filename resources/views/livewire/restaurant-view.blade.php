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
                <form method="POST" action="{{ route('restaurants.orders.store', $restaurant) }}">
                    @csrf
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 sm:w-auto">Start an order</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 sm:w-auto">Log in to start an order</a>
            @endauth
        </div>
    </header>

    {{-- Tabs --}}
    @auth
        <div class="mb-4 flex overflow-hidden rounded-lg border border-zinc-200 text-sm font-medium">
            <button
                type="button"
                wire:click="selectTab('recent')"
                class="flex-1 cursor-pointer px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'recent' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
            >Recent orders</button>
            <button
                type="button"
                wire:click="selectTab('your')"
                class="flex-1 cursor-pointer border-l border-zinc-200 px-3 py-2 transition hover:bg-zinc-50 {{ $tab === 'your' ? 'bg-zinc-900 text-white hover:bg-zinc-800' : 'bg-white text-zinc-600' }}"
            >Your orders</button>
        </div>
    @endauth

    {{-- Orders list header --}}
    <div class="mb-3 flex items-center justify-between gap-3">
        <p class="text-sm text-zinc-500">{{ $this->orders->total() }} {{ $this->orders->total() === 1 ? 'order' : 'orders' }}</p>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600">
            <span class="font-medium">Filter: party size</span>
            <select
                wire:model.live="partySizeFilter"
                class="cursor-pointer rounded-md border border-zinc-300 bg-white px-2 py-1 text-sm text-zinc-900 transition hover:border-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200"
            >
                <option value="">All</option>
                @foreach (range(1, 20) as $size)
                    <option value="{{ $size }}">{{ $size }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Orders --}}
    @if ($this->orders->isEmpty())
        <p class="py-8 text-center text-sm text-zinc-500">No orders found.</p>
    @else
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
            @foreach ($this->orders as $order)
                <x-order-receipt :order="$order" :href="route('orders.show', $order)" :item-limit="6" wire:key="order-{{ $order->id }}" />
            @endforeach
        </div>

        @if ($this->orders->hasPages())
            <div class="pt-4">{{ $this->orders->links() }}</div>
        @endif
    @endif

</div>
