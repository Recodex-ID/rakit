<?php

use App\Exceptions\InsufficientStock;
use App\Models\Item;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Stock')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $warehouse = '';

    #[Url]
    public bool $lowOnly = false;

    public ?int $adjustItemId = null;

    public ?int $adjustWarehouseId = null;

    public int|string $adjustQuantity = '';

    public string $adjustReason = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'warehouse', 'lowOnly'], true)) {
            $this->resetPage();
        }
    }

    public function openAdjustment(?int $itemId = null): void
    {
        Gate::authorize('inventory.manage');

        $this->reset('adjustQuantity', 'adjustReason');
        $this->resetValidation();
        $this->adjustItemId = $itemId;
        $this->adjustWarehouseId = $this->warehouse !== '' ? (int) $this->warehouse : $this->warehouses->first()?->id;

        Flux::modal('adjust-stock')->show();
    }

    public function saveAdjustment(): void
    {
        Gate::authorize('inventory.manage');

        $this->validate([
            'adjustItemId' => ['required', 'integer', 'exists:items,id'],
            'adjustWarehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'adjustQuantity' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'adjustReason' => ['required', 'string', 'max:255'],
        ], [
            'adjustQuantity.not_in' => 'Enter how many units to add (positive) or remove (negative).',
        ], [
            'adjustItemId' => 'item',
            'adjustWarehouseId' => 'warehouse',
            'adjustQuantity' => 'quantity',
            'adjustReason' => 'reason',
        ]);

        $item = Item::query()->findOrFail($this->adjustItemId);
        $warehouse = Warehouse::query()->findOrFail($this->adjustWarehouseId);

        try {
            $item->adjustStock($warehouse, (int) $this->adjustQuantity, $this->adjustReason, Auth::user());
        } catch (InsufficientStock $exception) {
            $this->addError('adjustQuantity', $exception->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: "Stock of {$item->name} in {$warehouse->name} was adjusted.");
        Flux::modal('adjust-stock')->close();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function adjustableItems()
    {
        return Item::query()->active()->orderBy('name')->get(['id', 'sku', 'name']);
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->withStockOnHand($this->warehouse !== '' ? (int) $this->warehouse : null)
            ->withSum('stockMovements as total_on_hand', 'quantity')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('sku', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->when($this->lowOnly, fn ($query) => $query->belowMinimum())
            ->active()
            ->orderBy('name')
            ->paginate(20);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Stock</flux:heading>
            <flux:subheading>On-hand quantities, worked out from every receipt, delivery and adjustment.</flux:subheading>
        </div>

        @can('inventory.manage')
            <flux:button variant="primary" icon="adjustments-horizontal" wire:click="openAdjustment">Adjust stock</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search SKU or name" icon="magnifying-glass" class="max-w-sm" aria-label="Search items" />

        <flux:select wire:model.live="warehouse" class="max-w-56" aria-label="Warehouse">
            <flux:select.option value="">All warehouses</flux:select.option>
            @foreach ($this->warehouses as $option)
                <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:checkbox wire:model.live="lowOnly" label="Below minimum only" />
    </div>

    <flux:card class="w-full">
        @if ($this->items->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="archive-box" class="size-8 text-zinc-500" />
                <flux:text>
                    @if ($lowOnly)
                        Nothing is below its minimum stock.
                    @elseif (filled($search))
                        No items match this search.
                    @else
                        No active items yet. Add items under Master data first.
                    @endif
                </flux:text>
            </div>
        @else
            <flux:table :paginate="$this->items">
                <flux:table.columns>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column>Unit</flux:table.column>
                    <flux:table.column align="end">{{ $warehouse ? 'In this warehouse' : 'On hand' }}</flux:table.column>
                    <flux:table.column align="end">Minimum (all warehouses)</flux:table.column>
                    <flux:table.column>Level</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->items as $item)
                        @php($isLow = $item->isBelowMinimum((int) $item->total_on_hand))
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900">{{ $item->name }}</div>
                                <div class="font-mono text-xs text-zinc-500">{{ $item->sku }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->unit }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium tabular-nums">{{ number_format((int) $item->on_hand, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $item->minimum_stock ? number_format($item->minimum_stock, 0, ',', '.') : '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($isLow)
                                    <flux:badge size="sm" color="red" icon="exclamation-triangle">Below minimum</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">OK</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="py-0">
                                <div class="flex items-center gap-1">
                                    <flux:button size="sm" variant="outline" :href="route('inventory.stock-card', $item)" wire:navigate>Stock card</flux:button>
                                    @can('inventory.manage')
                                        <flux:button size="sm" variant="outline" icon="adjustments-horizontal" wire:click="openAdjustment({{ $item->id }})" aria-label="Adjust {{ $item->name }}" />
                                    @endcan
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="adjust-stock" class="max-w-md" focusable>
        <form wire:submit="saveAdjustment" class="space-y-6">
            <div>
                <flux:heading size="lg">Adjust stock</flux:heading>
                <flux:subheading>For stock opname corrections. Receipts and deliveries are booked by their orders.</flux:subheading>
            </div>

            <flux:select wire:model="adjustItemId" label="Item" placeholder="Choose an item">
                @foreach ($this->adjustableItems as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->sku }} · {{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="adjustWarehouseId" label="Warehouse" placeholder="Choose a warehouse">
                @foreach ($this->warehouses as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="number" step="1" label="Quantity" wire:model="adjustQuantity" description="Positive adds stock, negative removes it." />
            <flux:input label="Reason" wire:model="adjustReason" placeholder="e.g. Count on 30 September found 3 extra" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Post adjustment</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
