<?php

if (! class_exists('Laravel\\Fortify\\Features')) {
    return;
}

use App\Models\User;
use Laravel\Fortify\Features;

test('confirm password screen can be rendered', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('password.confirm'));

    $response->assertOk();
});

test('password confirmation requires authentication', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
