<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * Define the model's default state. Orders start as drafts; use the model's
     * confirm/deliver methods to move them on.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
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
        return $this->afterCreating(function (SalesOrder $order) use ($lines): void {
            foreach ($lines as [$item, $quantity]) {
                $order->lines()->create([
                    'item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $item->sale_price,
                ]);
            }
        });
    }
}
