<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->numberBetween(5, 500) * 1000;

        return [
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'name' => ucfirst(fake()->word()).' '.fake()->word(),
            'unit' => fake()->randomElement(['pcs', 'box', 'kg', 'm']),
            'purchase_price' => $purchasePrice,
            'sale_price' => (int) round($purchasePrice * 1.3, -2),
            'minimum_stock' => fake()->numberBetween(0, 20),
            'description' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Gives the item an opening balance in a warehouse through the ledger.
     */
    public function withStock(int $quantity, Warehouse $warehouse): static
    {
        return $this->afterCreating(function (Item $item) use ($quantity, $warehouse): void {
            $item->stockMovements()->create([
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'type' => StockMovementType::Adjustment,
                'note' => 'Opening balance',
            ]);
        });
    }
}
