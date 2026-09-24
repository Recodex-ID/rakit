<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-snow antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">
            {{-- Branding panel --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-brand-ink p-12 lg:flex lg:w-1/2 xl:p-16">
                <div class="auth-grid-bg absolute inset-0 opacity-50"></div>

                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div class="relative size-[420px]">
                        <div class="absolute inset-0 rounded-full border border-brand-orange/15">
                            <div class="absolute top-0 left-1/2 size-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-orange"></div>
                        </div>
                        <div class="absolute inset-[50px] rounded-full border border-brand-snow/10">
                            <div class="absolute top-0 left-1/2 size-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-snow"></div>
                            <div class="absolute bottom-0 left-1/2 size-2 -translate-x-1/2 translate-y-1/2 rounded-full bg-brand-orange/60"></div>
                        </div>
                        <div class="absolute inset-[100px] rounded-full border border-brand-snow/15"></div>

                        <div class="absolute top-1/2 left-1/2 flex size-28 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-3xl border border-brand-snow/10 bg-brand-graphite">
                            <flux:icon.cube class="size-9 text-brand-orange" />
                        </div>
                    </div>
                </div>

                <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-3" wire:navigate>
                    <img src="{{ asset('images/logo.png') }}" alt="" width="44" height="44" class="size-11 rounded-xl border border-brand-ink bg-brand-snow p-2">
                    <span class="flex flex-col leading-none">
                        <span class="font-display text-lg font-bold text-brand-snow">{{ config('app.name') }}</span>
                        <span class="mt-1 font-mono text-[10px] tracking-[0.25em] text-brand-mist/80 uppercase">ERP Starter Kit</span>
                    </span>
                </a>

                <div class="relative z-10 max-w-md">
                    <h1 class="mb-6 font-display text-4xl leading-[1.1] font-bold tracking-tight text-brand-snow xl:text-5xl">
                        Stock, purchasing<br>
                        <span class="text-brand-orange">and sales</span><br>
                        in one ledger.
                    </h1>

                    <p class="mb-8 leading-relaxed text-brand-mist/80">
                        Received goods raise stock, delivered orders lower it, and every change can be traced back to the document that caused it.
                    </p>

                    <ul class="grid grid-cols-3 gap-6 border-t border-brand-snow/10 pt-8">
                        <li>
                            <flux:icon.archive-box class="size-6 text-brand-orange" />
                            <div class="mt-2 text-[11px] tracking-wider text-brand-mist/80 uppercase">Inventory</div>
                        </li>
                        <li>
                            <flux:icon.truck class="size-6 text-brand-orange" />
                            <div class="mt-2 text-[11px] tracking-wider text-brand-mist/80 uppercase">Purchasing</div>
                        </li>
                        <li>
                            <flux:icon.shopping-cart class="size-6 text-brand-orange" />
                            <div class="mt-2 text-[11px] tracking-wider text-brand-mist/80 uppercase">Sales</div>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="relative flex flex-1 flex-col justify-center bg-brand-snow px-6 py-12 sm:px-12 lg:px-16 xl:px-24">
                <a href="{{ route('home') }}" class="absolute top-8 left-6 flex items-center gap-3 sm:left-12 lg:hidden" wire:navigate>
                    <img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl border border-brand-ink bg-brand-snow p-1.5">
                    <span class="flex flex-col leading-none">
                        <span class="font-display font-bold text-brand-ink">{{ config('app.name') }}</span>
                        <span class="mt-1 font-mono text-[9px] tracking-[0.25em] text-brand-ink/70 uppercase">ERP Starter Kit</span>
                    </span>
                </a>

                <div class="mx-auto mt-16 w-full max-w-md lg:mt-0">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
