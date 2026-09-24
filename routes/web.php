<?php

use Illuminate\Support\Facades\Route;

// Rakit has no public site: everything lives behind the login.
Route::redirect('/', '/dashboard')->name('home');

Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

require __DIR__.'/super-admin.php';
require __DIR__.'/app.php';
