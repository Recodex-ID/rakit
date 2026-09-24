<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidDocumentTransition;
use Closure;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property int $warehouse_id
 * @property SalesOrderStatus $status
 * @property Carbon $order_date
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['customer_id', 'warehouse_id', 'order_date', 'notes', 'created_by'])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order): void {
            $order->number ??= DocumentSequence::next('SO', $order->order_date);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SalesOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function total(): int
    {
        return $this->lines->sum(fn (SalesOrderLine $line) => $line->subtotal());
    }

    public function isEditable(): bool
    {
        return $this->status === SalesOrderStatus::Draft;
    }

    public function confirm(User $user): void
    {
        $this->transition([SalesOrderStatus::Draft], 'confirmed', $user, function (): void {
            if (! $this->lines()->exists()) {
                throw new InvalidDocumentTransition("{$this->number} has no lines, so it cannot be confirmed.");
            }

            $this->forceFill(['status' => SalesOrderStatus::Confirmed, 'confirmed_at' => now()])->save();
        });
    }

    /**
     * Takes every line out of the order's warehouse. If any item is short, nothing
     * is booked: the whole order waits until there is stock for all of it.
     */
    public function deliver(User $user): void
    {
        $this->transition([SalesOrderStatus::Confirmed], 'delivered', $user, function () use ($user): void {
            $lines = $this->lines()->with('item')->get();
            $needed = $lines->groupBy('item_id')->map->sum('quantity');

            Item::lockForStockChange($needed->keys()->all());

            $onHand = StockMovement::query()
                ->where('warehouse_id', $this->warehouse_id)
                ->whereIn('item_id', $needed->keys())
                ->groupBy('item_id')
                ->selectRaw('item_id, SUM(quantity) as on_hand')
                ->pluck('on_hand', 'item_id');

            foreach ($needed as $itemId => $quantity) {
                $available = (int) ($onHand[$itemId] ?? 0);

                if ($available < $quantity) {
                    throw InsufficientStock::for(
                        $lines->firstWhere('item_id', $itemId)->item->name,
                        $this->warehouse->name,
                        $available,
                        $quantity,
                    );
                }
            }

            foreach ($lines as $line) {
                StockMovement::query()->create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity' => -$line->quantity,
                    'type' => StockMovementType::SalesDelivery,
                    'reference_type' => static::class,
                    'reference_id' => $this->id,
                    'note' => "Delivered on {$this->number}",
                    'user_id' => $user->id,
                ]);
            }

            $this->forceFill(['status' => SalesOrderStatus::Delivered, 'delivered_at' => now()])->save();
        });
    }

    public function cancel(User $user): void
    {
        $this->transition(
            [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed],
            'cancelled',
            $user,
            fn () => $this->forceFill(['status' => SalesOrderStatus::Cancelled, 'cancelled_at' => now()])->save(),
        );
    }

    /**
     * Locks the order row, re-reads its status, and only then applies the change,
     * so a double click cannot deliver the same order twice.
     *
     * @param  array<int, SalesOrderStatus>  $allowedFrom
     */
    private function transition(array $allowedFrom, string $pastTense, User $user, Closure $apply): void
    {
        DB::transaction(function () use ($allowedFrom, $pastTense, $user, $apply): void {
            $current = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail()->status;

            if (! in_array($current, $allowedFrom, true)) {
                throw InvalidDocumentTransition::from($this->number, strtolower($current->label()), $pastTense);
            }

            $this->status = $current;
            $apply();

            activity('sales')->performedOn($this)->causedBy($user)->log("{$this->number} was {$pastTense}");
        });
    }
}
