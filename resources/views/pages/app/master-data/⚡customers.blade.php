<?php

use App\Models\Customer;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component
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

        Flux::modal('customer-form')->show();
    }

    public function edit(int $customerId): void
    {
        Gate::authorize('master-data.manage');

        $customer = Customer::query()->findOrFail($customerId);

        $this->resetForm();
        $this->editingId = $customer->id;
        $this->code = $customer->code;
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->isActive = $customer->is_active;

        Flux::modal('customer-form')->show();
    }

    public function save(): void
    {
        Gate::authorize('master-data.manage');

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('customers', 'code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ]);

        $customer = Customer::query()->updateOrCreate(['id' => $this->editingId], [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'is_active' => $validated['isActive'],
        ]);

        Flux::toast(variant: 'success', text: "{$customer->name} was saved.");
        Flux::modal('customer-form')->close();

        $this->resetForm();
    }

    public function delete(int $customerId): void
    {
        Gate::authorize('master-data.manage');

        $customer = Customer::query()->findOrFail($customerId);

        if ($customer->salesOrders()->exists()) {
            Flux::toast(variant: 'danger', text: "{$customer->name} has sales orders, so it cannot be deleted. Deactivate it instead.");

            return;
        }

        $customer->delete();

        Flux::toast(variant: 'success', text: "{$customer->name} was deleted.");
    }

    #[Computed]
    public function customers()
    {
        return Customer::query()
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
            <flux:heading size="xl">Customers</flux:heading>
            <flux:subheading>The businesses and people you sell to.</flux:subheading>
        </div>

        @can('master-data.manage')
            <flux:button variant="primary" icon="plus" wire:click="create">New customer</flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search code, name or email" icon="magnifying-glass" class="max-w-sm" aria-label="Search customers" />

    <flux:card class="w-full">
        @if ($this->customers->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-center">
                <flux:icon icon="user-group" class="size-8 text-zinc-500" />
                <flux:text>{{ filled($search) ? 'No customers match this search.' : 'No customers yet. Add one before creating a sales order.' }}</flux:text>
            </div>
        @else
            <flux:table :paginate="$this->customers">
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
                    @foreach ($this->customers as $customer)
                        <flux:table.row :key="$customer->id">
                            <flux:table.cell class="font-mono">{{ $customer->code }}</flux:table.cell>
                            <flux:table.cell variant="strong">{{ $customer->name }}</flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $customer->email ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $customer->phone }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$customer->is_active ? 'green' : 'zinc'">{{ $customer->is_active ? 'Active' : 'Inactive' }}</flux:badge>
                            </flux:table.cell>
                            @can('master-data.manage')
                                <flux:table.cell class="py-0">
                                    <div class="flex items-center gap-1">
                                        <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $customer->id }})" aria-label="Edit {{ $customer->name }}" />
                                        <flux:modal.trigger name="delete-customer-{{ $customer->id }}">
                                            <flux:button type="button" variant="danger" size="sm" icon="trash" aria-label="Delete {{ $customer->name }}" />
                                        </flux:modal.trigger>
                                    </div>

                                    <flux:modal name="delete-customer-{{ $customer->id }}" class="max-w-md" focusable>
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">Delete {{ $customer->name }}?</flux:heading>
                                                <flux:subheading>Customers with sales orders cannot be deleted; deactivate them instead.</flux:subheading>
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:button variant="danger" wire:click="delete({{ $customer->id }})">Delete</flux:button>
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

    <flux:modal flyout name="customer-form" class="max-w-md" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? 'Edit customer' : 'New customer' }}</flux:heading>

            <flux:input label="Code" wire:model="code" description="Short and unique, e.g. CUS-0001" />
            <flux:input label="Name" wire:model="name" />
            <flux:input type="email" label="Email" wire:model="email" />
            <flux:input type="tel" label="Phone" wire:model="phone" />
            <flux:textarea label="Address" wire:model="address" rows="3" />
            <flux:switch label="Active" wire:model="isActive" description="Inactive customers cannot be picked on new orders." />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save customer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
