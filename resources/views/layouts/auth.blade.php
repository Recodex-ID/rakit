<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-brand-snow text-brand-ink antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">
            <aside class="bg-brand-ink px-6 py-5 text-brand-snow sm:px-12 lg:w-1/2 lg:p-12 xl:p-16">
                <div class="mx-auto flex h-full w-full max-w-md flex-col">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 self-start" wire:navigate>
                        <img src="{{ asset('images/logo.png') }}" alt="" width="40" height="40" class="size-9 lg:size-10">
                        <span class="font-display text-lg font-bold">{{ config('app.name') }}</span>
                    </a>

                    <div class="hidden flex-1 flex-col justify-center py-12 lg:flex">
                        <h1 class="font-display text-3xl leading-tight font-bold xl:text-4xl">
                            Every unit of stock has a document behind it.
                        </h1>

                        <x-stock-ledger class="mt-10" />

                        <p class="mt-6 text-sm text-brand-mist">
                            Open any item's stock card to trace its balance back to these documents.
                        </p>
                    </div>
                </div>
            </aside>

            <main class="flex flex-1 flex-col px-6 py-12 lg:justify-center sm:px-12 lg:px-16 xl:px-24">
                <div class="mx-auto w-full max-w-sm">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
