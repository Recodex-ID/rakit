<?php

use App\Http\Controllers\PrintDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::app.dashboard')->name('dashboard');

    Route::middleware('can:master-data.view')->prefix('master-data')->name('master-data.')->group(function () {
        Route::livewire('items', 'pages::app.master-data.items')->name('items');
        Route::livewire('warehouses', 'pages::app.master-data.warehouses')->name('warehouses');
        Route::livewire('customers', 'pages::app.master-data.customers')->name('customers');
        Route::livewire('suppliers', 'pages::app.master-data.suppliers')->name('suppliers');
    });

    Route::middleware('can:inventory.view')->prefix('inventory')->name('inventory.')->group(function () {
        Route::livewire('stock', 'pages::app.inventory.stock')->name('stock');
        Route::livewire('stock/{item}', 'pages::app.inventory.stock-card')->name('stock-card');
    });

    Route::middleware('can:purchasing.view')->prefix('purchasing')->name('purchasing.')->group(function () {
        Route::livewire('orders', 'pages::app.purchasing.orders')->name('orders');
        Route::livewire('orders/create', 'pages::app.purchasing.order-form')->middleware('can:purchasing.manage')->name('orders.create');
        Route::livewire('orders/{purchaseOrder}', 'pages::app.purchasing.order-show')->name('orders.show');
        Route::livewire('orders/{purchaseOrder}/edit', 'pages::app.purchasing.order-form')->middleware('can:purchasing.manage')->name('orders.edit');
        Route::get('orders/{purchaseOrder}/print', [PrintDocumentController::class, 'purchaseOrder'])->name('orders.print');
    });

    Route::middleware('can:sales.view')->prefix('sales')->name('sales.')->group(function () {
        Route::livewire('orders', 'pages::app.sales.orders')->name('orders');
        Route::livewire('orders/create', 'pages::app.sales.order-form')->middleware('can:sales.manage')->name('orders.create');
        Route::livewire('orders/{salesOrder}', 'pages::app.sales.order-show')->name('orders.show');
        Route::livewire('orders/{salesOrder}/edit', 'pages::app.sales.order-form')->middleware('can:sales.manage')->name('orders.edit');
        Route::get('orders/{salesOrder}/print', [PrintDocumentController::class, 'salesOrder'])->name('orders.print');
    });

    Route::prefix('other')->name('other.')->group(function () {
        Route::livewire('docs', 'pages::app.other.docs')->name('docs');
    });

    Route::middleware(['role:super-admin|admin'])->prefix('system')->name('system.')->group(function () {
        Route::livewire('settings', 'pages::app.system.settings')->name('settings');
        Route::livewire('users', 'pages::app.system.users')->name('users');
        Route::livewire('media-library', 'pages::app.system.media-library')->name('media-library');
    });

    Route::redirect('settings', 'settings/profile');
    Route::livewire('settings/profile', 'pages::app.settings.profile')->name('profile.edit');
    Route::livewire('settings/security', 'pages::app.settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
