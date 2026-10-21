<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 text-slate-900 sm:px-6">
    <header>
        <a href="{{ route('restaurants.view', $order->restaurant) }}" class="text-sm text-orange-600 hover:text-orange-700 transition-colors">&larr; {{ $order->restaurant->name }}</a>
        <p class="mt-4 text-sm text-slate-500">Order from {{ $order->ordered_at?->format('j M Y') ?? $order->created_at?->format('j M Y') }}</p>
        <h1 class="text-3xl font-semibold text-slate-900">{{ $order->title }}</h1>
        <p class="mt-1 text-slate-600">{{ $order->participant_count }} {{ $order->participant_count === 1 ? 'person' : 'people' }}</p>
    </header>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <x-order-item-list :items="$items" :editable="false" />
    </section>

    @if ($order->notes)
        <p class="rounded-xl border border-slate-200 bg-white p-5 text-slate-700">{{ $order->notes }}</p>
    @endif

    <button type="button" wire:click="useAsStartingPoint" class="cursor-pointer rounded-lg bg-orange-600 px-4 py-3 font-semibold text-white transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">Use as starting point</button>
</div>