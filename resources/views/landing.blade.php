@php
    $repositoryUrl = 'https://github.com/Recodex-ID/rakit';

    $sections = [
        'ledger' => 'Stock ledger',
        'orders' => 'Order flow',
        'modules' => 'Modules',
        'start' => 'Get started',
    ];

    $ledgerRules = [
        'Stock is a ledger of signed movements, never a number someone can overwrite.',
        'Receiving a purchase order books every line into the warehouse in one transaction.',
        'Delivery is all or nothing: if any line is short in the ship-from warehouse, nothing moves and the message names the item.',
        'Adjustments after a stock count need a reason and can never push stock below zero.',
        'Each item has a stock card with a running balance, so every unit traces back to its document.',
    ];

    $modules = [
        'Master data' => 'Items (SKU, unit, purchase and sale price, minimum stock, photo), warehouses, customers and suppliers. Anything already used on a document can only be deactivated, never deleted.',
        'Inventory' => 'Stock levels per warehouse, a low-stock filter, a stock card per item and adjustments after a stock count.',
        'Documents' => 'PO and SO numbering from a locked counter, amounts stored as whole Rupiah integers, and a print view per order. The browser\'s "Save as PDF" covers the PDF case.',
        'Roles & permissions' => 'Permissions are defined in code, roles are edited in the app by a super admin. Admin gets every module, staff is read-only, and you can add roles like "warehouse clerk".',
        'Dashboard' => 'Sales and purchases this month, orders waiting for approval or delivery, low-stock items and a 14-day chart of delivered sales. Each card only shows if the user has access to that module.',
        'Back office' => 'User management, company details for printed documents, a media library and an activity log of who changed what.',
        'Private by default' => 'The app sits behind the login, every page is noindex and robots.txt disallows everything. Security headers and HTTPS enforcement are on in production.',
    ];

    $leftOut = ['Accounting', 'Invoices and payments', 'Tax (PPN)', 'Multiple currencies', 'Partial receipts and deliveries', 'Transfers between warehouses'];

    $stack = [
        'PHP' => '8.4',
        'Laravel' => '13',
        'Auth' => 'Fortify: login, password reset, email verification, no self sign-up',
        'Frontend' => 'Livewire 4 + Flux UI',
        'Styling' => 'Tailwind CSS v4',
        'Roles' => 'Spatie Permission',
        'Audit' => 'Spatie Activitylog',
        'Media' => 'Spatie Media Library',
        'Quality' => 'Pest 4, Pint, Larastan',
    ];

    $demoAccounts = [
        ['super-admin@mail.test', 'super-admin', 'Everything, plus roles and the activity log'],
        ['admin@mail.test', 'admin', 'Every module, users, settings, media'],
        ['staff@mail.test', 'staff', 'Read-only'],
    ];

    $customisationSteps = [
        'Set <code>APP_NAME</code> and the rest of <code>.env</code>.',
        'Fill in the company name, address, phone, email and tax ID under System &gt; Settings. They are printed on every order.',
        'Swap the palette in <code>resources/css/app.css</code> (<code>--color-brand-*</code>), the logo in <code>public/images/logo.png</code> and the <code>public/favicon*</code> files.',
        'Adjust roles under Super Admin &gt; Roles &amp; permissions, or add permissions for a new module in <code>App\Enums\Permission</code>.',
        'Update the <code>name</code> and <code>description</code> in <code>composer.json</code> if you rename the repo.',
    ];

    $linkFocus = 'rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-orange';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => 'Laravel ERP starter kit'])
    </head>
    <body class="min-h-screen bg-brand-snow text-brand-ink antialiased">
        <header class="bg-brand-ink text-brand-snow">
            <div class="mx-auto flex min-h-svh max-w-6xl flex-col px-4 sm:px-8">
                <nav class="flex items-center justify-between gap-4 py-5" aria-label="Main">
                    <a href="{{ route('landing') }}" class="flex items-center gap-3 py-1 {{ $linkFocus }}">
                        <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36" class="size-9">
                        <span class="font-display text-lg font-bold">{{ config('app.name') }}</span>
                    </a>

                    <div class="flex items-center gap-2 text-sm sm:gap-6">
                        <a href="{{ $repositoryUrl }}" class="hidden px-2 py-3 text-brand-mist hover:text-brand-snow sm:block {{ $linkFocus }}">GitHub</a>
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-md border border-brand-graphite px-4 py-3 font-medium hover:border-brand-mist {{ $linkFocus }}">Open dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md border border-brand-graphite px-4 py-3 font-medium hover:border-brand-mist {{ $linkFocus }}">Sign in</a>
                        @endauth
                    </div>
                </nav>

                <div class="grid flex-1 content-center gap-12 pt-10 pb-16 lg:grid-cols-12 lg:items-center lg:gap-16 lg:pt-16 lg:pb-24">
                    <div class="lg:col-span-7">
                        <h1 class="font-display text-4xl leading-[1.1] font-bold sm:text-5xl">
                            A Laravel ERP starter kit that gets stock right.
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-relaxed text-brand-mist">
                            Inventory, purchasing and sales sit on one stock ledger, so every unit in a warehouse traces back to the purchase order that brought it in or the sales order that took it out. Clone it, rename it, and build the rest of your system on top.
                        </p>

                        <div class="mt-8 max-w-xl rounded-md border border-brand-graphite bg-black/30 px-4 py-3">
                            <code class="block overflow-x-auto font-mono text-sm whitespace-nowrap">laravel new my-erp --using=recodex-id/rakit</code>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ $repositoryUrl }}" class="inline-flex min-h-11 items-center rounded-md bg-brand-orange px-5 font-medium text-brand-ink hover:bg-brand-snow {{ $linkFocus }}">View the source on GitHub</a>
                            <a href="#start" class="inline-flex min-h-11 items-center rounded-md border border-brand-graphite px-5 font-medium hover:border-brand-mist {{ $linkFocus }}">Read the setup steps</a>
                        </div>
                    </div>

                    <div class="lg:col-span-5 lg:pt-3">
                        <x-stock-ledger />
                        <p class="mt-4 text-sm text-brand-mist">
                            The three ways stock can change. There is no fourth.
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <nav class="sticky top-0 z-10 border-b border-zinc-200 bg-brand-snow/95 backdrop-blur" aria-label="Sections">
            <ul class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 text-sm sm:px-8">
                @foreach ($sections as $anchor => $label)
                    <li>
                        <a href="#{{ $anchor }}" class="block px-3 py-3.5 whitespace-nowrap text-zinc-600 hover:text-brand-ink {{ $linkFocus }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <main class="mx-auto max-w-6xl px-4 sm:px-8">
            <section id="ledger" class="grid scroll-mt-16 gap-8 py-16 lg:grid-cols-12 lg:py-20">
                <div class="lg:col-span-4">
                    <h2 class="font-display text-2xl font-bold">Stock ledger</h2>
                    <p class="mt-3 text-zinc-600">The rules the stock, purchasing and sales modules enforce, each with its own tests.</p>
                </div>
                <ol class="divide-y divide-zinc-200 border-y border-zinc-200 lg:col-span-8">
                    @foreach ($ledgerRules as $rule)
                        <li class="flex gap-5 py-4">
                            <span class="w-6 shrink-0 font-mono text-sm text-brand-orange-dark tabular-nums">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span>{{ $rule }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section id="orders" class="grid scroll-mt-16 gap-8 border-t border-zinc-200 py-16 lg:grid-cols-12 lg:py-20">
                <div class="lg:col-span-4">
                    <h2 class="font-display text-2xl font-bold">Order flow</h2>
                    <p class="mt-3 text-zinc-600">Status colours mean the same thing on every screen: grey is a draft, amber waits for someone, blue is approved, green is done.</p>
                </div>
                <div class="space-y-10 lg:col-span-8">
                    <div>
                        <h3 class="font-medium">Purchasing <span class="ms-2 font-mono text-sm text-zinc-600">PO-{{ now()->year }}-0001</span></h3>
                        <ol class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-3">
                            @foreach (App\Enums\PurchaseOrderStatus::cases() as $status)
                                @continue($status === App\Enums\PurchaseOrderStatus::Cancelled)
                                <li class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
                                    @unless ($loop->remaining === 1)
                                        <span class="text-zinc-400" aria-hidden="true">/</span>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-4 text-zinc-600">Approval is its own permission, so ordering and approving can be split between people. Receiving books every line into stock at once.</p>
                    </div>

                    <div>
                        <h3 class="font-medium">Sales <span class="ms-2 font-mono text-sm text-zinc-600">SO-{{ now()->year }}-0001</span></h3>
                        <ol class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-3">
                            @foreach (App\Enums\SalesOrderStatus::cases() as $status)
                                @continue($status === App\Enums\SalesOrderStatus::Cancelled)
                                <li class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
                                    @unless ($loop->remaining === 1)
                                        <span class="text-zinc-400" aria-hidden="true">/</span>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-4 text-zinc-600">Delivery checks every line against the ship-from warehouse before anything moves. Either order can be cancelled before it touches stock.</p>
                    </div>
                </div>
            </section>

            <section id="modules" class="grid scroll-mt-16 gap-8 border-t border-zinc-200 py-16 lg:grid-cols-12 lg:py-20">
                <div class="lg:col-span-4">
                    <h2 class="font-display text-2xl font-bold">Modules</h2>
                    <p class="mt-3 text-zinc-600">What ships in the box.</p>
                </div>
                <div class="lg:col-span-8">
                    <dl class="grid gap-x-10 gap-y-8 sm:grid-cols-2">
                        @foreach ($modules as $name => $description)
                            <div>
                                <dt class="font-medium">{{ $name }}</dt>
                                <dd class="mt-1.5 text-zinc-600">{{ $description }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-12 rounded-md bg-zinc-100 p-6">
                        <h3 class="font-medium">Left out on purpose</h3>
                        <p class="mt-1.5 text-zinc-600">Each project decides these for itself. The in-app documentation shows how to add a module in the same shape.</p>
                        <ul class="mt-4 flex flex-wrap gap-2 text-sm">
                            @foreach ($leftOut as $feature)
                                <li class="rounded-sm border border-zinc-300 bg-brand-snow px-2.5 py-1">{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>

            <section class="grid gap-8 border-t border-zinc-200 py-16 lg:grid-cols-12 lg:py-20">
                <div class="lg:col-span-4">
                    <h2 class="font-display text-2xl font-bold">Stack</h2>
                    <p class="mt-3 text-zinc-600">Rakit is the sister project of <a href="https://github.com/Recodex-ID/rewire" class="text-brand-orange-dark underline underline-offset-2 {{ $linkFocus }}">Rewire</a>: same stack and conventions, aimed at internal systems instead of landing pages.</p>
                </div>
                <div class="lg:col-span-8">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-zinc-200 border-y border-zinc-200">
                            @foreach ($stack as $layer => $choice)
                                <tr>
                                    <th scope="row" class="w-32 py-3 pe-4 align-top font-medium">{{ $layer }}</th>
                                    <td class="py-3 text-zinc-600">{{ $choice }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="start" class="grid scroll-mt-16 gap-8 border-t border-zinc-200 py-16 lg:grid-cols-12 lg:py-20">
                <div class="lg:col-span-4">
                    <h2 class="font-display text-2xl font-bold">Get started</h2>
                    <p class="mt-3 text-zinc-600">Uses SQLite by default, so there is no database server to set up first.</p>
                </div>
                <div class="min-w-0 space-y-10 lg:col-span-8">
                    <div>
                        <h3 class="font-medium">Clone and run</h3>
                        <pre class="mt-4 overflow-x-auto rounded-md bg-brand-ink p-5 font-mono text-sm leading-relaxed text-brand-mist"><code>git clone {{ $repositoryUrl }}.git
cd rakit
composer install
npm install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
composer run dev</code></pre>
                        <p class="mt-3 text-zinc-600"><code class="font-mono text-sm">composer run dev</code> runs the app server, queue listener and Vite together. The app is then at <code class="font-mono text-sm">http://localhost:8000</code>.</p>
                    </div>

                    <div>
                        <h3 class="font-medium">Demo accounts</h3>
                        <p class="mt-1.5 text-zinc-600">The seeder creates a small demo company (two warehouses, eight items, suppliers, customers and orders in every status) and these accounts, all with the password <code class="font-mono text-sm">password</code>. For a real deployment seed only the accounts with <code class="font-mono text-sm">--seeder=UserSeeder</code> and change the passwords.</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[32rem] text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-300 text-zinc-600">
                                        <th class="py-2 pe-4 font-medium">Email</th>
                                        <th class="py-2 pe-4 font-medium">Role</th>
                                        <th class="py-2 font-medium">Access</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 border-b border-zinc-200">
                                    @foreach ($demoAccounts as [$email, $role, $access])
                                        <tr>
                                            <td class="py-3 pe-4 font-mono">{{ $email }}</td>
                                            <td class="py-3 pe-4">{{ $role }}</td>
                                            <td class="py-3 text-zinc-600">{{ $access }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium">Make it yours</h3>
                        <ol class="mt-4 space-y-3">
                            @foreach ($customisationSteps as $step)
                                <li class="flex gap-4">
                                    <span class="w-6 shrink-0 font-mono text-sm leading-6 text-brand-orange-dark tabular-nums">{{ $loop->iteration }}.</span>
                                    <span class="text-zinc-600 [&_code]:font-mono [&_code]:text-sm [&_code]:text-brand-ink">{!! $step !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <div>
                        <h3 class="font-medium">Quality checks</h3>
                        <pre class="mt-4 overflow-x-auto rounded-md bg-brand-ink p-5 font-mono text-sm leading-relaxed text-brand-mist"><code>composer test</code></pre>
                        <p class="mt-3 text-zinc-600">Runs Pest, Pint and Larastan, the same as CI on every push.</p>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-zinc-200">
            <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-6 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p>{{ config('app.name') }} is open source under the MIT license.</p>
                <ul class="flex gap-5">
                    <li><a href="{{ $repositoryUrl }}" class="block py-3 hover:text-brand-ink {{ $linkFocus }}">Source</a></li>
                    <li><a href="{{ $repositoryUrl }}/releases" class="block py-3 hover:text-brand-ink {{ $linkFocus }}">Releases</a></li>
                </ul>
            </div>
        </footer>
    </body>
</html>
