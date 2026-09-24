<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * A small, believable data set so every screen has something to show after
 * `migrate --seed`. Orders go through the real workflow methods, so the stock
 * ledger is exactly what the app itself would have produced.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@mail.test')->firstOrFail();

        $main = Warehouse::create(['code' => 'WH-JKT', 'name' => 'Jakarta main warehouse', 'address' => 'Jl. Industri Raya No. 12, Jakarta Utara', 'is_active' => true]);
        $branch = Warehouse::create(['code' => 'WH-SBY', 'name' => 'Surabaya branch', 'address' => 'Jl. Rungkut Industri No. 5, Surabaya', 'is_active' => true]);

        $items = collect([
            ['ATK-001', 'A4 copy paper 80gsm', 'ream', 42000, 55000, 50],
            ['ATK-002', 'Ballpoint pen, black', 'box', 18000, 26000, 20],
            ['ATK-003', 'Stapler No. 10', 'pcs', 15000, 24000, 10],
            ['ELK-001', 'USB flash drive 32 GB', 'pcs', 55000, 79000, 15],
            ['ELK-002', 'Wireless mouse', 'pcs', 85000, 125000, 10],
            ['ELK-003', 'HDMI cable 2 m', 'pcs', 35000, 55000, 10],
            ['KBR-001', 'Cleaning cloth', 'pack', 12000, 20000, 0],
            ['KBR-002', 'Hand soap refill 500 ml', 'bottle', 22000, 32000, 12],
        ])->map(fn (array $row) => Item::create([
            'sku' => $row[0],
            'name' => $row[1],
            'unit' => $row[2],
            'purchase_price' => $row[3],
            'sale_price' => $row[4],
            'minimum_stock' => $row[5],
            'is_active' => true,
        ]))->keyBy('sku');

        $suppliers = collect([
            ['SUP-0001', 'CV Sumber Kertas', 'sales@sumberkertas.test', '021 555 0101'],
            ['SUP-0002', 'PT Elektronik Nusantara', 'order@eln.test', '021 555 0202'],
            ['SUP-0003', 'UD Bersih Selalu', 'admin@bersihselalu.test', '031 555 0303'],
        ])->map(fn (array $row) => Supplier::create(['code' => $row[0], 'name' => $row[1], 'email' => $row[2], 'phone' => $row[3], 'is_active' => true]));

        $customers = collect([
            ['CUS-0001', 'PT Maju Bersama', 'purchasing@majubersama.test', '021 777 1001'],
            ['CUS-0002', 'Sekolah Harapan Bangsa', 'tu@harapanbangsa.test', '021 777 1002'],
            ['CUS-0003', 'Klinik Sehat Sentosa', 'admin@sehatsentosa.test', '031 777 1003'],
            ['CUS-0004', 'Koperasi Karyawan Sejahtera', 'kopkar@sejahtera.test', '031 777 1004'],
        ])->map(fn (array $row) => Customer::create(['code' => $row[0], 'name' => $row[1], 'email' => $row[2], 'phone' => $row[3], 'is_active' => true]));

        // Received purchase orders put the opening stock in place.
        $this->purchase($suppliers[0], $main, $admin, [[$items['ATK-001'], 120], [$items['ATK-002'], 40], [$items['ATK-003'], 25]], received: true);
        $this->purchase($suppliers[1], $main, $admin, [[$items['ELK-001'], 30], [$items['ELK-002'], 12], [$items['ELK-003'], 20]], received: true);
        $this->purchase($suppliers[2], $branch, $admin, [[$items['KBR-001'], 60], [$items['KBR-002'], 8]], received: true);

        // One waiting for approval, so the dashboard has something to act on.
        $this->purchase($suppliers[1], $main, $admin, [[$items['ELK-002'], 20]], received: false);

        // Delivered sales spread over the last days, for the dashboard chart.
        foreach ([[9, $customers[0], [[$items['ATK-001'], 30], [$items['ATK-002'], 10]]], [5, $customers[1], [[$items['ATK-001'], 25], [$items['ELK-001'], 6]]], [2, $customers[0], [[$items['ELK-002'], 4], [$items['ELK-003'], 5]]]] as [$daysAgo, $customer, $lines]) {
            $order = $this->sale($customer, $main, $admin, $lines);
            $order->confirm($admin);
            $order->deliver($admin);
            $order->forceFill(['order_date' => now()->subDays($daysAgo), 'delivered_at' => now()->subDays($daysAgo)])->saveQuietly();
        }

        $this->sale($customers[2], $branch, $admin, [[$items['KBR-001'], 10], [$items['KBR-002'], 3]])->confirm($admin);
        $this->sale($customers[3], $main, $admin, [[$items['ATK-003'], 5]]);
    }

    /**
     * @param  array<int, array{0: Item, 1: int}>  $lines
     */
    private function purchase(Supplier $supplier, Warehouse $warehouse, User $user, array $lines, bool $received): PurchaseOrder
    {
        $order = PurchaseOrder::create(['supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'order_date' => now()->toDateString(), 'created_by' => $user->id]);

        foreach ($lines as [$item, $quantity]) {
            $order->lines()->create(['item_id' => $item->id, 'quantity' => $quantity, 'unit_price' => $item->purchase_price]);
        }

        $order->submit($user);

        if ($received) {
            $order->approve($user);
            $order->receive($user);
        }

        return $order;
    }

    /**
     * @param  array<int, array{0: Item, 1: int}>  $lines
     */
    private function sale(Customer $customer, Warehouse $warehouse, User $user, array $lines): SalesOrder
    {
        $order = SalesOrder::create(['customer_id' => $customer->id, 'warehouse_id' => $warehouse->id, 'order_date' => now()->toDateString(), 'created_by' => $user->id]);

        foreach ($lines as [$item, $quantity]) {
            $order->lines()->create(['item_id' => $item->id, 'quantity' => $quantity, 'unit_price' => $item->sale_price]);
        }

        return $order;
    }
}
