<?php

use App\Enums\PurchaseOrderStatus;
use App\Exceptions\InvalidDocumentTransition;
use App\Models\PurchaseOrder;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public PurchaseOrder $order;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->order = $purchaseOrder;
    }

    public function submit(): void
    {
        $this->act('purchasing.manage', fn () => $this->order->submit(Auth::user()), 'submitted for approval');
    }

    public function approve(): void
    {
        $this->act('purchasing.approve', fn () => $this->order->approve(Auth::user()), 'approved');
    }

    public function receive(): void
    {
        $this->act('purchasing.manage', fn () => $this->order->receive(Auth::user()), 'received and the stock was booked');
    }

    public function cancel(): void
    {
        $this->act('purchasing.manage', fn () => $this->order->cancel(Auth::user()), 'cancelled');

        Flux::modal('cancel-order')->close();
    }

    public function render()
    {
        $this->order->load(['lines.item', 'supplier', 'warehouse', 'creator', 'approver']);

        return $this->view()->title($this->order->number);
    }

    private function act(string $permission, Closure $action, string $pastTense): void
    {
        Gate::authorize($permission);

        try {
            $action();
        } catch (InvalidDocumentTransition $exception) {
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
            <flux:subheading>Purchase order from {{ $order->supplier->name }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="outline" icon="arrow-left" :href="route('purchasing.orders')" wire:navigate>All orders</flux:button>
            <flux:button variant="outline" icon="printer" :href="route('purchasing.orders.print', $order)" target="_blank" rel="noopener">Print</flux:button>

            @if ($order->status === PurchaseOrderStatus::Draft)
                @can('purchasing.manage')
                    <flux:button variant="outline" icon="pencil" :href="route('purchasing.orders.edit', $order)" wire:navigate>Edit</flux:button>
                    <flux:button variant="primary" wire:click="submit">Submit for approval</flux:button>
                @endcan
            @elseif ($order->status === PurchaseOrderStatus::Submitted)
                @can('purchasing.approve')
                    <flux:button variant="primary" wire:click="approve">Approve</flux:button>
                @else
                    <flux:text class="text-sm">Waiting for someone with approval rights.</flux:text>
                @endcan
            @elseif ($order->status === PurchaseOrderStatus::Approved)
                @can('purchasing.manage')
                    <flux:button variant="primary" icon="inbox-arrow-down" wire:click="receive" wire:confirm="Receive every line into {{ $order->warehouse->name }}? This books the stock.">Receive goods</flux:button>
                @endcan
            @endif

            @if (in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Approved], true))
                @can('purchasing.manage')
                    <flux:modal.trigger name="cancel-order">
                        <flux:button variant="danger">Cancel order</flux:button>
                    </flux:modal.trigger>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <flux:card class="space-y-1">
            <flux:text>Supplier</flux:text>
            <div class="font-medium text-zinc-900">{{ $order->supplier->name }}</div>
            <div class="text-sm text-zinc-600">{{ $order->supplier->email }}</div>
            <div class="text-sm text-zinc-600">{{ $order->supplier->phone }}</div>
        </flux:card>
        <flux:card class="space-y-1">
            <flux:text>Receive into</flux:text>
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
                @if ($order->submitted_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Submitted</dt><dd>{{ $order->submitted_at->translatedFormat('j M Y H:i') }}</dd></div>
                @endif
                @if ($order->approved_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Approved</dt><dd>{{ $order->approved_at->translatedFormat('j M Y H:i') }}{{ $order->approver ? ' by '.$order->approver->name : '' }}</dd></div>
                @endif
                @if ($order->received_at)
                    <div class="flex justify-between gap-4"><dt class="text-zinc-600">Received</dt><dd>{{ $order->received_at->translatedFormat('j M Y H:i') }}</dd></div>
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
                <flux:subheading>The order stays in the list as cancelled. Nothing is received.</flux:subheading>
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
