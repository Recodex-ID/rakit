<?php

use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->syncRoles(['super-admin']);
});

test('only super admins can open the roles page', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    $this->actingAs($admin)->get(route('super-admin.roles'))->assertForbidden();
    $this->actingAs($this->superAdmin)->get(route('super-admin.roles'))->assertOk()->assertSee('Purchasing');
});

test('granting a permission to a role takes effect for its members and is logged', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['staff']);
    expect($staff->can('inventory.manage'))->toBeFalse();

    $this->actingAs($this->superAdmin);

    Livewire::test('pages::super-admin.roles')
        ->call('selectRole', 'staff')
        ->set('granted', ['inventory.view', 'inventory.manage'])
        ->call('save')
        ->assertHasNoErrors();

    expect($staff->fresh()->can('inventory.manage'))->toBeTrue();
    expect($staff->fresh()->can('sales.view'))->toBeFalse();
    expect(Activity::query()->where('log_name', 'roles')->latest('id')->first()->description)
        ->toBe('Permissions for the staff role were updated');
});

test('unknown permission names are rejected', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test('pages::super-admin.roles')
        ->call('selectRole', 'staff')
        ->set('granted', ['launch.missiles'])
        ->call('save')
        ->assertHasErrors(['granted.0']);
});

test('a new role is created with no permissions and a slug name', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test('pages::super-admin.roles')
        ->set('newRoleName', 'Warehouse Clerk')
        ->call('createRole')
        ->assertHasNoErrors()
        ->assertSet('selectedRole', 'warehouse-clerk');

    expect(Role::findByName('warehouse-clerk')->permissions)->toBeEmpty();
});

test('built-in roles and roles with members cannot be deleted', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test('pages::super-admin.roles')->call('selectRole', 'staff')->call('deleteRole');
    expect(Role::query()->where('name', 'staff')->exists())->toBeTrue();

    Role::create(['name' => 'auditor', 'guard_name' => 'web']);
    User::factory()->create()->syncRoles(['auditor']);

    Livewire::test('pages::super-admin.roles')->call('selectRole', 'auditor')->call('deleteRole');
    expect(Role::query()->where('name', 'auditor')->exists())->toBeTrue();
});

test('an empty custom role can be deleted', function () {
    Role::create(['name' => 'auditor', 'guard_name' => 'web']);
    $this->actingAs($this->superAdmin);

    Livewire::test('pages::super-admin.roles')->call('selectRole', 'auditor')->call('deleteRole');

    expect(Role::query()->where('name', 'auditor')->exists())->toBeFalse();
});
