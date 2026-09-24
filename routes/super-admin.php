<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::livewire('roles', 'pages::super-admin.roles')->name('roles');
    Route::livewire('activity', 'pages::super-admin.activity')->name('activity');
});
