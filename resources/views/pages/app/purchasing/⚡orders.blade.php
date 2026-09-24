<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Support\Money;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Purchase orders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function orders()
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->addSelect(['total_amount' => PurchaseOrderLine::query()
                ->selectRaw('coalesce(sum(quantity * unit_price), 0)')
                ->whereColumn('purchase_order_id', 'purchase_orders.id')])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('number', 'like', "%{$this->search}%")
                ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->latest('order_date')
            ->latest('id')
            ->paginate(15);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Purchase orders</flux:heading>
            <flux:subheading>Draft, submit for approval, then receive the goods into a warehouse.</flux:subheading>
        </div>

        @can('purchasing.manage')
            <flux:button variant="primary" icon="plus" :href="route('purchasing.orders.create')" wire:navigate>New purchase order</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search number or supplier" icon="magnifying-glass" class="max-w-sm" aria-label="Search purchase orders" />

        <flux:select wire:model.live="status" class="max-w-48" aria-label="Filter by status">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach (PurchaseOrderStatus::cases() as $option)
                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="w-full">
        @if ($this->orders->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="truck" class="size-8 text-zinc-500" />
                <flux:text>
                    {{ filled($search) || $status !== '' ? 'No purchase orders match these filters.' : 'No purchase orders yet. Create one to order stock from a supplier.' }}
                </flux:text>
            </div>
        @else
            <flux:table :paginate="$this->orders">
                <flux:table.columns>
                    <flux:table.column>Number</flux:table.column>
                    <flux:table.column>Supplier</flux:table.column>
                    <flux:table.column>Warehouse</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column align="end">Total</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell>
                                <flux:link :href="route('purchasing.orders.show', $order)" wire:navigate class="font-mono">{{ $order->number }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->supplier->name }}</flux:table.cell>
                            <flux:table.cell>{{ $order->warehouse->name }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $order->order_date->translatedFormat('j M Y') }}</flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap tabular-nums">{{ Money::format((int) $order->total_amount) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
