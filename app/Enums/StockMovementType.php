<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseReceipt = 'purchase_receipt';
    case SalesDelivery = 'sales_delivery';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseReceipt => 'Purchase receipt',
            self::SalesDelivery => 'Sales delivery',
            self::Adjustment => 'Adjustment',
        };
    }
}
