<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Livewire\Livewire;

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    return $user;
}

test('staff can open every module but not the create forms', function () {
    $this->actingAs(userWithRole('staff'));

    foreach (['master-data.items', 'inventory.stock', 'purchasing.orders', 'sales.orders'] as $route) {
        $this->get(route($route))->assertOk();
    }

    $this->get(route('purchasing.orders.create'))->assertForbidden();
    $this->get(route('sales.orders.create'))->assertForbidden();
    $this->get(route('system.users'))->assertForbidden();
});

test('staff cannot move an order even by calling the action directly', function () {
    $order = SalesOrder::factory()->create();
    $this->actingAs(userWithRole('staff'));

    Livewire::test('pages::app.sales.order-show', ['salesOrder' => $order])
        ->call('confirm')
        ->assertForbidden();

    expect($order->fresh()->isEditable())->toBeTrue();
});

test('a user without any role only reaches the dashboard', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk()->assertSee('Your role does not include any modules yet');
    $this->get(route('sales.orders'))->assertForbidden();
    $this->get(route('master-data.items'))->assertForbidden();
});

test('approving a purchase order needs the approve permission, not just manage', function () {
    $clerk = userWithRole('staff');
    $clerk->givePermissionTo('purchasing.manage');
    $order = PurchaseOrder::factory()->withLines([[Item::factory()->create(), 5]])->create();
    $order->submit($clerk);

    $this->actingAs($clerk);

    Livewire::test('pages::app.purchasing.order-show', ['purchaseOrder' => $order])
        ->call('approve')
        ->assertForbidden();

    $clerk->givePermissionTo('purchasing.approve');

    Livewire::test('pages::app.purchasing.order-show', ['purchaseOrder' => $order])
        ->call('approve')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Approved);
});

test('super admin passes every permission check, including ones no role grants', function () {
    $superAdmin = userWithRole('super-admin');

    expect($superAdmin->can('purchasing.approve'))->toBeTrue();
    expect($superAdmin->can('sales.manage'))->toBeTrue();
});
