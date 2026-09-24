<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    #[Computed]
    public function lowStockItems()
    {
        return Item::query()->active()->belowMinimum()->withStockOnHand()->orderBy('name')->limit(5)->get();
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return Item::query()->active()->belowMinimum()->count();
    }

    #[Computed]
    public function purchaseOrdersAwaitingApproval(): int
    {
        return PurchaseOrder::query()->where('status', PurchaseOrderStatus::Submitted)->count();
    }

    #[Computed]
    public function purchaseOrdersToReceive(): int
    {
        return PurchaseOrder::query()->where('status', PurchaseOrderStatus::Approved)->count();
    }

    #[Computed]
    public function salesOrdersToDeliver(): int
    {
        return SalesOrder::query()->where('status', SalesOrderStatus::Confirmed)->count();
    }

    /**
     * Value of goods received this month, at purchase order prices.
     */
    #[Computed]
    public function purchasesThisMonth(): int
    {
        return (int) PurchaseOrderLine::query()
            ->whereHas('purchaseOrder', fn ($query) => $query
                ->where('status', PurchaseOrderStatus::Received)
                ->where('received_at', '>=', now()->startOfMonth()))
            ->sum(DB::raw('quantity * unit_price'));
    }

    /**
     * Value of goods delivered this month, at sales order prices.
     */
    #[Computed]
    public function salesThisMonth(): int
    {
        return (int) SalesOrderLine::query()
            ->whereHas('salesOrder', fn ($query) => $query
                ->where('status', SalesOrderStatus::Delivered)
                ->where('delivered_at', '>=', now()->startOfMonth()))
            ->sum(DB::raw('quantity * unit_price'));
    }

    /**
     * Delivered sales value per day for the last 14 days, oldest first.
     *
     * @return list<array{label: string, total: int}>
     */
    #[Computed]
    public function salesPerDay(): array
    {
        $totals = SalesOrder::query()
            ->with('lines')
            ->where('status', SalesOrderStatus::Delivered)
            ->where('delivered_at', '>=', now()->subDays(13)->startOfDay())
            ->get()
            ->groupBy(fn (SalesOrder $order) => $order->delivered_at->toDateString())
            ->map(fn ($orders) => $orders->sum(fn (SalesOrder $order) => $order->total()));

        return collect(range(13, 0))
            ->map(fn (int $daysAgo) => now()->subDays($daysAgo))
            ->map(fn ($day) => [
                'label' => $day->translatedFormat('j M'),
                'total' => (int) ($totals[$day->toDateString()] ?? 0),
            ])
            ->all();
    }
}; ?>

<div class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Welcome back, {{ Auth::user()->name }}</flux:heading>
        <flux:subheading>{{ now()->translatedFormat('l, j F Y') }}</flux:subheading>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @can('sales.view')
            <flux:card>
                <flux:text>Sales this month</flux:text>
                <div class="mt-1 font-display text-2xl font-bold tabular-nums">{{ Money::format($this->salesThisMonth) }}</div>
                <flux:text class="mt-1 text-sm">Delivered orders since {{ now()->startOfMonth()->translatedFormat('j M') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text>Sales orders to deliver</flux:text>
                <div class="mt-1 font-display text-2xl font-bold tabular-nums">{{ $this->salesOrdersToDeliver }}</div>
                <flux:link class="mt-1 text-sm" :href="route('sales.orders', ['status' => 'confirmed'])" wire:navigate>See confirmed orders</flux:link>
            </flux:card>
        @endcan

        @can('purchasing.view')
            <flux:card>
                <flux:text>Purchases this month</flux:text>
                <div class="mt-1 font-display text-2xl font-bold tabular-nums">{{ Money::format($this->purchasesThisMonth) }}</div>
                <flux:text class="mt-1 text-sm">Goods received since {{ now()->startOfMonth()->translatedFormat('j M') }}</flux:text>
            </flux:card>
            <flux:card>
                <flux:text>Purchase orders waiting</flux:text>
                <div class="mt-1 font-display text-2xl font-bold tabular-nums">{{ $this->purchaseOrdersAwaitingApproval }} <span class="text-base font-medium text-zinc-600">to approve</span></div>
                <div class="mt-1 text-sm text-zinc-600 tabular-nums">{{ $this->purchaseOrdersToReceive }} approved, not yet received</div>
            </flux:card>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @can('sales.view')
            <flux:card class="lg:col-span-2">
                <flux:heading size="lg">Delivered sales, last 14 days</flux:heading>
                <flux:subheading>Value of sales orders delivered each day</flux:subheading>

                @if (collect($this->salesPerDay)->sum('total') === 0)
                    <flux:text class="mt-8">No deliveries in the last 14 days yet. The chart fills in as sales orders are delivered.</flux:text>
                @else
                    <div
                        wire:ignore
                        class="mt-6 h-56"
                        role="img"
                        aria-label="Bar chart of delivered sales per day for the last 14 days"
                        x-data="{
                            async init() {
                                const Chart = await window.loadChart();
                                const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
                                new Chart(this.$refs.canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: @js(collect($this->salesPerDay)->pluck('label')),
                                        datasets: [{
                                            data: @js(collect($this->salesPerDay)->pluck('total')),
                                            backgroundColor: '#f25623',
                                            borderRadius: 4,
                                        }],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: { callbacks: { label: (context) => rupiah.format(context.parsed.y) } },
                                        },
                                        scales: {
                                            y: { beginAtZero: true, ticks: { callback: (value) => rupiah.format(value) } },
                                        },
                                    },
                                });
                            },
                        }"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                @endif
            </flux:card>
        @endcan

        @can('inventory.view')
            <flux:card class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg">Low stock</flux:heading>
                        <flux:subheading>{{ $this->lowStockCount }} {{ Str::plural('item', $this->lowStockCount) }} under minimum</flux:subheading>
                    </div>
                    @if ($this->lowStockCount > 0)
                        <flux:link :href="route('inventory.stock', ['lowOnly' => 1])" wire:navigate class="text-sm">View all</flux:link>
                    @endif
                </div>

                @forelse ($this->lowStockItems as $item)
                    <a wire:key="low-{{ $item->id }}" href="{{ route('inventory.stock-card', $item) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-md px-2 py-1.5 hover:bg-zinc-100">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-zinc-900">{{ $item->name }}</span>
                            <span class="block font-mono text-xs text-zinc-500">{{ $item->sku }}</span>
                        </span>
                        <span class="shrink-0 text-sm tabular-nums text-red-700">{{ (int) $item->on_hand }} / {{ $item->minimum_stock }} {{ $item->unit }}</span>
                    </a>
                @empty
                    <flux:text>Every item is at or above its minimum stock.</flux:text>
                @endforelse
            </flux:card>
        @endcan
    </div>

    @if (! Auth::user()->canAny(['sales.view', 'purchasing.view', 'inventory.view']))
        <flux:callout icon="information-circle">
            <flux:callout.text>Your role does not include any modules yet. Ask an administrator to grant you access.</flux:callout.text>
        </flux:callout>
    @endif
</div>
