<?php

namespace App\Livewire;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class MenuItemPicker extends Component
{
    public Restaurant $restaurant;

    public Order $order;

    public string $query = '';

    public function selectMenuItem(int $menuItemId): void
    {
        $menuItem = $this->catalogItems()->firstWhere('id', $menuItemId);

        abort_unless($menuItem, 404);

        $existing = $this->order->items()->where('menu_item_id', $menuItem->id)->first();

        if ($existing) {
            $existing->increment('quantity');
        } else {
            $this->order->items()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
            ]);
        }

        $this->query = '';
        $this->dispatch('item-added');
    }

    public function selectFirstOrAddAdHoc(): void
    {
        $filtered = $this->filteredItems();

        if ($filtered->isNotEmpty()) {
            $this->selectMenuItem($filtered->first()->id);
        } else {
            $this->addAdHocItem();
        }
    }

    public function addAdHocItem(): void
    {
        $name = trim($this->query);

        if ($name === '' || $this->filteredItems()->isNotEmpty()) {
            return;
        }

        $menuItem = $this->findOrCreateMenuItem($name);

        $this->order->items()->create([
            'menu_item_id' => $menuItem->id,
            'quantity' => 1,
        ]);

        $this->query = '';
        $this->dispatch('item-added');
    }

    public function render()
    {
        return view('livewire.menu-item-picker', [
            'filteredItems' => $this->filteredItems(),
            'frequentlyOrderedItems' => $this->frequentlyOrderedItems(),
        ]);
    }

    /** @return EloquentCollection<int, MenuItem> */
    protected function catalogItems(): EloquentCollection
    {
        return MenuItem::query()
            ->whereHas('menu', fn ($query) => $query->where('restaurant_id', $this->restaurant->id))
            ->where('is_available', true)
            ->orderBy('order_index')
            ->get();
    }

    /** @return Collection<int, MenuItem> */
    protected function filteredItems(): Collection
    {
        $term = Str::lower(trim($this->query));

        return $term === ''
            ? collect()
            : $this->catalogItems()->filter(fn (MenuItem $item) => Str::contains(Str::lower($item->name), $term))->take(8)->values();
    }

    /** @return Collection<int, MenuItem> */
    protected function frequentlyOrderedItems(): Collection
    {
        $withCounts = $this->catalogItems()
            ->map(function (MenuItem $item): MenuItem {
                $item->restaurant_order_count = $item->orderItems()
                    ->whereHas('order', fn ($query) => $query->where('restaurant_id', $this->restaurant->id))
                    ->count();

                return $item;
            });

        $popular = $withCounts
            ->filter(fn (MenuItem $item) => $item->restaurant_order_count > 0)
            ->sortByDesc('restaurant_order_count')
            ->take(6)
            ->values();

        if ($popular->isNotEmpty()) {
            return $popular;
        }

        return $this->catalogItems()
            ->sortByDesc('id')
            ->take(10)
            ->values();
    }

    protected function findOrCreateMenuItem(string $name): MenuItem
    {
        $existing = $this->catalogItems()
            ->first(fn (MenuItem $item) => Str::lower($item->name) === Str::lower($name));

        if ($existing) {
            return $existing;
        }

        $menu = MenuItem::query()
            ->whereHas('menu', fn ($query) => $query->where('restaurant_id', $this->restaurant->id))
            ->first()
            ?->menu
            ?? $this->restaurant->menus()->firstOrCreate(
                ['name' => 'Default Menu'],
                ['menu_type' => 'manual', 'is_active' => true]
            );

        return MenuItem::firstOrCreate(
            ['menu_id' => $menu->id, 'name' => $name],
            ['description' => null, 'price' => null, 'section' => null, 'order_index' => 0, 'is_available' => true]
        );
    }
}
