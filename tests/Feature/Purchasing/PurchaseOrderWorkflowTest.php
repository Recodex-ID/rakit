<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidDocumentTransition;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;

test('purchase orders are numbered per year in sequence', function () {
    $first = PurchaseOrder::factory()->create(['order_date' => '2026-03-01']);
    $second = PurchaseOrder::factory()->create(['order_date' => '2026-03-02']);
    $nextYear = PurchaseOrder::factory()->create(['order_date' => '2027-01-02']);

    expect($first->number)->toBe('PO-2026-0001');
    expect($second->number)->toBe('PO-2026-0002');
    expect($nextYear->number)->toBe('PO-2027-0001');
});

test('the full purchase flow puts the received goods into the order warehouse', function () {
    $warehouse = Warehouse::factory()->create();
    $bolts = Item::factory()->create();
    $nuts = Item::factory()->create();
    $user = User::factory()->create();

    $order = PurchaseOrder::factory()->for($warehouse)->withLines([[$bolts, 100], [$nuts, 40]])->create();

    $order->submit($user);
    $order->approve($user);
    $order->receive($user);

    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
    expect($order->fresh()->approved_by)->toBe($user->id);
    expect($bolts->stockOnHand($warehouse))->toBe(100);
    expect($nuts->stockOnHand($warehouse))->toBe(40);

    $movement = StockMovement::query()->where('item_id', $bolts->id)->firstOrFail();
    expect($movement->type)->toBe(StockMovementType::PurchaseReceipt);
    expect($movement->reference->is($order))->toBeTrue();
});

test('an order without lines cannot be submitted', function () {
    $order = PurchaseOrder::factory()->create();

    expect(fn () => $order->submit(User::factory()->create()))->toThrow(InvalidDocumentTransition::class);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Draft);
});

test('an order cannot skip approval and be received straight away', function () {
    $order = PurchaseOrder::factory()->withLines([[Item::factory()->create(), 5]])->create();
    $user = User::factory()->create();
    $order->submit($user);

    expect(fn () => $order->receive($user))->toThrow(InvalidDocumentTransition::class, 'is submitted, so it cannot be received');
    expect(StockMovement::query()->count())->toBe(0);
});

test('receiving twice books the goods only once', function () {
    $item = Item::factory()->create();
    $order = PurchaseOrder::factory()->withLines([[$item, 5]])->create();
    $user = User::factory()->create();
    $order->submit($user);
    $order->approve($user);
    $order->receive($user);

    $stale = PurchaseOrder::query()->findOrFail($order->id);

    expect(fn () => $order->receive($user))->toThrow(InvalidDocumentTransition::class);
    expect(fn () => $stale->receive($user))->toThrow(InvalidDocumentTransition::class);
    expect($item->stockOnHand())->toBe(5);
});

test('an order can be cancelled until it is received', function () {
    $user = User::factory()->create();

    $draft = PurchaseOrder::factory()->create();
    $draft->cancel($user);
    expect($draft->fresh()->status)->toBe(PurchaseOrderStatus::Cancelled);

    $received = PurchaseOrder::factory()->withLines([[Item::factory()->create(), 1]])->create();
    $received->submit($user);
    $received->approve($user);
    $received->receive($user);

    expect(fn () => $received->cancel($user))->toThrow(InvalidDocumentTransition::class);
});

test('only drafts are editable', function () {
    $user = User::factory()->create();
    $order = PurchaseOrder::factory()->withLines([[Item::factory()->create(), 1]])->create();

    expect($order->isEditable())->toBeTrue();

    $order->submit($user);

    expect($order->isEditable())->toBeFalse();
});

test('the order total is the sum of quantity times unit price', function () {
    $order = PurchaseOrder::factory()->create();
    $order->lines()->create(['item_id' => Item::factory()->create()->id, 'quantity' => 3, 'unit_price' => 12500]);
    $order->lines()->create(['item_id' => Item::factory()->create()->id, 'quantity' => 2, 'unit_price' => 1000]);

    expect($order->fresh()->total())->toBe(39500);
});

test('every status change lands in the audit log', function () {
    $user = User::factory()->create();
    $order = PurchaseOrder::factory()->withLines([[Item::factory()->create(), 1]])->create(['order_date' => '2026-05-05']);

    $order->submit($user);
    $order->approve($user);

    $this->assertDatabaseHas('activity_log', ['log_name' => 'purchasing', 'description' => 'PO-2026-0001 was submitted', 'causer_id' => $user->id]);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'purchasing', 'description' => 'PO-2026-0001 was approved']);
});
