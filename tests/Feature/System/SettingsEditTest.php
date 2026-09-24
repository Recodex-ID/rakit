<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('non-admin cannot access the settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('system.settings'))->assertForbidden();
});

test('admin can view and save company details', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    $this->get(route('system.settings'))->assertOk();

    Livewire::test('pages::app.system.settings')
        ->set('companyName', 'PT Contoh Jaya')
        ->set('companyTaxId', '01.234.567.8-901.000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('company_name'))->toBe('PT Contoh Jaya');
    expect(Setting::get('company_tax_id'))->toBe('01.234.567.8-901.000');
});

test('the company email must be a valid address', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.system.settings')
        ->set('companyEmail', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['companyEmail']);
});
