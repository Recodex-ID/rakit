<?php

use App\Models\Item;
use App\Models\PurchaseOrderLine;
use App\Models\SalesOrderLine;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Items')] class extends Component
{
    use WithFileUploads, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'active';

    public ?int $editingId = null;

    public string $sku = '';

    public string $name = '';

    public string $unit = 'pcs';

    public int|string $purchasePrice = 0;

    public int|string $salePrice = 0;

    public int|string $minimumStock = 0;

    public string $description = '';

    public bool $isActive = true;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('master-data.manage');

        $this->resetForm();

        Flux::modal('item-form')->show();
    }

    public function edit(int $itemId): void
    {
        Gate::authorize('master-data.manage');

        $item = Item::query()->findOrFail($itemId);

        $this->resetForm();
        $this->editingId = $item->id;
        $this->sku = $item->sku;
        $this->name = $item->name;
        $this->unit = $item->unit;
        $this->purchasePrice = $item->purchase_price;
        $this->salePrice = $item->sale_price;
        $this->minimumStock = $item->minimum_stock;
        $this->description = $item->description ?? '';
        $this->isActive = $item->is_active;

        Flux::modal('item-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('master-data.manage');

        $validated = $this->validate([
            'sku' => ['required', 'string', 'max:64', Rule::unique('items', 'sku')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:16'],
            'purchasePrice' => ['required', 'integer', 'min:0'],
            'salePrice' => ['required', 'integer', 'min:0'],
            'minimumStock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $item = Item::query()->updateOrCreate(['id' => $this->editingId], [
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'purchase_price' => (int) $validated['purchasePrice'],
            'sale_price' => (int) $validated['salePrice'],
            'minimum_stock' => (int) $validated['minimumStock'],
            'description' => $validated['description'] ?: null,
            'is_active' => $validated['isActive'],
        ]);

        if ($this->photo) {
            $item->addMedia($this->photo->getRealPath())
                ->usingFileName($this->photo->hashName())
                ->toMediaCollection('photo');
        }

        Flux::toast(variant: 'success', text: "{$item->name} was saved.");
        Flux::modal('item-form')->close();

        $this->resetForm();
    }

    public function removePhoto(): void
    {
        Gate::authorize('master-data.manage');

        Item::query()->findOrFail($this->editingId)->clearMediaCollection('photo');
    }

    /**
     * Items with history are kept: deleting them would break the stock ledger and
     * old orders. Deactivating hides them from new documents instead.
     */
    public function delete(int $itemId): void
    {
        Gate::authorize('master-data.manage');

        $item = Item::query()->findOrFail($itemId);

        $inUse = $item->stockMovements()->exists()
            || PurchaseOrderLine::query()->where('item_id', $item->id)->exists()
            || SalesOrderLine::query()->where('item_id', $item->id)->exists();

        if ($inUse) {
            Flux::toast(variant: 'danger', text: "{$item->name} has stock or order history, so it cannot be deleted. Deactivate it instead.");

            return;
        }

        $item->delete();

        Flux::toast(variant: 'success', text: "{$item->name} was deleted.");
    }

    #[Computed]
    public function editingItem(): ?Item
    {
        return $this->editingId ? Item::query()->find($this->editingId) : null;
    }

    #[Computed]
    public function items()
    {
        return Item::query()
            ->with('media')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('sku', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'sku', 'name', 'unit', 'purchasePrice', 'salePrice', 'minimumStock', 'description', 'isActive', 'photo');
        $this->resetValidation();
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Items</flux:heading>
            <flux:subheading>Everything you buy, stock and sell.</flux:subheading>
        </div>

        @can('master-data.manage')
            <flux:button variant="primary" icon="plus" wire:click="create">New item</flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search SKU or name" icon="magnifying-glass" class="max-w-sm" aria-label="Search items" />

        <flux:select wire:model.live="status" class="max-w-40" aria-label="Filter by status">
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
            <flux:select.option value="all">All</flux:select.option>
        </flux:select>
    </div>

    <flux:card class="w-full">
        @if ($this->items->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="cube" class="size-8 text-zinc-500" />
                <flux:text>
                    @if (filled($search) || $status !== 'active')
                        No items match these filters.
                    @else
                        No items yet. Add the first one to start recording stock and orders.
                    @endif
                </flux:text>
            </div>
        @else
            <flux:table :paginate="$this->items">
                <flux:table.columns>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column>Unit</flux:table.column>
                    <flux:table.column align="end">Purchase price</flux:table.column>
                    <flux:table.column align="end">Sale price</flux:table.column>
                    <flux:table.column align="end">Minimum stock</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    @can('master-data.manage')
                        <flux:table.column>Actions</flux:table.column>
                    @endcan
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->items as $item)
                        <flux:table.row :key="$item->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($item->getFirstMediaUrl('photo', 'thumb'))
                                        <img src="{{ $item->getFirstMediaUrl('photo', 'thumb') }}" alt="" width="40" height="40" loading="lazy" class="size-10 rounded-lg border border-zinc-200 object-cover">
                                    @else
                                        <div class="flex size-10 items-center justify-center rounded-lg border border-zinc-200 bg-zinc-50">
                                            <flux:icon icon="cube" variant="micro" class="text-zinc-500" />
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-zinc-900">{{ $item->name }}</div>
                                        <div class="font-mono text-xs text-zinc-500">{{ $item->sku }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->unit }}</flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap">{{ Money::format($item->purchase_price) }}</flux:table.cell>
                            <flux:table.cell align="end" class="whitespace-nowrap">{{ Money::format($item->sale_price) }}</flux:table.cell>
                            <flux:table.cell align="end">{{ number_format($item->minimum_stock, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$item->is_active ? 'green' : 'zinc'">{{ $item->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                            </flux:table.cell>
                            @can('master-data.manage')
                                <flux:table.cell class="py-0">
                                    <div class="flex items-center gap-1">
                                        <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $item->id }})" aria-label="Edit {{ $item->name }}" />

                                        <flux:modal.trigger name="delete-item-{{ $item->id }}">
                                            <flux:button type="button" variant="danger" size="sm" icon="trash" aria-label="Delete {{ $item->name }}" />
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="delete-item-{{ $item->id }}" class="max-w-md" focusable>
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete {{ $item->name }}?</flux:heading>
                                                <flux:subheading>Items with stock or order history cannot be deleted; deactivate them instead.</flux:subheading>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="delete({{ $item->id }})">Delete</flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </flux:table.cell>
                            @endcan
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    <flux:modal flyout name="item-form" class="max-w-lg" focusable>
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit item' : 'New item' }}</flux:heading>
                <flux:subheading>Prices are in whole Rupiah.</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input label="SKU" wire:model="sku" />
                <flux:input label="Unit" wire:model="unit" description="e.g. pcs, box, kg" />
            </div>
            <flux:input label="Name" wire:model="name" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input type="number" min="0" step="1" label="Purchase price" wire:model="purchasePrice" />
                <flux:input type="number" min="0" step="1" label="Sale price" wire:model="salePrice" />
            </div>
            <flux:input type="number" min="0" step="1" label="Minimum stock" wire:model="minimumStock" description="The dashboard flags the item once stock falls below this. Use 0 to switch it off." />
            <flux:textarea label="Description" wire:model="description" rows="2" />

            <x-media-upload
                label="Photo"
                wire:model="photo"
                :current-url="$this->editingItem?->getFirstMediaUrl('photo', 'thumb') ?: null"
                :new-upload="$photo"
                remove-action="removePhoto"
            />

            <flux:switch label="Active" wire:model="isActive" description="Inactive items stay in history but cannot be added to new orders." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save item</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
