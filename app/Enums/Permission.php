<?php

namespace App\Enums;

/**
 * Every permission the app checks. Permissions are defined in code (a permission
 * nothing checks would do nothing), while roles are edited at runtime.
 */
enum Permission: string
{
    case MasterDataView = 'master-data.view';
    case MasterDataManage = 'master-data.manage';
    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';
    case PurchasingView = 'purchasing.view';
    case PurchasingManage = 'purchasing.manage';
    case PurchasingApprove = 'purchasing.approve';
    case SalesView = 'sales.view';
    case SalesManage = 'sales.manage';

    public function module(): string
    {
        return match ($this) {
            self::MasterDataView, self::MasterDataManage => 'Master data',
            self::InventoryView, self::InventoryManage => 'Inventory',
            self::PurchasingView, self::PurchasingManage, self::PurchasingApprove => 'Purchasing',
            self::SalesView, self::SalesManage => 'Sales',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MasterDataView => 'See items, warehouses, customers and suppliers',
            self::MasterDataManage => 'Create and edit items, warehouses, customers and suppliers',
            self::InventoryView => 'See stock levels and stock cards',
            self::InventoryManage => 'Post stock adjustments',
            self::PurchasingView => 'See purchase orders',
            self::PurchasingManage => 'Create, submit, receive and cancel purchase orders',
            self::PurchasingApprove => 'Approve submitted purchase orders',
            self::SalesView => 'See sales orders',
            self::SalesManage => 'Create, confirm, deliver and cancel sales orders',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $permission) => $permission->value, self::cases());
    }

    /**
     * @return list<string>
     */
    public static function viewOnly(): array
    {
        return array_values(array_filter(self::values(), fn (string $value) => str_ends_with($value, '.view')));
    }
}
