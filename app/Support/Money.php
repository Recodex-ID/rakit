<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Amounts are stored as whole Rupiah integers; this is the one place that
 * turns them into text, so a fork that needs another currency changes one line.
 */
class Money
{
    public static function format(int $amount): string
    {
        return Number::currency($amount, in: 'IDR', locale: 'id', precision: 0) ?: 'Rp '.number_format($amount, 0, ',', '.');
    }
}
