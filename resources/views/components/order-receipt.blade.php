@props([
    'order',
    'href' => null,
    'itemLimit' => null,
])

@php
    $orderedAt = $order->ordered_at ?? $order->created_at;
    $items = $order->items;
    $shownItems = $itemLimit ? $items->take($itemLimit) : $items;
    $hiddenItemCount = $items->count() - $shownItems->count();
    $element = $href ? 'a' : 'div';
@endphp

<{{ $element }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['receipt block h-full', 'transition hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400' => $href]) }}
>
    <div class="receipt-list flex h-full flex-col">
        <div class="receipt-header">
            <p class="receipt-eyebrow">{{ $orderedAt?->format('D j M Y') }}</p>
            <h2 class="receipt-title">{{ $order->title }}</h2>
            @if ($order->participant_count > 0)
                <p class="receipt-subtitle">{{ $order->participant_count }} {{ Str::plural('person', $order->participant_count) }}</p>
            @endif
        </div>

        <div class="flex flex-1 flex-col">
            @forelse ($shownItems as $item)
                <div class="receipt-item">
                    <p class="receipt-label">{{ $item->name }}@if ($item->variant) <span class="text-gray-500">({{ $item->variant }})</span>@endif</p>
                    <p class="receipt-value">x {{ $item->display_quantity }}</p>
                </div>
            @empty
                <div class="receipt-item receipt-item-empty">
                    <p class="receipt-label">No dishes yet.</p>
                </div>
            @endforelse

            @if ($hiddenItemCount > 0)
                <p class="receipt-more">+ {{ $hiddenItemCount }} more</p>
            @endif
        </div>

        {{ $slot }}
    </div>
</{{ $element }}>
