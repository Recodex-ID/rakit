<?php

use App\Models\User;

test('guests can open the landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Rakit')
        ->assertSee('laravel new my-erp --using=recodex-id/rakit')
        ->assertSee(route('login'), false)
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
});

test('signed in users see a link to the dashboard instead of sign in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('dashboard'), false);
});
