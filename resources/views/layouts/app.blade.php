<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50">

        <flux:sidebar sticky collapsible="mobile" class="dark border-e border-brand-ink/50 bg-brand-ink">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3 px-2 py-1">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt=""
                        width="44"
                        height="44"
                        class="size-11 shrink-0 object-contain"
                    >
                    <span class="min-w-0 truncate text-lg font-bold text-white">{{ config('app.name') }}</span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    Dashboard
                </flux:sidebar.item>

                @can('sales.view')
                    <flux:sidebar.group heading="Sales" class="grid">
                        <flux:sidebar.item icon="shopping-cart" :href="route('sales.orders')" :current="request()->routeIs('sales.orders*')" wire:navigate>
                            Sales orders
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('purchasing.view')
                    <flux:sidebar.group heading="Purchasing" class="grid">
                        <flux:sidebar.item icon="truck" :href="route('purchasing.orders')" :current="request()->routeIs('purchasing.orders*')" wire:navigate>
                            Purchase orders
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('inventory.view')
                    <flux:sidebar.group heading="Inventory" class="grid">
                        <flux:sidebar.item icon="archive-box" :href="route('inventory.stock')" :current="request()->routeIs('inventory.*')" wire:navigate>
                            Stock
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('master-data.view')
                    <flux:sidebar.group heading="Master data" class="grid">
                        <flux:sidebar.item icon="cube" :href="route('master-data.items')" :current="request()->routeIs('master-data.items')" wire:navigate>
                            Items
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-storefront" :href="route('master-data.warehouses')" :current="request()->routeIs('master-data.warehouses')" wire:navigate>
                            Warehouses
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-group" :href="route('master-data.customers')" :current="request()->routeIs('master-data.customers')" wire:navigate>
                            Customers
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-office" :href="route('master-data.suppliers')" :current="request()->routeIs('master-data.suppliers')" wire:navigate>
                            Suppliers
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @role('super-admin|admin')
                    <flux:sidebar.group heading="System" class="grid">
                        <flux:sidebar.item icon="users" :href="route('system.users')" :current="request()->routeIs('system.users')" wire:navigate>
                            Users
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="photo" :href="route('system.media-library')" :current="request()->routeIs('system.media-library')" wire:navigate>
                            Media Library
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('system.settings')" :current="request()->routeIs('system.settings')" wire:navigate>
                            Settings
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endrole

                <flux:sidebar.group heading="Other" class="grid">
                    <flux:sidebar.item icon="folder-git-2" href="https://github.com/Recodex-ID/rakit" target="_blank" rel="noopener">
                        Repository
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('other.docs')" :current="request()->routeIs('other.docs')" wire:navigate>
                        Documentation
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            @role('super-admin')
                <flux:sidebar.nav>
                    <flux:sidebar.group heading="Super Admin" class="grid">
                        <flux:sidebar.item icon="key" :href="route('super-admin.roles')" :current="request()->routeIs('super-admin.roles')" wire:navigate>
                            Roles &amp; permissions
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('super-admin.activity')" :current="request()->routeIs('super-admin.activity')" wire:navigate>
                            Activity log
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endrole

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Settings
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Desktop Topbar -->
        @php
            $breadcrumbGroup = match (true) {
                str_starts_with(request()->route()?->getName() ?? '', 'system.') => 'System',
                str_starts_with(request()->route()?->getName() ?? '', 'super-admin.') => 'Super Admin',
                str_starts_with(request()->route()?->getName() ?? '', 'master-data.') => 'Master data',
                str_starts_with(request()->route()?->getName() ?? '', 'inventory.') => 'Inventory',
                str_starts_with(request()->route()?->getName() ?? '', 'purchasing.') => 'Purchasing',
                str_starts_with(request()->route()?->getName() ?? '', 'sales.') => 'Sales',
                str_starts_with(request()->route()?->getName() ?? '', 'other.') => 'Other',
                default => null,
            };
        @endphp
        <flux:header sticky class="hidden border-b border-zinc-200 bg-white lg:flex">
            <div>
                <flux:breadcrumbs>
                    @unless (request()->routeIs('dashboard'))
                        <flux:breadcrumbs.item icon="home" :href="route('dashboard')" wire:navigate />
                    @endunless
                    @if ($breadcrumbGroup)
                        <flux:breadcrumbs.item>{{ $breadcrumbGroup }}</flux:breadcrumbs.item>
                    @endif
                    <flux:breadcrumbs.item>{{ $title ?? 'Dashboard' }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>

            <flux:spacer />

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 rounded-full border border-green-500/20 bg-green-500/10 px-3 py-1.5">
                    <span class="inline-flex size-2 rounded-full bg-green-500" aria-hidden="true"></span>
                    <span class="text-xs font-medium text-green-700">{{ app()->environment('production') ? 'Production' : ucfirst(app()->environment()) }}</span>
                </div>
            </div>
        </flux:header>

        <flux:main class="bg-zinc-50">
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
