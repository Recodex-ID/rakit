<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidDocumentTransition;
use Closure;
use Database\Factories\PurchaseOrderFactory;
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
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property PurchaseOrderStatus $status
 * @property Carbon $order_date
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $received_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'warehouse_id', 'order_date', 'notes', 'created_by'])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $order): void {
            $order->number ??= DocumentSequence::next('PO', $order->order_date);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PurchaseOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function total(): int
    {
        return $this->lines->sum(fn (PurchaseOrderLine $line) => $line->subtotal());
    }

    public function isEditable(): bool
    {
        return $this->status === PurchaseOrderStatus::Draft;
    }

    public function submit(User $user): void
    {
        $this->transition([PurchaseOrderStatus::Draft], 'submitted', $user, function (): void {
            if (! $this->lines()->exists()) {
                throw new InvalidDocumentTransition("{$this->number} has no lines, so it cannot be submitted.");
            }

            $this->forceFill(['status' => PurchaseOrderStatus::Submitted, 'submitted_at' => now()])->save();
        });
    }

    public function approve(User $user): void
    {
        $this->transition([PurchaseOrderStatus::Submitted], 'approved', $user, function () use ($user): void {
            $this->forceFill([
                'status' => PurchaseOrderStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ])->save();
        });
    }

    /**
     * Books every line into the order's warehouse. Receiving is all or nothing.
     */
    public function receive(User $user): void
    {
        $this->transition([PurchaseOrderStatus::Approved], 'received', $user, function () use ($user): void {
            foreach ($this->lines()->get() as $line) {
                StockMovement::query()->create([
                    'item_id' => $line->item_id,
                    'warehouse_id' => $this->warehouse_id,
                    'quantity' => $line->quantity,
                    'type' => StockMovementType::PurchaseReceipt,
                    'reference_type' => static::class,
                    'reference_id' => $this->id,
                    'note' => "Received on {$this->number}",
                    'user_id' => $user->id,
                ]);
            }

            $this->forceFill(['status' => PurchaseOrderStatus::Received, 'received_at' => now()])->save();
        });
    }

    public function cancel(User $user): void
    {
        $this->transition(
            [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Approved],
            'cancelled',
            $user,
            fn () => $this->forceFill(['status' => PurchaseOrderStatus::Cancelled, 'cancelled_at' => now()])->save(),
        );
    }

    /**
     * Locks the order row, re-reads its status, and only then applies the change,
     * so a double click or two users acting at once cannot, for example, receive
     * the same order twice.
     *
     * @param  array<int, PurchaseOrderStatus>  $allowedFrom
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

            activity('purchasing')->performedOn($this)->causedBy($user)->log("{$this->number} was {$pastTense}");
        });
    }
}
