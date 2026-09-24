<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Documentation')] class extends Component
{
    /**
     * Quick-nav sections, in page order. Drives both the sticky quick-nav and each section's heading icon.
     *
     * @return array<int, array{id: string, label: string, icon: string}>
     */
    #[Computed]
    public function sections(): array
    {
        return [
            ['id' => 'getting-started', 'label' => 'Getting started', 'icon' => 'rocket-launch'],
            ['id' => 'permissions', 'label' => 'Roles & permissions', 'icon' => 'shield-check'],
            ['id' => 'master-data', 'label' => 'Master data', 'icon' => 'rectangle-stack'],
            ['id' => 'inventory', 'label' => 'Inventory', 'icon' => 'archive-box'],
            ['id' => 'purchasing', 'label' => 'Purchasing', 'icon' => 'truck'],
            ['id' => 'sales', 'label' => 'Sales', 'icon' => 'shopping-cart'],
            ['id' => 'documents', 'label' => 'Numbers, money & printing', 'icon' => 'printer'],
            ['id' => 'extending', 'label' => 'Adding a module', 'icon' => 'puzzle-piece'],
            ['id' => 'launch', 'label' => 'Going live', 'icon' => 'globe-alt'],
            ['id' => 'quality', 'label' => 'Tests & quality', 'icon' => 'beaker'],
        ];
    }
};
?>

@php($code = 'rounded bg-zinc-100 px-1.5 py-0.5 text-sm')

<div class="w-full space-y-8">
    <div>
        <flux:heading size="xl">Documentation</flux:heading>
        <flux:subheading>How {{ config('app.name') }} works, and where each rule lives in the code.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[272px_1fr] lg:items-start">
        <flux:card class="lg:sticky lg:top-20">
            <flux:text size="sm" class="mb-3 font-semibold tracking-wide text-zinc-500 uppercase">On this page</flux:text>
            <nav class="flex flex-col gap-1">
                @foreach ($this->sections() as $section)
                    <flux:link
                        href="#{{ $section['id'] }}"
                        variant="ghost"
                        class="rounded-lg px-2 py-1.5 text-sm text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900"
                    >
                        <span class="flex items-center gap-2.5">
                            <flux:icon :name="$section['icon']" class="size-4 text-zinc-500" />
                            {{ $section['label'] }}
                        </span>
                    </flux:link>
                @endforeach
            </nav>
        </flux:card>

        <div class="space-y-6">
            <section id="getting-started" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="rocket-launch" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Getting started</flux:heading>
                    </div>
                    <flux:text>
                        <code class="{{ $code }}">php artisan migrate --seed</code> creates three accounts (password
                        <code class="{{ $code }}">password</code>) plus a small demo company: two warehouses, eight items, suppliers,
                        customers, and orders in every status so each screen has something to show.
                    </flux:text>
                    <div class="overflow-hidden rounded-lg border border-zinc-200">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 text-left">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Email</th>
                                    <th class="px-4 py-2 font-medium">Role</th>
                                    <th class="px-4 py-2 font-medium">Can do</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200">
                                <tr>
                                    <td class="px-4 py-2 font-mono">super-admin@mail.test</td>
                                    <td class="px-4 py-2"><flux:badge size="sm" color="amber">super-admin</flux:badge></td>
                                    <td class="px-4 py-2">Everything, plus roles and the activity log</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">admin@mail.test</td>
                                    <td class="px-4 py-2"><flux:badge size="sm">admin</flux:badge></td>
                                    <td class="px-4 py-2">Every module, users, settings, media</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-mono">staff@mail.test</td>
                                    <td class="px-4 py-2"><flux:badge size="sm" color="zinc">staff</flux:badge></td>
                                    <td class="px-4 py-2">Read-only access to every module</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <flux:text>
                        There is no public site and no self sign-up: <code class="{{ $code }}">/</code> goes straight to the dashboard,
                        every page is <code class="{{ $code }}">noindex</code>, and <code class="{{ $code }}">/robots.txt</code> disallows
                        everything. Admins create accounts from <flux:link :href="route('system.users')">Users</flux:link>. Password reset and
                        email verification still run on Laravel Fortify.
                    </flux:text>
                </flux:card>
            </section>

            <section id="permissions" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="shield-check" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Roles &amp; permissions</flux:heading>
                    </div>
                    <flux:text>
                        Permissions are defined in code, in <code class="{{ $code }}">App\Enums\Permission</code>, because a permission that no
                        code checks does nothing. Roles are data: a super admin edits them on
                        <flux:link :href="route('super-admin.roles')">Roles &amp; permissions</flux:link>, can add new ones (for example a
                        warehouse clerk with only inventory access) and can delete any role except the three built-in ones.
                    </flux:text>
                    <flux:text>
                        Checks happen in three places: route middleware (<code class="{{ $code }}">can:purchasing.view</code> in
                        <code class="{{ $code }}">routes/app.php</code>), <code class="{{ $code }}">Gate::authorize()</code> inside every
                        Livewire action that changes data, and <code class="{{ $code }}">@@can</code> in Blade to hide buttons. Hiding a button is
                        never the only guard. <code class="{{ $code }}">super-admin</code> passes every check through
                        <code class="{{ $code }}">Gate::before</code> in <code class="{{ $code }}">AppServiceProvider</code>.
                    </flux:text>
                </flux:card>
            </section>

            <section id="master-data" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="rectangle-stack" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Master data</flux:heading>
                    </div>
                    <flux:text>
                        Items, warehouses, customers and suppliers. Each has a unique code and an active switch. Anything already used on a
                        document or in the stock ledger cannot be deleted, only deactivated, so old orders keep pointing at real records.
                        Inactive records disappear from the order forms but stay visible on past documents. Items carry a purchase price, a
                        sale price (both prefilled on order lines), a minimum stock level for the low-stock alert, and an optional photo stored
                        through the media library.
                    </flux:text>
                </flux:card>
            </section>

            <section id="inventory" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="archive-box" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Inventory</flux:heading>
                    </div>
                    <flux:text>
                        Stock is a ledger. There is no quantity column to overwrite: every change is a row in
                        <code class="{{ $code }}">stock_movements</code> with a signed quantity, a type (purchase receipt, sales delivery,
                        adjustment), the document that caused it and the user. On hand is the sum of those rows, per warehouse or overall.
                    </flux:text>
                    <flux:text>
                        <flux:link :href="route('inventory.stock')">Stock</flux:link> lists on-hand quantities and posts adjustments after a
                        stock count; an adjustment needs a reason and can never take a warehouse below zero. Each item's stock card shows every
                        movement with a running balance and links back to its purchase or sales order.
                    </flux:text>
                    <flux:text>
                        Writes lock the affected item rows (<code class="{{ $code }}">Item::lockForStockChange()</code>) inside a transaction,
                        so two people delivering the last unit at the same moment cannot both succeed.
                    </flux:text>
                </flux:card>
            </section>

            <section id="purchasing" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="truck" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Purchasing</flux:heading>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <flux:badge size="sm" color="zinc">Draft</flux:badge> <flux:icon.arrow-right class="size-4 text-zinc-500" />
                        <flux:badge size="sm" color="amber">Submitted</flux:badge> <flux:icon.arrow-right class="size-4 text-zinc-500" />
                        <flux:badge size="sm" color="sky">Approved</flux:badge> <flux:icon.arrow-right class="size-4 text-zinc-500" />
                        <flux:badge size="sm" color="green">Received</flux:badge>
                    </div>
                    <flux:text>
                        Only drafts can be edited. Submitting needs <code class="{{ $code }}">purchasing.manage</code>, approving needs the
                        separate <code class="{{ $code }}">purchasing.approve</code>, so the person who orders and the person who approves can
                        be different. Receiving books every line into the order's warehouse in one transaction. Anything before Received can
                        be cancelled. Each move lives on the model (<code class="{{ $code }}">PurchaseOrder::submit()</code>,
                        <code class="{{ $code }}">approve()</code>, <code class="{{ $code }}">receive()</code>,
                        <code class="{{ $code }}">cancel()</code>), which locks the order and re-checks its status, so a double click cannot
                        receive the same goods twice.
                    </flux:text>
                </flux:card>
            </section>

            <section id="sales" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="shopping-cart" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Sales</flux:heading>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <flux:badge size="sm" color="zinc">Draft</flux:badge> <flux:icon.arrow-right class="size-4 text-zinc-500" />
                        <flux:badge size="sm" color="sky">Confirmed</flux:badge> <flux:icon.arrow-right class="size-4 text-zinc-500" />
                        <flux:badge size="sm" color="green">Delivered</flux:badge>
                    </div>
                    <flux:text>
                        Delivery is all or nothing. Before writing anything, <code class="{{ $code }}">SalesOrder::deliver()</code> adds up
                        the quantity per item (the same item on two lines counts once), checks on-hand stock in the ship-from warehouse, and
                        refuses the whole delivery with a message naming the short item if any line cannot be covered. The order page shows
                        the available quantity next to each line so a shortage is visible before anyone presses Deliver.
                    </flux:text>
                </flux:card>
            </section>

            <section id="documents" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="printer" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Numbers, money &amp; printing</flux:heading>
                    </div>
                    <ul class="list-disc space-y-2 pl-6 text-sm text-zinc-700">
                        <li>
                            <strong>Numbers.</strong> <code class="{{ $code }}">PO-2026-0001</code> and <code class="{{ $code }}">SO-2026-0001</code>
                            come from <code class="{{ $code }}">DocumentSequence::next()</code>, a locked counter per prefix and year. They never
                            repeat and restart at 0001 each January.
                        </li>
                        <li>
                            <strong>Money.</strong> Stored as whole Rupiah in integer columns, never floats, and shown with
                            <code class="{{ $code }}">App\Support\Money::format()</code> (for example Rp 1.250.000).
                        </li>
                        <li>
                            <strong>Printing.</strong> Every order has a Print button that opens a plain A4 page
                            (<code class="{{ $code }}">resources/views/prints/order.blade.php</code>) with the company details from
                            <flux:link :href="route('system.settings')">Settings</flux:link>. Use the browser's "Save as PDF" when you need a file;
                            no PDF library is installed.
                        </li>
                        <li>
                            <strong>Audit trail.</strong> Master data edits, status changes, role changes and settings all land in the
                            <flux:link :href="route('super-admin.activity')">activity log</flux:link> with the user who made them.
                        </li>
                    </ul>
                </flux:card>
            </section>

            <section id="extending" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="puzzle-piece" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Adding a module</flux:heading>
                    </div>
                    <flux:text>The three modules follow the same shape, so a new one (say, invoices) is mostly copying:</flux:text>
                    <ol class="list-decimal space-y-2 pl-6 text-sm text-zinc-700">
                        <li>Add cases to <code class="{{ $code }}">App\Enums\Permission</code> and a migration that creates them with <code class="{{ $code }}">Permission::findOrCreate()</code>.</li>
                        <li>Put the rules on the model: status enum, transition methods that lock and re-check, activity log entries.</li>
                        <li>Write Pest tests for those methods before any screen.</li>
                        <li>Add Livewire pages under <code class="{{ $code }}">resources/views/pages/app/</code> and routes in <code class="{{ $code }}">routes/app.php</code> behind <code class="{{ $code }}">can:</code> middleware.</li>
                        <li>Add the sidebar entry inside an <code class="{{ $code }}">@@can</code> block in <code class="{{ $code }}">layouts/app.blade.php</code>.</li>
                    </ol>
                    <flux:text>
                        Deliberately left out, so each project can decide: accounting and journals, invoices and payments, tax (PPN),
                        multiple currencies, partial receipts and deliveries, and transfers between warehouses.
                    </flux:text>
                </flux:card>
            </section>

            <section id="launch" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="globe-alt" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Going live</flux:heading>
                    </div>
                    <ul class="list-disc space-y-2 pl-6 text-sm text-zinc-700">
                        <li>Replace the demo data: run <code class="{{ $code }}">php artisan migrate --seed --seeder=UserSeeder</code> on a fresh database, then change every seeded password.</li>
                        <li>Fill in the company name, address and tax ID in <flux:link :href="route('system.settings')">Settings</flux:link>; they appear on every printed order.</li>
                        <li>
                            With <code class="{{ $code }}">FORCE_HTTPS</code> on (the production default) http is redirected, HSTS is sent and the
                            session cookie is secure. Set <code class="{{ $code }}">TRUSTED_PROXIES</code> when TLS ends at a proxy.
                        </li>
                        <li>
                            Point cron at <code class="{{ $code }}">php artisan schedule:run</code>. It prunes activity log entries older than
                            {{ config('activitylog.clean_after_days') }} days.
                        </li>
                        <li>The forgot-password form has a honeypot field and a limit of five requests per minute per address; sign-in has Fortify's own throttle.</li>
                    </ul>
                </flux:card>
            </section>

            <section id="quality" class="scroll-mt-24">
                <flux:card class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <flux:icon name="beaker" class="size-5 text-zinc-500" />
                        <flux:heading size="lg">Tests &amp; quality</flux:heading>
                    </div>
                    <flux:text>
                        The stock, purchasing and sales rules are covered by Pest tests in
                        <code class="{{ $code }}">tests/Feature/Inventory</code>, <code class="{{ $code }}">Purchasing</code> and
                        <code class="{{ $code }}">Sales</code>. Every change should pass all three:
                    </flux:text>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="rounded-lg bg-zinc-900 px-4 py-2.5 text-zinc-100">php artisan test --compact</div>
                        <div class="rounded-lg bg-zinc-900 px-4 py-2.5 text-zinc-100">vendor/bin/pint --dirty</div>
                        <div class="rounded-lg bg-zinc-900 px-4 py-2.5 text-zinc-100">vendor/bin/phpstan analyse</div>
                    </div>
                </flux:card>
            </section>
        </div>
    </div>
</div>
