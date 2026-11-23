@props([
    'order',
    'href' => null,
    'itemLimit' => null,
])

@php
    $orderedAt = $order->ordered_at;
    $pizzaMetres = $order->display_pizza_metres;
    $metresPerPerson = $pizzaMetres && $order->participant_count > 0
        ? rtrim(rtrim(number_format($order->pizza_metres / $order->participant_count, 2, '.', ''), '0'), '.')
        : null;
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
            @if ($orderedAt)
                <p class="receipt-eyebrow">{{ $orderedAt->format('D j M Y') }}</p>
            @endif
            <h2 class="receipt-title">{{ $order->title }}</h2>
            @if ($order->participant_count > 0)
                <p class="receipt-subtitle">{{ $order->participant_count }} {{ Str::plural('person', $order->participant_count) }}</p>
            @endif

            @if ($pizzaMetres)
                <p class="receipt-metres" data-test="pizza-metres">{{ $pizzaMetres }}m pizza @if ($metresPerPerson)<span class="font-normal text-gray-500">· {{ $metresPerPerson }}m each</span>@endif</p>
            @endif

            @if ($order->notes && $href)
                <p class="receipt-subtitle">{{ $order->notes }}</p>
            @endif

            @auth
                @if ($order->attendees || $order->attendee_notes)
                    <p class="receipt-subtitle" data-test="attendees">
                        @if ($order->attendees)<span class="font-semibold text-gray-600">Who:</span> {{ implode(', ', $order->attendees) }}@endif
                        @if ($order->attendee_notes) {{ $order->attendee_notes }}@endif
                    </p>
                @endif
            @endauth
        </div>

        <div class="flex flex-1 flex-col">
            @forelse ($shownItems as $item)
                <div class="receipt-item">
                    <p class="receipt-label">{{ $item->name }}@if ($item->variant) <span class="text-gray-500">({{ $item->variant }})</span>@endif @if ($item->notes)<span class="text-gray-500">· {{ $item->notes }}</span>@endif</p>
                    <p class="receipt-value">{{ $item->menuItem?->unit === \App\Models\MenuItem::UNIT_METRES ? $item->display_quantity.'m' : 'x '.$item->display_quantity }}</p>
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
