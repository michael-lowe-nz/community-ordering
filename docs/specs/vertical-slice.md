# Shared components + vertical slice — spec

Scope for this pass, matching the current simplification: no verdict UI, no confidence indicators, no reminder strip, no guest/device order creation (creating an order requires login; viewing one doesn't). The quick-create restaurant-picker from Home is left out of this pass too — the Order create component below doesn't care how it received a restaurant, so that entry point is a thin addition later, not a blocker now.

Two prompts, run in order — components first, then the pages that assemble them.

---

## Step 4 — shared components

| Component | Type | Purpose |
|---|---|---|
| `RestaurantCard` | Blade component | Restaurant name/cuisine/suburb + order count, for lists (search results, Home). |
| `OrderSummaryCard` | Blade component | One row in a "recent orders" / "your orders" list: date, party size, item preview. No verdict. |
| `GuidanceSummary` | Blade component | Renders an `OrderGuidance` DTO as "≈ 3 mains, 2 sides, 1 rice", or a "not enough data yet" state when the breakdown is empty. |
| `OrderItemList` | Blade component | Renders an order's items (merged from `menuItemOrder.menuItem` and `orderItems`). Takes an `editable` bool — editable shows quantity controls and a remove action, read-only just lists them. Used in both Order create and Order view. |
| `MenuItemPicker` | Livewire component | Search the restaurant's catalog items (via `restaurant->menus->menuItems`), quick-add chips for frequently-ordered items, and an inline "add '[query]' as a new item" path that creates an ad-hoc `order_items` row (not a new `MenuItem`) when there's no catalog match. Emits an event when an item is added so the parent can refresh totals. |

Notes:
- The party-size control is **not** a separate component — it's simple enough to be inline state (`wire:model.live`) on whichever parent component uses it (Restaurant view, Order create). A dedicated Livewire component for a single number input adds nesting overhead for no real benefit here.
- "Frequently ordered here" = the N most common catalog items in `menu_item_order` for this restaurant, by simple count — no weighting, no recency logic.

### Copilot prompt — components

```
Use Boost to confirm the Restaurant, Order, MenuItemOrder, OrderItem, and MenuItem
models/relationships match the current schema before building these.

Create the following Blade components (resources/views/components/):

1. restaurant-card.blade.php — accepts a Restaurant model. Shows name, cuisine_type,
   suburb, and its total order count (add a withCount('orders') friendly accessor
   or use $restaurant->orders_count if already eager loaded).

2. order-summary-card.blade.php — accepts an Order model (with items eager loaded).
   Shows ordered_at (fallback to created_at if null), participant_count, and a
   truncated preview of item names ("Mapo tofu, Fried rice +2 more"). No verdict
   display.

3. guidance-summary.blade.php — accepts an OrderGuidance DTO (app/Services/OrderGuidance.php).
   If categoryBreakdown is empty, render "Not enough data yet for this restaurant."
   Otherwise render each category and count (e.g. "3 Mains · 2 Sides · 1 Rice") plus
   totalDishes.

4. order-item-list.blade.php — accepts a merged collection of items (each with at
   least name and quantity — build this merge from $order->menuItemOrder (with
   menuItem) and $order->orderItems in the parent, don't do it inside the component)
   and an $editable boolean prop. When editable, render a quantity stepper and a
   remove (x) control per row wired to emit events; when not editable, render a
   plain list.

Create app/Livewire/MenuItemPicker.php (+ view):
- Public property partySize is NOT part of this component — it only needs $restaurant
  and $order (or order-in-progress items) passed in.
- A search input (wire:model.live.debounce) filtering
  $restaurant->menus->flatMap->menuItems by name, case-insensitive.
- A "frequently ordered here" row: top 6 menu items by count in menu_item_order
  for this restaurant, shown as quick-add chips regardless of the search state.
- If the search text has no matching catalog item, show an inline
  "Add '[query]' as a new item" action that creates an OrderItem row (ad-hoc,
  no menu_item_id) on the current order with quantity 1.
- Selecting a catalog item creates or increments a MenuItemOrder row for the
  current order.
- Dispatch a Livewire event (e.g. 'item-added') after either action so the
  parent component can refresh its item list and totals.

Write Pest + Livewire component tests for MenuItemPicker: searching filters
correctly, selecting a catalog item creates/increments MenuItemOrder, typing an
unmatched query and adding it creates an OrderItem, and the item-added event
fires in both cases.
```

---

## Step 5 — vertical slice pages

Three Livewire full-page components wiring the above together.

**`RestaurantView`** (route: `GET /restaurants/{restaurant}`)
- `partySize` public property, default 4, `wire:model.live`.
- Computed property resolving `OrderGuidanceCalculator` from the container and calling `->calculate($restaurant, $this->partySize)`, rendered via `<x-guidance-summary>`.
- Recent orders: `$restaurant->orders()->latest('ordered_at')->with(['menuItemOrder.menuItem', 'orderItems'])->paginate(10)`, rendered as a list of `<x-order-summary-card>`.
- A "Filter: party size" select (exact match or a small range — your call) that filters the same query. Keep it a separate control from the slider, not reactive to it.
- "Your orders" tab: same query scoped to `auth()->id()`, only visible when logged in.
- "+ Log a new order here" — link to Order create with `restaurant_id` pre-filled. Auth-gated: if not logged in, link to login instead.

**`OrderCreate`** (route: `GET/POST /restaurants/{restaurant}/orders/create`, `auth` middleware)
- `participant_count` input, default 4.
- `<livewire:menu-item-picker>` embedded, listening for `item-added` to refresh the running item list/total.
- `<x-order-item-list :editable="true">` showing current items.
- Collapsed notes field (single-line toggle, per the wireframe — a simple `$showNotes` boolean plus a textarea, no separate component needed).
- Save action: create the `Order` (`restaurant_id`, `user_id = auth()->id()`, `participant_count`, `ordered_at = now()`), the component doesn't need to touch `verdict` at all — leave it at its column default. Redirect to `OrderShow` for the new order.

**`OrderShow`** (route: `GET /orders/{order}`, no auth required)
- Full-width, single-scroll layout — no sticky panel, matching the read-only wireframe.
- Renders restaurant name, `ordered_at`, `participant_count`, `<x-order-item-list :editable="false">`, and notes if present. No verdict badge.
- "Use as starting point" button: if logged in, duplicates the order's items into a new `OrderCreate` session pre-filled; if logged out, links to login first (guest duplication is deferred along with device-linked orders).

### Copilot prompt — pages

```
Use Boost to confirm current route definitions and the OrderGuidanceCalculator
binding (from AppServiceProvider) before building these. Assume the components
from the previous step already exist and use them rather than re-implementing
their markup inline.

Create three Livewire full-page components:

1. app/Livewire/RestaurantView.php, route GET /restaurants/{restaurant}
   - public int $partySize = 4, wire:model.live from a slider/stepper in the view
   - a computed property that resolves OrderGuidanceCalculator from the container
     and calls calculate($this->restaurant, $this->partySize), passed to
     <x-guidance-summary>
   - recent orders: $restaurant->orders()->latest('ordered_at')
     ->with(['menuItemOrder.menuItem', 'orderItems'])->paginate(10), rendered
     with <x-order-summary-card> in a loop
   - a party-size filter select, separate from the $partySize slider above,
     filtering the same orders query by participant_count — do not make the
     slider filter this list
   - a "Your orders" tab scoped to auth()->id(), shown only when authenticated
   - a "+ Log a new order here" link to the OrderCreate route for this
     restaurant when authenticated, otherwise to the login route

2. app/Livewire/OrderCreate.php, route GET/POST /restaurants/{restaurant}/orders/create,
   behind the auth middleware
   - int $participantCount = 4 input
   - embed <livewire:menu-item-picker :restaurant="$restaurant" :order="$order">
     (create a draft Order model on mount, or pass the restaurant and manage
     items against a not-yet-persisted collection — pick whichever fits Livewire's
     model better and note the choice)
   - <x-order-item-list :editable="true"> reflecting current items, refreshed
     when MenuItemPicker dispatches 'item-added'
   - a collapsed notes field: boolean $showNotes toggled by a "+ Add a note" link,
     revealing a textarea bound to the order's notes when true
   - on save: create the Order with restaurant_id, user_id = auth()->id(),
     participant_count, ordered_at = now(). Do not set verdict. Redirect to
     OrderShow for the new order.

3. app/Livewire/OrderShow.php, route GET /orders/{order}, no auth middleware
   - full-width single-scroll layout, no sticky/fixed panel
   - renders restaurant name, ordered_at, participant_count,
     <x-order-item-list :editable="false">, and notes if present — no verdict
     display anywhere on this page
   - a "Use as starting point" button: if auth()->check(), create a new draft
     Order pre-filled from this one's items and redirect into OrderCreate;
     if not authenticated, redirect to the login route first

Write Pest + Livewire tests for each: RestaurantView renders guidance and orders
list, the party-size filter narrows the list independent of the slider value,
OrderCreate requires auth and successfully creates an order with items from
both MenuItemOrder and OrderItem sources, and OrderShow renders without auth
and never displays a verdict.
```