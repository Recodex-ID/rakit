<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStock;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $unit
 * @property int $purchase_price
 * @property int $sale_price
 * @property int $minimum_stock
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sku', 'name', 'unit', 'purchase_price', 'sale_price', 'minimum_stock', 'description', 'is_active'])]
class Item extends Model implements HasMedia
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'integer',
            'sale_price' => 'integer',
            'minimum_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Adds an `on_hand` column: the ledger total, optionally for one warehouse.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeWithStockOnHand(Builder $query, ?int $warehouseId = null): Builder
    {
        return $query->withSum(
            ['stockMovements as on_hand' => fn (Builder $movements) => $movements->when(
                $warehouseId,
                fn (Builder $movements) => $movements->where('warehouse_id', $warehouseId),
            )],
            'quantity',
        );
    }

    /**
     * Items whose total stock, across all warehouses, is under their minimum.
     * Items with a minimum of 0 are never flagged.
     *
     * @param  Builder<Item>  $query
     * @return Builder<Item>
     */
    public function scopeBelowMinimum(Builder $query): Builder
    {
        return $query
            ->where('minimum_stock', '>', 0)
            ->whereRaw('(select coalesce(sum(quantity), 0) from stock_movements where stock_movements.item_id = items.id) < items.minimum_stock');
    }

    public function stockOnHand(?Warehouse $warehouse = null): int
    {
        return (int) $this->stockMovements()
            ->when($warehouse, fn (Builder $movements) => $movements->where('warehouse_id', $warehouse->id))
            ->sum('quantity');
    }

    /**
     * Posts a stock opname correction. Positive adds stock, negative removes it,
     * and a removal that would go below zero is refused.
     */
    public function adjustStock(Warehouse $warehouse, int $quantity, string $reason, User $user): StockMovement
    {
        if ($quantity === 0) {
            throw new InvalidArgumentException('An adjustment must change the quantity.');
        }

        return DB::transaction(function () use ($warehouse, $quantity, $reason, $user): StockMovement {
            static::lockForStockChange([$this->id]);

            $onHand = $this->stockOnHand($warehouse);

            if ($onHand + $quantity < 0) {
                throw InsufficientStock::for($this->name, $warehouse->name, $onHand, abs($quantity));
            }

            $movement = $this->stockMovements()->create([
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
                'type' => StockMovementType::Adjustment,
                'note' => $reason,
                'user_id' => $user->id,
            ]);

            activity('inventory')
                ->performedOn($this)
                ->causedBy($user)
                ->withProperties(['warehouse' => $warehouse->code, 'quantity' => $quantity, 'reason' => $reason])
                ->log(sprintf('Stock of %s in %s adjusted by %+d', $this->sku, $warehouse->code, $quantity));

            return $movement;
        });
    }

    /**
     * Row-locks the given items until the surrounding transaction ends, so two
     * requests cannot both read the same balance and then both take from it.
     *
     * @param  array<int, int>  $itemIds
     */
    public static function lockForStockChange(array $itemIds): void
    {
        static::query()->whereKey($itemIds)->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function isBelowMinimum(int $onHand): bool
    {
        return $this->minimum_stock > 0 && $onHand < $this->minimum_stock;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')->quality(80);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('master-data')
            ->setDescriptionForEvent(fn (string $eventName) => "Item {$this->sku} was {$eventName}");
    }
}
