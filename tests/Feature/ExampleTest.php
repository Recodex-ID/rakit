<?php

test('the root url sends visitors to the landing page', function () {
    $this->get(route('home'))->assertRedirect(route('landing'));
    $this->get('/dashboard')->assertRedirect(route('login'));
});
