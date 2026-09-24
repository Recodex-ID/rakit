<?php

use App\Enums\Permission;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new #[Title('Roles & permissions')] class extends Component
{
    /**
     * Roles the app itself relies on: they can be edited (except super-admin)
     * but never deleted.
     */
    public const CORE_ROLES = ['super-admin', 'admin', 'staff'];

    #[Url(as: 'role')]
    public string $selectedRole = 'admin';

    /** @var list<string> */
    public array $granted = [];

    public string $newRoleName = '';

    public function mount(): void
    {
        if (! $this->roles->contains('name', $this->selectedRole)) {
            $this->selectedRole = 'admin';
        }

        $this->loadGranted();
    }

    public function selectRole(string $name): void
    {
        $this->selectedRole = $name;
        $this->loadGranted();
        $this->resetErrorBag();
    }

    public function save(): void
    {
        if ($this->selectedRole === 'super-admin') {
            return;
        }

        $this->validate([
            'granted' => ['array'],
            'granted.*' => ['string', Rule::in(Permission::values())],
        ]);

        $role = Role::findByName($this->selectedRole);
        $before = $role->permissions->pluck('name')->sort()->values()->all();
        $role->syncPermissions($this->granted);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('roles')
            ->performedOn($role)
            ->withProperties(['old' => $before, 'attributes' => collect($this->granted)->sort()->values()->all()])
            ->log("Permissions for the {$role->name} role were updated");

        Flux::toast(variant: 'success', text: 'Permissions for '.Str::headline($role->name).' were saved.');
    }

    public function createRole(): void
    {
        $this->newRoleName = Str::slug($this->newRoleName);

        $this->validate([
            'newRoleName' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')],
        ], attributes: ['newRoleName' => 'role name']);

        $role = Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);

        activity('roles')->performedOn($role)->log("The {$role->name} role was created");

        $this->reset('newRoleName');
        unset($this->roles);
        Flux::modal('create-role')->close();
        $this->selectRole($role->name);
    }

    public function deleteRole(): void
    {
        if (in_array($this->selectedRole, self::CORE_ROLES, true)) {
            return;
        }

        $role = Role::findByName($this->selectedRole);

        if ($role->users()->exists()) {
            Flux::modal('delete-role')->close();
            Flux::toast(variant: 'danger', text: 'Move its members to another role first. A role with members cannot be deleted.');

            return;
        }

        activity('roles')->withProperties(['name' => $role->name])->log("The {$role->name} role was deleted");

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Flux::modal('delete-role')->close();
        Flux::toast(variant: 'success', text: 'The role was deleted.');
        unset($this->roles);
        $this->selectRole('admin');
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->withCount('users')->orderBy('id')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<string, list<Permission>>
     */
    #[Computed]
    public function permissionsByModule()
    {
        return collect(Permission::cases())->groupBy(fn (Permission $permission) => $permission->module());
    }

    private function loadGranted(): void
    {
        $this->granted = Role::findByName($this->selectedRole)->permissions->pluck('name')->all();
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Roles &amp; permissions</flux:heading>
            <flux:subheading>Decide what each role can see and do. Assign roles to people on the Users page.</flux:subheading>
        </div>

        <flux:modal.trigger name="create-role">
            <flux:button variant="primary" icon="plus">New role</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[16rem_1fr]">
        <flux:card class="h-fit p-2">
            <nav aria-label="Roles" class="flex flex-col gap-1">
                @foreach ($this->roles as $role)
                    <button
                        type="button"
                        wire:key="role-{{ $role->id }}"
                        wire:click="selectRole('{{ $role->name }}')"
                        @class([
                            'flex items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-sm',
                            'bg-zinc-900 text-white' => $role->name === $selectedRole,
                            'text-zinc-700 hover:bg-zinc-100' => $role->name !== $selectedRole,
                        ])
                        @if ($role->name === $selectedRole) aria-current="true" @endif
                    >
                        <span class="font-medium">{{ Str::headline($role->name) }}</span>
                        <span class="tabular-nums text-xs">{{ $role->users_count }} {{ Str::plural('member', $role->users_count) }}</span>
                    </button>
                @endforeach
            </nav>
        </flux:card>

        <flux:card class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ Str::headline($selectedRole) }}</flux:heading>
                    @if (in_array($selectedRole, $this::CORE_ROLES, true))
                        <flux:text class="text-sm">Built-in role. It can be edited but not deleted.</flux:text>
                    @endif
                </div>

                @unless (in_array($selectedRole, $this::CORE_ROLES, true))
                    <flux:modal.trigger name="delete-role">
                        <flux:button variant="danger" size="sm" icon="trash">Delete role</flux:button>
                    </flux:modal.trigger>
                @endunless
            </div>

            @if ($selectedRole === 'super-admin')
                <flux:callout icon="shield-check">
                    <flux:callout.text>Super admins pass every permission check, including the ones added later. There is nothing to tick here.</flux:callout.text>
                </flux:callout>
            @else
                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        @foreach ($this->permissionsByModule as $module => $permissions)
                            <fieldset wire:key="module-{{ Str::slug($module) }}" class="space-y-3">
                                <legend class="mb-2 text-sm font-semibold text-zinc-900">{{ $module }}</legend>
                                @foreach ($permissions as $permission)
                                    <flux:checkbox
                                        wire:key="permission-{{ $permission->value }}"
                                        wire:model="granted"
                                        value="{{ $permission->value }}"
                                        label="{{ Str::headline(Str::after($permission->value, '.')) }}"
                                        description="{{ $permission->description() }}"
                                    />
                                @endforeach
                            </fieldset>
                        @endforeach
                    </div>

                    <div class="flex justify-end">
                        <flux:button type="submit" variant="primary">Save permissions</flux:button>
                    </div>
                </form>
            @endif

            @if (in_array($selectedRole, ['admin', 'super-admin'], true))
                <flux:text class="text-sm">Admins and super admins also get the System menu (users, settings, media library). That access comes from the role itself, not from a permission above.</flux:text>
            @endif
        </flux:card>
    </div>

    <flux:modal name="create-role" class="md:w-96" focusable>
        <form wire:submit="createRole" class="space-y-6">
            <div>
                <flux:heading size="lg">New role</flux:heading>
                <flux:subheading>It starts with no permissions. Tick them after creating it.</flux:subheading>
            </div>
            <flux:input wire:model="newRoleName" label="Name" placeholder="warehouse-clerk" description="Lowercase with dashes. Spaces are converted for you." />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create role</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-role" class="max-w-md" focusable>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete {{ Str::headline($selectedRole) }}?</flux:heading>
                <flux:subheading>Only roles without members can be deleted.</flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Keep role</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRole">Delete role</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
