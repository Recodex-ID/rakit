<?php

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Warehouses')] class extends Component
{
    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $address = '';

    public bool $isActive = true;

    public function create(): void
    {
        Gate::authorize('master-data.manage');

        $this->resetForm();

        Flux::modal('warehouse-form')->show();
    }

    public function edit(int $warehouseId): void
    {
        Gate::authorize('master-data.manage');

        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        $this->resetForm();
        $this->editingId = $warehouse->id;
        $this->code = $warehouse->code;
        $this->name = $warehouse->name;
        $this->address = $warehouse->address ?? '';
        $this->isActive = $warehouse->is_active;

        Flux::modal('warehouse-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('master-data.manage');

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('warehouses', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ]);

        $warehouse = Warehouse::query()->updateOrCreate(['id' => $this->editingId], [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'address' => $validated['address'] ?: null,
            'is_active' => $validated['isActive'],
        ]);

        Flux::toast(variant: 'success', text: "{$warehouse->name} was saved.");
        Flux::modal('warehouse-form')->close();

        $this->resetForm();
    }

    public function delete(int $warehouseId): void
    {
        Gate::authorize('master-data.manage');

        $warehouse = Warehouse::query()->findOrFail($warehouseId);

        $inUse = $warehouse->stockMovements()->exists()
            || PurchaseOrder::query()->where('warehouse_id', $warehouse->id)->exists()
            || SalesOrder::query()->where('warehouse_id', $warehouse->id)->exists();

        if ($inUse) {
            Flux::toast(variant: 'danger', text: "{$warehouse->name} has stock or order history, so it cannot be deleted. Deactivate it instead.");

            return;
        }

        $warehouse->delete();

        Flux::toast(variant: 'success', text: "{$warehouse->name} was deleted.");
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderByDesc('is_active')->orderBy('name')->get();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'address', 'isActive');
        $this->resetValidation();
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Warehouses</flux:heading>
            <flux:subheading>Places that hold stock. Every stock movement belongs to one warehouse.</flux:subheading>
        </div>

        @can('master-data.manage')
            <flux:button variant="primary" icon="plus" wire:click="create">New warehouse</flux:button>
        @endcan
    </div>

    <flux:card class="w-full">
        @if ($this->warehouses->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="building-storefront" class="size-8 text-zinc-500" />
                <flux:text>No warehouses yet. Stock and orders need at least one.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Code</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Address</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    @can('master-data.manage')
                        <flux:table.column>Actions</flux:table.column>
                    @endcan
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->warehouses as $warehouse)
                        <flux:table.row :key="$warehouse->id">
                            <flux:table.cell class="font-mono">{{ $warehouse->code }}</flux:table.cell>
                            <flux:table.cell variant="strong">{{ $warehouse->name }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $warehouse->address ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$warehouse->is_active ? 'green' : 'zinc'">{{ $warehouse->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                            </flux:table.cell>
                            @can('master-data.manage')
                                <flux:table.cell class="py-0">
                                    <div class="flex items-center gap-1">
                                        <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $warehouse->id }})" aria-label="Edit {{ $warehouse->name }}" />
                                        <flux:modal.trigger name="delete-warehouse-{{ $warehouse->id }}">
                                            <flux:button type="button" variant="danger" size="sm" icon="trash" aria-label="Delete {{ $warehouse->name }}" />
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="delete-warehouse-{{ $warehouse->id }}" class="max-w-md" focusable>
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete {{ $warehouse->name }}?</flux:heading>
                                                <flux:subheading>Warehouses with stock or order history cannot be deleted; deactivate them instead.</flux:subheading>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="delete({{ $warehouse->id }})">Delete</flux:button>
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

    <flux:modal flyout name="warehouse-form" class="max-w-md" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Edit warehouse' : 'New warehouse' }}</flux:heading>

            <flux:input label="Code" wire:model="code" description="Short and unique, e.g. WH-JKT" />
            <flux:input label="Name" wire:model="name" />
            <flux:textarea label="Address" wire:model="address" rows="3" />
            <flux:switch label="Active" wire:model="isActive" description="Inactive warehouses cannot be picked on new orders." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save warehouse</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
