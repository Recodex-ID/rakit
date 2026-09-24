<?php

namespace App\Livewire\Concerns;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Line editing shared by the purchase and sales order forms: add and remove
 * rows, default the price from the item, and validate every row.
 */
trait ManagesOrderLines
{
    /** @var array<int, array{item_id: int|string, quantity: int|string, unit_price: int|string}> */
    public array $lines = [];

    /**
     * The price to prefill when an item is picked: purchase price on a purchase
     * order, sale price on a sales order.
     */
    abstract protected function defaultPriceFor(Item $item): int;

    public function addLine(): void
    {
        $this->lines[] = ['item_id' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function updatedLines(mixed $value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);

        if ($field !== 'item_id' || blank($value)) {
            return;
        }

        $item = $this->selectableItems->firstWhere('id', (int) $value);

        if ($item) {
            $this->lines[(int) $index]['unit_price'] = $this->defaultPriceFor($item);
        }
    }

    #[Computed]
    public function selectableItems(): Collection
    {
        return Item::query()->active()->orderBy('name')->get();
    }

    public function linesTotal(): int
    {
        return collect($this->lines)->sum(fn (array $line) => (int) $line['quantity'] * (int) $line['unit_price']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function lineRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0', 'max:1000000000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function lineMessages(): array
    {
        return [
            'lines.required' => 'Add at least one line.',
            'lines.min' => 'Add at least one line.',
            'lines.*.item_id.required' => 'Choose an item.',
            'lines.*.item_id.exists' => 'Choose an active item.',
            'lines.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    /**
     * @return array<int, array{item_id: int, quantity: int, unit_price: int}>
     */
    protected function normalizedLines(): array
    {
        return array_map(fn (array $line) => [
            'item_id' => (int) $line['item_id'],
            'quantity' => (int) $line['quantity'],
            'unit_price' => (int) $line['unit_price'],
        ], $this->lines);
    }
}
