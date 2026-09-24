<?php

use App\Livewire\Concerns\ManagesOrderLines;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use ManagesOrderLines;

    public ?PurchaseOrder $order = null;

    public int|string $supplierId = '';

    public int|string $warehouseId = '';

    public string $orderDate = '';

    public string $notes = '';

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        if ($purchaseOrder?->exists) {
            if (! $purchaseOrder->isEditable()) {
                Flux::toast(variant: 'danger', text: "{$purchaseOrder->number} is no longer a draft, so it cannot be edited.");

                $this->redirectRoute('purchasing.orders.show', $purchaseOrder, navigate: true);

                return;
            }

            $this->order = $purchaseOrder->load('lines');
            $this->supplierId = $purchaseOrder->supplier_id;
            $this->warehouseId = $purchaseOrder->warehouse_id;
            $this->orderDate = $purchaseOrder->order_date->toDateString();
            $this->notes = $purchaseOrder->notes ?? '';
            $this->lines = $purchaseOrder->lines->map(fn ($line) => [
                'item_id' => $line->item_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
            ])->all();

            return;
        }

        $this->orderDate = now()->toDateString();
        $this->warehouseId = $this->warehouses->count() === 1 ? $this->warehouses->first()->id : '';
        $this->addLine();
    }

    public function save(): void
    {
        $this->validate([
            'supplierId' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'warehouseId' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'orderDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...$this->lineRules(),
        ], $this->lineMessages(), [
            'supplierId' => 'supplier',
            'warehouseId' => 'warehouse',
            'orderDate' => 'order date',
        ]);

        if ($this->order && ! $this->order->fresh()->isEditable()) {
            Flux::toast(variant: 'danger', text: "{$this->order->number} changed status while you were editing, so it was not saved.");

            return;
        }

        $order = DB::transaction(function (): PurchaseOrder {
            $order = $this->order ?? new PurchaseOrder(['created_by' => Auth::id()]);

            $order->fill([
                'supplier_id' => (int) $this->supplierId,
                'warehouse_id' => (int) $this->warehouseId,
                'order_date' => $this->orderDate,
                'notes' => $this->notes ?: null,
            ])->save();

            $order->lines()->delete();
            $order->lines()->createMany($this->normalizedLines());

            return $order;
        });

        Flux::toast(variant: 'success', text: "{$order->number} was saved as a draft.");

        $this->redirectRoute('purchasing.orders.show', $order, navigate: true);
    }

    protected function defaultPriceFor(Item $item): int
    {
        return $item->purchase_price;
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->active()->orderBy('name')->get();
    }

    public function render()
    {
        return $this->view()->title($this->order ? "Edit {$this->order->number}" : 'New purchase order');
    }
}; ?>

<div class="w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $order ? "Edit {$order->number}" : 'New purchase order' }}</flux:heading>
            <flux:subheading>Saved as a draft. Submit it for approval from the order page.</flux:subheading>
        </div>
        <flux:button variant="outline" icon="arrow-left" :href="$order ? route('purchasing.orders.show', $order) : route('purchasing.orders')" wire:navigate>Back</flux:button>
    </div>

    @if ($this->suppliers->isEmpty() || $this->warehouses->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Master data missing</flux:callout.heading>
            <flux:callout.text>A purchase order needs at least one active supplier and one active warehouse. Add them under Master data first.</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:card class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <flux:select wire:model="supplierId" label="Supplier" placeholder="Choose a supplier">
                @foreach ($this->suppliers as $supplier)
                    <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="warehouseId" label="Receive into" placeholder="Choose a warehouse">
                @foreach ($this->warehouses as $warehouse)
                    <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model="orderDate" label="Order date" />

            <div class="md:col-span-3">
                <flux:textarea wire:model="notes" label="Notes" rows="2" />
            </div>
        </flux:card>

        @include('partials.order-lines')

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="$order ? route('purchasing.orders.show', $order) : route('purchasing.orders')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save draft</flux:button>
        </div>
    </form>
</div>
