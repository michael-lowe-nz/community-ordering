<div class="receipt">
    <div class="receipt-list">
        @foreach ($orderItems as $item)
            <div class="receipt-item">
                <div class="receipt-label">{{ $item->name}}</div>
                <div class="receipt-value">x {{ $item->quantity}}</div>
            </div>
        @endforeach
    </div> 
</div>