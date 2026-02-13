<div class="receipt">
    <div class="receipt-list">
        <h1 class="receipt-title">{{ $order->formattedCreatedAt }}</h1>
        <p class="receipt-subtitle">{{ $order->shortFormattedCreatedAt }}</p>
        @forelse ($order->items as $item)
            <div class="receipt-item">
                <p class="receipt-label">{{ $item->name }}</p>
                <p class="receipt-value">x {{ $item->quantity }}</p>
            </div>
        @empty
            <div class="receipt-item">
                <div class="receipt-label">No items in this order.</div>
            </div>
        @endforelse
    </div>
</div>
