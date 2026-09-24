<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state. Orders start as drafts; use the model's
     * submit/approve/receive methods to move them on, so tests go through the
     * same rules as the app.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_date' => now()->toDateString(),
            'notes' => null,
        ];
    }

    /**
     * @param  array<int, array{0: Item, 1: int}>  $lines  item and quantity pairs
     */
    public function withLines(array $lines): static
    {
        return $this->afterCreating(function (PurchaseOrder $order) use ($lines): void {
            foreach ($lines as [$item, $quantity]) {
                $order->lines()->create([
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $item->purchase_price,
                ]);
            }
        });
    }
}
