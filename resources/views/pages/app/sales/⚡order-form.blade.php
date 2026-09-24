<?php

use App\Livewire\Concerns\ManagesOrderLines;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
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

    public ?SalesOrder $order = null;

    public int|string $customerId = '';

    public int|string $warehouseId = '';

    public string $orderDate = '';

    public string $notes = '';

    public function mount(?SalesOrder $salesOrder = null): void
    {
        if ($salesOrder?->exists) {
            if (! $salesOrder->isEditable()) {
                Flux::toast(variant: 'danger', text: "{$salesOrder->number} is no longer a draft, so it cannot be edited.");

                $this->redirectRoute('sales.orders.show', $salesOrder, navigate: true);

                return;
            }

            $this->order = $salesOrder->load('lines');
            $this->customerId = $salesOrder->customer_id;
            $this->warehouseId = $salesOrder->warehouse_id;
            $this->orderDate = $salesOrder->order_date->toDateString();
            $this->notes = $salesOrder->notes ?? '';
            $this->lines = $salesOrder->lines->map(fn ($line) => [
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
            'customerId' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'warehouseId' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'orderDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...$this->lineRules(),
        ], $this->lineMessages(), [
            'customerId' => 'customer',
            'warehouseId' => 'warehouse',
            'orderDate' => 'order date',
        ]);

        if ($this->order && ! $this->order->fresh()->isEditable()) {
            Flux::toast(variant: 'danger', text: "{$this->order->number} changed status while you were editing, so it was not saved.");

            return;
        }

        $order = DB::transaction(function (): SalesOrder {
            $order = $this->order ?? new SalesOrder(['created_by' => Auth::id()]);

            $order->fill([
                'customer_id' => (int) $this->customerId,
                'warehouse_id' => (int) $this->warehouseId,
                'order_date' => $this->orderDate,
                'notes' => $this->notes ?: null,
            ])->save();

            $order->lines()->delete();
            $order->lines()->createMany($this->normalizedLines());

            return $order;
        });

        Flux::toast(variant: 'success', text: "{$order->number} was saved as a draft.");

        $this->redirectRoute('sales.orders.show', $order, navigate: true);
    }

    protected function defaultPriceFor(Item $item): int
    {
        return $item->sale_price;
    }

    #[Computed]
    public function customers()
    {
        return Customer::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->active()->orderBy('name')->get();
    }

    public function render()
    {
        return $this->view()->title($this->order ? "Edit {$this->order->number}" : 'New sales order');
    }
}; ?>

<div class="w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $order ? "Edit {$order->number}" : 'New sales order' }}</flux:heading>
            <flux:subheading>Saved as a draft. Confirm it from the order page once the customer agrees.</flux:subheading>
        </div>
        <flux:button variant="outline" icon="arrow-left" :href="$order ? route('sales.orders.show', $order) : route('sales.orders')" wire:navigate>Back</flux:button>
    </div>

    @if ($this->customers->isEmpty() || $this->warehouses->isEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Master data missing</flux:callout.heading>
            <flux:callout.text>A sales order needs at least one active customer and one active warehouse. Add them under Master data first.</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:card class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <flux:select wire:model="customerId" label="Customer" placeholder="Choose a customer">
                @foreach ($this->customers as $customer)
                    <flux:select.option value="{{ $customer->id }}">{{ $customer->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="warehouseId" label="Ship from" placeholder="Choose a warehouse">
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
            <flux:button variant="filled" :href="$order ? route('sales.orders.show', $order) : route('sales.orders')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Save draft</flux:button>
        </div>
    </form>
</div>
