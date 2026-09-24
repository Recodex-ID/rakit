<?php

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->syncRoles(['admin']);
    $this->actingAs($this->admin);
});

test('the form saves a draft with prices prefilled from the item', function () {
    $customer = Customer::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->create(['sale_price' => 25000]);

    Livewire::test('pages::app.sales.order-form')
        ->set('customerId', $customer->id)
        ->set('warehouseId', $warehouse->id)
        ->set('lines.0.item_id', $item->id)
        ->assertSet('lines.0.unit_price', 25000)
        ->set('lines.0.quantity', 3)
        ->call('save')
        ->assertHasNoErrors();

    $order = SalesOrder::query()->sole();
    expect($order->status)->toBe(SalesOrderStatus::Draft);
    expect($order->total())->toBe(75000);
});

test('the form refuses an order without lines', function () {
    Livewire::test('pages::app.sales.order-form')
        ->set('customerId', Customer::factory()->create()->id)
        ->set('warehouseId', Warehouse::factory()->create()->id)
        ->call('removeLine', 0)
        ->call('save')
        ->assertHasErrors(['lines']);
});

test('a short delivery shows the reason and leaves the order confirmed', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(1, $warehouse)->create(['name' => 'Copper wire']);
    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 5]])->create();
    $order->confirm($this->admin);

    Livewire::test('pages::app.sales.order-show', ['salesOrder' => $order])
        ->assertSee('(short)')
        ->call('deliver');

    expect($order->fresh()->status)->toBe(SalesOrderStatus::Confirmed);
    expect($item->stockOnHand())->toBe(1);
});

test('a delivered order cannot be edited', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(5, $warehouse)->create();
    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 2]])->create();
    $order->confirm($this->admin);
    $order->deliver($this->admin);

    $this->get(route('sales.orders.edit', $order))->assertRedirect(route('sales.orders.show', $order));
});

test('the print page shows the company details and the order total', function () {
    Setting::put('company_name', 'PT Contoh Jaya');
    Setting::put('company_tax_id', '01.234.567.8-901.000');
    $item = Item::factory()->create(['sale_price' => 1250000]);
    $order = SalesOrder::factory()->withLines([[$item, 2]])->create();

    $this->get(route('sales.orders.print', $order))
        ->assertOk()
        ->assertSee('PT Contoh Jaya')
        ->assertSee('01.234.567.8-901.000')
        ->assertSee($order->number)
        ->assertSee(Money::format(2500000), false);
});
