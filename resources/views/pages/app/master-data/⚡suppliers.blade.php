<?php

use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Suppliers')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('master-data.manage');

        $this->resetForm();

        Flux::modal('supplier-form')->show();
    }

    public function edit(int $supplierId): void
    {
        Gate::authorize('master-data.manage');

        $supplier = Supplier::query()->findOrFail($supplierId);

        $this->resetForm();
        $this->editingId = $supplier->id;
        $this->code = $supplier->code;
        $this->name = $supplier->name;
        $this->email = $supplier->email ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->isActive = $supplier->is_active;

        Flux::modal('supplier-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('master-data.manage');

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('suppliers', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ]);

        $supplier = Supplier::query()->updateOrCreate(['id' => $this->editingId], [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'is_active' => $validated['isActive'],
        ]);

        Flux::toast(variant: 'success', text: "{$supplier->name} was saved.");
        Flux::modal('supplier-form')->close();

        $this->resetForm();
    }

    public function delete(int $supplierId): void
    {
        Gate::authorize('master-data.manage');

        $supplier = Supplier::query()->findOrFail($supplierId);

        if ($supplier->purchaseOrders()->exists()) {
            Flux::toast(variant: 'danger', text: "{$supplier->name} has purchase orders, so it cannot be deleted. Deactivate it instead.");

            return;
        }

        $supplier->delete();

        Flux::toast(variant: 'success', text: "{$supplier->name} was deleted.");
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'email', 'phone', 'address', 'isActive');
        $this->resetValidation();
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Suppliers</flux:heading>
            <flux:subheading>The businesses you buy from.</flux:subheading>
        </div>

        @can('master-data.manage')
            <flux:button variant="primary" icon="plus" wire:click="create">New supplier</flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search code, name or email" icon="magnifying-glass" class="max-w-sm" aria-label="Search suppliers" />

    <flux:card class="w-full">
        @if ($this->suppliers->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="building-office" class="size-8 text-zinc-500" />
                <flux:text>{{ filled($search) ? 'No suppliers match this search.' : 'No suppliers yet. Add one before creating a purchase order.' }}</flux:text>
            </div>
        @else
            <flux:table :paginate="$this->suppliers">
                <flux:table.columns>
                    <flux:table.column>Code</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Contact</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    @can('master-data.manage')
                        <flux:table.column>Actions</flux:table.column>
                    @endcan
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->suppliers as $supplier)
                        <flux:table.row :key="$supplier->id">
                            <flux:table.cell class="font-mono">{{ $supplier->code }}</flux:table.cell>
                            <flux:table.cell variant="strong">{{ $supplier->name }}</flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $supplier->email ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $supplier->phone }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$supplier->is_active ? 'green' : 'zinc'">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                            </flux:table.cell>
                            @can('master-data.manage')
                                <flux:table.cell class="py-0">
                                    <div class="flex items-center gap-1">
                                        <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $supplier->id }})" aria-label="Edit {{ $supplier->name }}" />
                                        <flux:modal.trigger name="delete-supplier-{{ $supplier->id }}">
                                            <flux:button type="button" variant="danger" size="sm" icon="trash" aria-label="Delete {{ $supplier->name }}" />
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="delete-supplier-{{ $supplier->id }}" class="max-w-md" focusable>
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete {{ $supplier->name }}?</flux:heading>
                                                <flux:subheading>Suppliers with purchase orders cannot be deleted; deactivate them instead.</flux:subheading>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="delete({{ $supplier->id }})">Delete</flux:button>
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

    <flux:modal flyout name="supplier-form" class="max-w-md" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Edit supplier' : 'New supplier' }}</flux:heading>

            <flux:input label="Code" wire:model="code" description="Short and unique, e.g. SUP-0001" />
            <flux:input label="Name" wire:model="name" />
            <flux:input type="email" label="Email" wire:model="email" />
            <flux:input type="tel" label="Phone" wire:model="phone" />
            <flux:textarea label="Address" wire:model="address" rows="3" />
            <flux:switch label="Active" wire:model="isActive" description="Inactive suppliers cannot be picked on new orders." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save supplier</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
