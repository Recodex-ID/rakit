<?php

use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidDocumentTransition;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;

test('sales orders are numbered per year in sequence', function () {
    expect(SalesOrder::factory()->create(['order_date' => '2026-01-10'])->number)->toBe('SO-2026-0001');
    expect(SalesOrder::factory()->create(['order_date' => '2026-01-11'])->number)->toBe('SO-2026-0002');
});

test('delivering takes the goods out of the order warehouse', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(10, $warehouse)->create();
    $user = User::factory()->create();

    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 4]])->create();
    $order->confirm($user);
    $order->deliver($user);

    expect($order->fresh()->status)->toBe(SalesOrderStatus::Delivered);
    expect($item->stockOnHand($warehouse))->toBe(6);

    $movement = StockMovement::query()->where('type', StockMovementType::SalesDelivery)->firstOrFail();
    expect($movement->quantity)->toBe(-4);
    expect($movement->reference->is($order))->toBeTrue();
});

test('a delivery short on any line books nothing and leaves the order confirmed', function () {
    $warehouse = Warehouse::factory()->create();
    $plenty = Item::factory()->withStock(50, $warehouse)->create();
    $scarce = Item::factory()->withStock(2, $warehouse)->create(['name' => 'Copper wire']);
    $user = User::factory()->create();

    $order = SalesOrder::factory()->for($warehouse)->withLines([[$plenty, 5], [$scarce, 3]])->create();
    $order->confirm($user);

    expect(fn () => $order->deliver($user))
        ->toThrow(InsufficientStock::class, 'Not enough Copper wire');

    expect($order->fresh()->status)->toBe(SalesOrderStatus::Confirmed);
    expect($plenty->stockOnHand($warehouse))->toBe(50);
    expect(StockMovement::query()->where('type', StockMovementType::SalesDelivery)->count())->toBe(0);
});

test('the same item on two lines is checked against stock as one total', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(5, $warehouse)->create();
    $user = User::factory()->create();

    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 3], [$item, 3]])->create();
    $order->confirm($user);

    expect(fn () => $order->deliver($user))->toThrow(InsufficientStock::class);
    expect($item->stockOnHand($warehouse))->toBe(5);
});

test('stock in another warehouse does not count', function () {
    $shipFrom = Warehouse::factory()->create();
    $elsewhere = Warehouse::factory()->create();
    $item = Item::factory()->withStock(100, $elsewhere)->create();
    $user = User::factory()->create();

    $order = SalesOrder::factory()->for($shipFrom)->withLines([[$item, 1]])->create();
    $order->confirm($user);

    expect(fn () => $order->deliver($user))->toThrow(InsufficientStock::class);
});

test('a draft cannot be delivered and an order without lines cannot be confirmed', function () {
    $user = User::factory()->create();
    $empty = SalesOrder::factory()->create();

    expect(fn () => $empty->confirm($user))->toThrow(InvalidDocumentTransition::class);

    $warehouse = Warehouse::factory()->create();
    $draft = SalesOrder::factory()->for($warehouse)->withLines([[Item::factory()->withStock(5, $warehouse)->create(), 1]])->create();

    expect(fn () => $draft->deliver($user))->toThrow(InvalidDocumentTransition::class, 'is draft, so it cannot be delivered');
});

test('a delivered order cannot be cancelled or delivered again', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(10, $warehouse)->create();
    $user = User::factory()->create();

    $order = SalesOrder::factory()->for($warehouse)->withLines([[$item, 2]])->create();
    $order->confirm($user);
    $order->deliver($user);

    expect(fn () => $order->cancel($user))->toThrow(InvalidDocumentTransition::class);
    expect(fn () => $order->deliver($user))->toThrow(InvalidDocumentTransition::class);
    expect($item->stockOnHand($warehouse))->toBe(8);
});

test('a confirmed order can still be cancelled', function () {
    $user = User::factory()->create();
    $order = SalesOrder::factory()->withLines([[Item::factory()->create(), 1]])->create();
    $order->confirm($user);
    $order->cancel($user);

    expect($order->fresh()->status)->toBe(SalesOrderStatus::Cancelled);
});
