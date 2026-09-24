<?php

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;

test('an adjustment adds or removes stock through the ledger', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->create();
    $user = User::factory()->create();

    $item->adjustStock($warehouse, 10, 'Opening count', $user);
    $item->adjustStock($warehouse, -3, 'Damaged in storage', $user);

    expect($item->stockOnHand($warehouse))->toBe(7);
    expect(StockMovement::query()->where('item_id', $item->id)->pluck('quantity')->all())->toBe([10, -3]);
    expect(StockMovement::query()->first()->type)->toBe(StockMovementType::Adjustment);
});

test('an adjustment cannot take stock below zero and writes nothing when refused', function () {
    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->withStock(5, $warehouse)->create();
    $user = User::factory()->create();

    expect(fn () => $item->adjustStock($warehouse, -6, 'Miscount', $user))
        ->toThrow(InsufficientStock::class);

    expect($item->stockOnHand($warehouse))->toBe(5);
    expect(StockMovement::query()->count())->toBe(1);
});

test('an adjustment of zero is rejected', function () {
    $item = Item::factory()->create();

    expect(fn () => $item->adjustStock(Warehouse::factory()->create(), 0, 'Nothing', User::factory()->create()))
        ->toThrow(InvalidArgumentException::class);
});

test('stock is kept separately per warehouse', function () {
    $jakarta = Warehouse::factory()->create();
    $surabaya = Warehouse::factory()->create();
    $item = Item::factory()->withStock(8, $jakarta)->withStock(2, $surabaya)->create();

    expect($item->stockOnHand($jakarta))->toBe(8);
    expect($item->stockOnHand($surabaya))->toBe(2);
    expect($item->stockOnHand())->toBe(10);

    $user = User::factory()->create();

    expect(fn () => $item->adjustStock($surabaya, -3, 'Wrong warehouse', $user))->toThrow(InsufficientStock::class);
});

test('the on-hand scope matches the ledger for every item', function () {
    $warehouse = Warehouse::factory()->create();
    $other = Warehouse::factory()->create();
    $stocked = Item::factory()->withStock(4, $warehouse)->withStock(9, $other)->create();
    $empty = Item::factory()->create();

    $all = Item::query()->withStockOnHand()->get()->keyBy('id');
    $oneWarehouse = Item::query()->withStockOnHand($warehouse->id)->get()->keyBy('id');

    expect((int) $all[$stocked->id]->on_hand)->toBe(13);
    expect((int) $all[$empty->id]->on_hand)->toBe(0);
    expect((int) $oneWarehouse[$stocked->id]->on_hand)->toBe(4);
});

test('an item flags itself as below minimum only when a minimum is set', function () {
    $item = Item::factory()->make(['minimum_stock' => 10]);
    $noMinimum = Item::factory()->make(['minimum_stock' => 0]);

    expect($item->isBelowMinimum(9))->toBeTrue();
    expect($item->isBelowMinimum(10))->toBeFalse();
    expect($noMinimum->isBelowMinimum(0))->toBeFalse();
});

test('adjustments are written to the audit log with the reason', function () {
    $warehouse = Warehouse::factory()->create(['code' => 'WH-JKT']);
    $item = Item::factory()->create(['sku' => 'SKU-1']);

    $item->adjustStock($warehouse, 5, 'Found during stock opname', User::factory()->create());

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'inventory',
        'description' => 'Stock of SKU-1 in WH-JKT adjusted by +5',
    ]);
});
