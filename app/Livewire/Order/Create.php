<?php

namespace App\Livewire\Order;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Livewire\Component;

class Create extends Component
{
    public Restaurant $restaurant;

    public Order $order;

    public string $orderTitle = '';

    public bool $editingTitle = false;

    public ?int $editingItemId = null;

    public string $itemDraftName = '';

    public int $itemDraftQuantity = 1;

    #[Rule('required|string|min:2|max:255')]
    public string $query = '';

    #[Rule('required|integer|min:1|max:99')]
    public int $quantity = 1;

    public array $suggestions = [];

    public function mount(): void
    {
        $this->orderTitle = trim((string) ($this->order->content ?? '')) ?: $this->order->title;
    }

    public function updatedQuery(string $value): void
    {
        $term = trim($value);

        if ($term === '') {
            $this->suggestions = [];

            return;
        }

        $this->suggestions = $this->searchMenuItems($term);
    }

    public function chooseSuggestion(string $name): void
    {
        $this->query = trim($name);
        $this->suggestions = [];
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min(99, $this->quantity + 1);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function addItem(): void
    {
        $this->validate();

        $name = trim($this->query);

        $menuItem = $this->findExistingMenuItem($name) ?? $this->createMenuItemFromName($name);

        $this->order->items()->create([
            'name' => $menuItem->name,
            'quantity' => $this->quantity,
            'price' => $menuItem->price,
        ]);

        $this->query = '';
        $this->quantity = 1;
        $this->suggestions = [];
    }

    public function startTitleEdit(): void
    {
        $this->editingTitle = true;
        $this->orderTitle = trim((string) ($this->order->content ?? '')) ?: $this->order->title;
    }

    public function saveTitle(): void
    {
        $this->orderTitle = trim($this->orderTitle) ?: $this->order->title;
        $this->order->content = $this->orderTitle;
        $this->order->save();
        $this->editingTitle = false;
    }

    public function beginEditItem(int $itemId): void
    {
        $item = $this->order->items()->findOrFail($itemId);

        $this->editingItemId = $itemId;
        $this->itemDraftName = $item->name;
        $this->itemDraftQuantity = max(1, (int) $item->quantity);
    }

    public function saveEditedItem(int $itemId): void
    {
        $name = trim($this->itemDraftName);

        if ($name === '') {
            $this->addError('itemDraftName', 'Item name is required.');

            return;
        }

        $this->order->items()
            ->whereKey($itemId)
            ->update([
                'name' => $name,
                'quantity' => max(1, min(99, (int) $this->itemDraftQuantity)),
            ]);

        $this->editingItemId = null;
        $this->itemDraftName = '';
        $this->itemDraftQuantity = 1;
    }

    public function incrementItemQuantity(int $itemId): void
    {
        $this->order->items()->whereKey($itemId)->increment('quantity');
    }

    public function decrementItemQuantity(int $itemId): void
    {
        $item = $this->order->items()->find($itemId);

        if (! $item) {
            return;
        }

        if ($item->quantity <= 1) {
            $item->delete();

            return;
        }

        $item->decrement('quantity');
    }

    public function removeItem(int $itemId): void
    {
        $this->order->items()->whereKey($itemId)->delete();
        $this->editingItemId = ($this->editingItemId === $itemId) ? null : $this->editingItemId;
    }

    public function render()
    {
        return view('livewire.order.create');
    }

    protected function searchMenuItems(string $term): array
    {
        $menuIds = $this->restaurant->menus()->pluck('id');

        if ($menuIds->isEmpty()) {
            return [];
        }

        return MenuItem::whereIn('menu_id', $menuIds)
            ->where('is_available', true)
            ->get()
            ->filter(fn (MenuItem $item) => Str::contains(Str::lower($item->name), Str::lower($term)))
            ->take(8)
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    protected function findExistingMenuItem(string $name): ?MenuItem
    {
        $menuIds = $this->restaurant->menus()->pluck('id');

        if ($menuIds->isEmpty()) {
            return null;
        }

        return MenuItem::whereIn('menu_id', $menuIds)
            ->where('is_available', true)
            ->get()
            ->first(fn (MenuItem $item) => Str::lower($item->name) === Str::lower($name));
    }

    protected function createMenuItemFromName(string $name): MenuItem
    {
        $menu = $this->restaurant->menus()->first();

        if (! $menu) {
            $menu = $this->restaurant->menus()->create([
                'name' => 'Default Menu',
                'menu_type' => 'manual',
                'is_active' => true,
            ]);
        }

        return MenuItem::firstOrCreate(
            [
                'menu_id' => $menu->id,
                'name' => $name,
            ],
            [
                'description' => null,
                'price' => null,
                'section' => null,
                'order_index' => 0,
                'is_available' => true,
            ]
        );
    }
}
