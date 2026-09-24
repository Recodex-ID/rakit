<?php

use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->syncRoles(['admin']);
    $this->actingAs($this->admin);
});

test('an item with stock history cannot be deleted', function () {
    $item = Item::factory()->withStock(3, Warehouse::factory()->create())->create();

    Livewire::test('pages::app.master-data.items')->call('delete', $item->id);

    expect(Item::query()->whereKey($item->id)->exists())->toBeTrue();
});

test('an unused item can be deleted', function () {
    $item = Item::factory()->create();

    Livewire::test('pages::app.master-data.items')->call('delete', $item->id);

    expect(Item::query()->whereKey($item->id)->exists())->toBeFalse();
});

test('staff cannot delete items', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['staff']);
    $item = Item::factory()->create();

    $this->actingAs($staff);

    Livewire::test('pages::app.master-data.items')->call('delete', $item->id)->assertForbidden();
});
