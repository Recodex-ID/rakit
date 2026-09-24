<?php

use Illuminate\Support\Facades\Route;

// The app itself lives behind the login; /landing is the one public page, describing the starter kit.
Route::redirect('/', '/dashboard')->name('home');
Route::view('/landing', 'landing')->name('landing');

Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

require __DIR__.'/super-admin.php';
require __DIR__.'/app.php';
