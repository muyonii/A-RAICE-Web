<?php

use App\Enums\UserRole;
use App\Models\User;

test('a farmer cannot view the outbreaks page', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);

    $this->actingAs($farmer)->get(route('outbreaks.index'))->assertForbidden();
});

test('lgu staff and admin can view the outbreaks page', function () {
    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('outbreaks.index'))->assertOk();
    }
});
