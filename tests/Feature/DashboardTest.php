<?php

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;

test('the dashboard shows real totals and low stock', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(10, $warehouse)->create(['name' => 'Copper wire', 'sale_price' => 100000, 'minimum_stock' => 8]);

    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 3]])->create();
    $order->confirm($admin);
    $order->deliver($admin);

    PurchaseOrder::factory()->withLines([[$item, 5]])->create()->submit($admin);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(Money::format(300000), false)
        ->assertSee('Copper wire')
        ->assertSee('1 item under minimum');
});

test('staff without sales access does not see sales figures', function () {
    $staff = User::factory()->create();
    $staff->syncRoles(['staff']);
    $staff->roles->first()->revokePermissionTo('sales.view');

    $this->actingAs($staff->fresh())->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Sales this month')
        ->assertSee('Low stock');
});
