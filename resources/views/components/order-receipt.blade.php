<div class="h-full">
    <div class="receipt h-full">
        <div class="receipt-list flex h-full flex-col">
            <div class="receipt-header">
                <p class="receipt-eyebrow">Order</p>
                <h2 class="receipt-title">{{ $order->title }}</h2>
                <p class="receipt-subtitle">{{ $order->formattedCreatedAt }} · {{ $order->shortFormattedCreatedAt }}</p>
            </div>

            <div class="flex flex-1 flex-col">
                @forelse ($order->items as $item)
                    <div class="receipt-item">
                        <p class="receipt-label">{{ $item->name }}</p>
                        <p class="receipt-value">x {{ $item->quantity }}</p>
                    </div>
                @empty
                    <div class="receipt-item receipt-item-empty">
                        <div class="receipt-label">No items in this order.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('restaurants.orders.show', [$order->restaurant, $order]) }}" 
                   class="inline-flex items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                    View Order
                </a>
            </div>
        </div>
    </div>
</div>
