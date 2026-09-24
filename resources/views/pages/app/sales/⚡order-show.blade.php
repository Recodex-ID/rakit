<?php

use App\Enums\SalesOrderStatus;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidDocumentTransition;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public SalesOrder $order;

    public function mount(SalesOrder $salesOrder): void
    {
        $this->order = $salesOrder;
    }

    public function confirm(): void
    {
        $this->act(fn () => $this->order->confirm(Auth::user()), 'confirmed');
    }

    public function deliver(): void
    {
        $this->act(fn () => $this->order->deliver(Auth::user()), 'delivered and the stock was booked out');
    }

    public function cancel(): void
    {
        $this->act(fn () => $this->order->cancel(Auth::user()), 'cancelled');

        Flux::modal('cancel-order')->close();
    }

    /**
     * On-hand stock per item in the order's warehouse, so the person about to
     * deliver can see a shortage before pressing the button.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    #[Computed]
    public function available()
    {
        return StockMovement::query()
            ->where('warehouse_id', $this->order->warehouse_id)
            ->whereIn('item_id', $this->order->lines->pluck('item_id'))
            ->groupBy('item_id')
            ->selectRaw('item_id, SUM(quantity) as on_hand')
            ->pluck('on_hand', 'item_id')
            ->map(fn ($onHand) => (int) $onHand);
    }

    public function render()
    {
        $this->order->load(['lines.item', 'customer', 'warehouse', 'creator']);

        return $this->view()->title($this->order->number);
    }

    private function act(Closure $action, string $pastTense): void
    {
        Gate::authorize('sales.manage');

        try {
            $action();
        } catch (InvalidDocumentTransition|InsufficientStock $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
            $this->order->refresh();

            return;
        }

        Flux::toast(variant: 'success', text: "{$this->order->number} was {$pastTense}.");
        $this->order->refresh();
    }
}; ?>

<div class="w-full max-w-5xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:heading size="xl" class="font-mono">{{ $order->number }}</flux:heading>
                <flux:badge :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
            </div>
            <flux:subheading>Sales order for {{ $order->customer->name }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="outline" icon="arrow-left" :href="route('sales.orders')" wire:navigate>All orders</flux:button>
            <flux:button variant="outline" icon="printer" :href="route('sales.orders.print', $order)" target="_blank" rel="noopener">Print</flux:button>

            @can('sales.manage')
                @if ($order->status === SalesOrderStatus::Draft)
                    <flux:button variant="outline" icon="pencil" :href="route('sales.orders.edit', $order)" wire:navigate>Edit</flux:button>
                    <flux:button variant="primary" wire:click="confirm">Confirm order</flux:button>
                @elseif ($order->status === SalesOrderStatus::Confirmed)
                    <flux:button variant="primary" icon="truck" wire:click="deliver" wire:confirm="Deliver every line from {{ $order->warehouse->name }}? This books the stock out.">Deliver</flux:button>
                @endif

                @if (in_array($order->status, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed], true))
                    <flux:modal.trigger name="cancel-order">
                        <flux:button variant="danger">Cancel order</flux:button>
                    </flux:modal.trigger>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <flux:card class="space-y-1">
            <flux:text>Customer</flux:text>
            <div class="font-medium text-zinc-900">{{ $order->customer->name }}</div>
            <div class="text-sm text-zinc-600">{{ $order->customer->email }}</div>
            <div class="text-sm text-zinc-600">{{ $order->customer->phone }}</div>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>Ship from</flux:text>
            <div class="font-medium text-zinc-900">{{ $order->warehouse->name }}</div>
            <div class="text-sm text-zinc-600">Order date {{ $order->order_date->translatedFormat('j F Y') }}</div>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>Total</flux:text>
            <div class="font-display text-2xl font-bold tabular-nums">{{ Money::format($order->total()) }}</div>
            <div class="text-sm text-zinc-600">{{ $order->lines->count() }} {{ Str::plural('line', $order->lines->count()) }}</div>
        </flux:card>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Item</flux:table.column>
                <flux:table.column align="end">Quantity</flux:table.column>
                @if (in_array($order->status, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed], true))
                    <flux:table.column align="end">In {{ $order->warehouse->name }}</flux:table.column>
                @endif
                <flux:table.column align="end">Unit price</flux:table.column>
                <flux:table.column align="end">Subtotal</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($order->lines as $line)
                    <flux:table.row :key="$line->id">
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900">{{ $line->item->name }}</div>
                            <div class="font-mono text-xs text-zinc-500">{{ $line->item->sku }}</div>
                        </flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums">{{ number_format($line->quantity, 0, ',', '.') }} {{ $line->item->unit }}</flux:table.cell>
                        @if (in_array($order->status, [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed], true))
                            @php($onHand = $this->available[$line->item_id] ?? 0)
                            <flux:table.cell align="end" class="tabular-nums">
                                @if ($onHand < $line->quantity)
                                    <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ number_format($onHand, 0, ',', '.') }} (short)</flux:badge>
                                @else
                                    {{ number_format($onHand, 0, ',', '.') }}
                                @endif
                            </flux:table.cell>
                        @endif
                        <flux:table.cell align="end" class="tabular-nums">{{ Money::format($line->unit_price) }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium tabular-nums">{{ Money::format($line->subtotal()) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <flux:card class="space-y-3">
            <flux:heading size="lg">History</flux:heading>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-zinc-600">Created</dt><dd>{{ $order->created_at->translatedFormat('j M Y H:i') }}{{ $order->creator ? ' by '.$order->creator->name : '' }}</dd></div>
                @if ($order->confirmed_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Confirmed</dt><dd>{{ $order->confirmed_at->translatedFormat('j M Y H:i') }}</dd></div>
                @endif
                @if ($order->delivered_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Delivered</dt><dd>{{ $order->delivered_at->translatedFormat('j M Y H:i') }}</dd></div>
                @endif
                @if ($order->cancelled_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Cancelled</dt><dd>{{ $order->cancelled_at->translatedFormat('j M Y H:i') }}</dd></div>
                @endif
            </dl>
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">Notes</flux:heading>
            <flux:text class="whitespace-pre-line">{{ $order->notes ?: 'No notes.' }}</flux:text>
        </flux:card>
    </div>

    <flux:modal name="cancel-order" class="max-w-md" focusable>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Cancel {{ $order->number }}?</flux:heading>
                <flux:subheading>The order stays in the list as cancelled. No stock leaves the warehouse.</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Keep order</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">Cancel order</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
