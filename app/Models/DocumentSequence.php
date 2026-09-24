<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $prefix
 * @property int $year
 * @property int $last_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['prefix', 'year', 'last_number'])]
class DocumentSequence extends Model
{
    /**
     * Hands out the next document number, e.g. "PO-2026-0001". The counter row is
     * locked while it is incremented, so two users saving at the same moment still
     * get different numbers. Numbering restarts every calendar year.
     */
    public static function next(string $prefix, ?CarbonInterface $date = null): string
    {
        $year = ($date ?? now())->year;

        return DB::transaction(function () use ($prefix, $year): string {
            $sequence = static::query()->where('prefix', $prefix)->where('year', $year)->lockForUpdate()->first()
                ?? static::query()->create(['prefix' => $prefix, 'year' => $year, 'last_number' => 0]);

            $sequence->increment('last_number');

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence->last_number);
        });
    }
}
