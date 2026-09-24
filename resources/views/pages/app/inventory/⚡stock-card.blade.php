<?php

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Item $item;

    #[Url]
    public string $warehouse = '';

    public function mount(Item $item): void
    {
        $this->item = $item;
    }

    public function updatingWarehouse(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('name')->get();
    }

    #[Computed]
    public function movements()
    {
        return $this->filteredMovements()
            ->with(['warehouse', 'user', 'reference'])
            ->orderBy('id')
            ->paginate(25);
    }

    /**
     * The balance just before the first row on this page, so the running
     * balance column is right on every page, not only the first.
     */
    #[Computed]
    public function openingBalance(): int
    {
        $firstId = $this->movements->first()?->id;

        return $firstId ? (int) $this->filteredMovements()->where('id', '<', $firstId)->sum('quantity') : 0;
    }

    #[Computed]
    public function onHand(): int
    {
        return (int) $this->filteredMovements()->sum('quantity');
    }

    public function referenceUrl(StockMovement $movement): ?string
    {
        return match (true) {
            $movement->reference instanceof PurchaseOrder => route('purchasing.orders.show', $movement->reference),
            $movement->reference instanceof SalesOrder => route('sales.orders.show', $movement->reference),
            default => null,
        };
    }

    public function render()
    {
        return $this->view()->title("Stock card: {$this->item->name}");
    }

    private function filteredMovements()
    {
        return StockMovement::query()
            ->where('item_id', $this->item->id)
            ->when($this->warehouse !== '', fn ($query) => $query->where('warehouse_id', (int) $this->warehouse));
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $item->name }}</flux:heading>
            <flux:subheading class="font-mono">{{ $item->sku }} · {{ $item->unit }}</flux:subheading>
        </div>

        <flux:button variant="outline" icon="arrow-left" :href="route('inventory.stock')" wire:navigate>Back to stock</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text>On hand {{ $warehouse !== '' ? 'in this warehouse' : 'in all warehouses' }}</flux:text>
            <div class="mt-2 font-display text-3xl font-bold tabular-nums">{{ number_format($this->onHand, 0, ',', '.') }}</div>
        </flux:card>
        <flux:card>
            <flux:text>Minimum stock</flux:text>
            <div class="mt-2 font-display text-3xl font-bold tabular-nums">{{ $item->minimum_stock ? number_format($item->minimum_stock, 0, ',', '.') : 'Not set' }}</div>
        </flux:card>
        <flux:card>
            <flux:select wire:model.live="warehouse" label="Warehouse">
                <flux:select.option value="">All warehouses</flux:select.option>
                @foreach ($this->warehouses as $option)
                    <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>
    </div>

    <flux:card class="w-full">
        @if ($this->movements->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="archive-box" class="size-8 text-zinc-500" />
                <flux:text>No stock has moved for this item yet. Receiving a purchase order or posting an adjustment will show up here.</flux:text>
            </div>
        @else
            @php($balance = $this->openingBalance)
            <flux:table :paginate="$this->movements">
                <flux:table.columns>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Warehouse</flux:table.column>
                    <flux:table.column>Reference</flux:table.column>
                    <flux:table.column align="end">In</flux:table.column>
                    <flux:table.column align="end">Out</flux:table.column>
                    <flux:table.column align="end">Balance</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->movements as $movement)
                        @php($balance += $movement->quantity)
                        <flux:table.row :key="$movement->id">
                            <flux:table.cell class="whitespace-nowrap">{{ $movement->created_at->translatedFormat('j M Y H:i') }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->type->label() }}</flux:table.cell>
                            <flux:table.cell>{{ $movement->warehouse->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($url = $this->referenceUrl($movement))
                                    <flux:link :href="$url" wire:navigate class="font-mono">{{ $movement->reference->number }}</flux:link>
                                @else
                                    <span class="text-zinc-600">{{ $movement->note }}</span>
                                @endif
                                @if ($movement->user)
                                    <div class="text-xs text-zinc-500">by {{ $movement->user->name }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums text-green-700">{{ $movement->quantity > 0 ? number_format($movement->quantity, 0, ',', '.') : '' }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums text-red-700">{{ $movement->quantity < 0 ? number_format(abs($movement->quantity), 0, ',', '.') : '' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium tabular-nums">{{ number_format($balance, 0, ',', '.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
