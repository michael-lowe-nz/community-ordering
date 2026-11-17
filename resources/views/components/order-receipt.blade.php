<div class="receipt">
    <div class="receipt-list">
        <h1 class="receipt-title">{{ $order->formattedCreatedAt }}</h1>
        <p class="receipt-subtitle">{{ $order->shortFormattedCreatedAt }}@if ($order->participant_count) · {{ $order->participant_count }} people @endif</p>
        @forelse ($order->items as $item)
            <div class="receipt-item">
                <p class="receipt-label">{{ $item->name }}@if ($item->variant) <span class="text-gray-500">({{ $item->variant }})</span>@endif</p>
                <p class="receipt-value">x {{ $item->display_quantity }}</p>
            </div>
        @empty
            <div class="receipt-item">
                <div class="receipt-label">No items in this order.</div>
            </div>
        @endforelse
    </div>
</div>
