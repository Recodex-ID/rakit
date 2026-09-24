<?php

namespace App\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Flux badge color for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Confirmed => 'sky',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }
}
