@props(['order'])

@php
    $itemNames = ($order->items ?? collect())
        ->map(fn ($item) => $item->menuItem?->name)
        ->filter()
        ->values();
    $preview = $itemNames->take(2)->implode(', ');
    $remainingItems = max(0, $itemNames->count() - 2);
@endphp

<article {{ $attributes->merge(['class' => 'border-b border-slate-200 py-4']) }}>
    <a href="{{ route('orders.show', $order) }}" class="flex items-start justify-between gap-4 hover:opacity-75 transition-opacity">
        <div>
            <p class="font-medium text-slate-900">{{ $order->ordered_at?->format('j M Y') ?? $order->created_at?->format('j M Y') }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $order->participant_count }} {{ $order->participant_count === 1 ? 'person' : 'people' }}</p>
        </div>
        <p class="text-right text-sm text-slate-600">
            {{ $preview ?: 'No items recorded' }}@if ($remainingItems > 0) +{{ $remainingItems }} more @endif
        </p>
    </a>
</article>