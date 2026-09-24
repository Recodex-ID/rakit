<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

/**
 * Printable purchase and sales orders. Plain HTML with print styles: the
 * browser's "Save as PDF" covers the PDF case without a PDF library.
 * Access is checked by the route's can:*.view middleware.
 */
class PrintDocumentController extends Controller
{
    public function purchaseOrder(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['lines.item', 'supplier', 'warehouse', 'creator', 'approver']);

        return view('prints.order', [
            'order' => $purchaseOrder,
            'heading' => 'Purchase order',
            'partyLabel' => 'Supplier',
            'party' => $purchaseOrder->supplier,
            'warehouseLabel' => 'Deliver to',
            'company' => $this->company(),
        ]);
    }

    public function salesOrder(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['lines.item', 'customer', 'warehouse', 'creator']);

        return view('prints.order', [
            'order' => $salesOrder,
            'heading' => 'Sales order',
            'partyLabel' => 'Customer',
            'party' => $salesOrder->customer,
            'warehouseLabel' => 'Ship from',
            'company' => $this->company(),
        ]);
    }

    /**
     * @return array{name: string, address: ?string, phone: ?string, email: ?string, tax_id: ?string}
     */
    private function company(): array
    {
        return [
            'name' => Setting::get('company_name') ?: config('app.name'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::get('company_email'),
            'tax_id' => Setting::get('company_tax_id'),
        ];
    }
}
