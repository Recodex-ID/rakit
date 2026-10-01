<?php

test('the root url shows the landing page and the app sends guests to login', function () {
    $this->get('/')->assertOk()->assertViewIs('landing');
    $this->get('/dashboard')->assertRedirect(route('login'));
});
