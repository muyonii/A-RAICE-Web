<?php

use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\Outbreak;
use App\Models\User;

test('a farmer cannot close an outbreak', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $outbreak = Outbreak::factory()->create();

    $this->actingAs($farmer)
        ->patch(route('outbreaks.close', $outbreak))
        ->assertForbidden();

    expect($outbreak->fresh()->status)->toBe(OutbreakStatusEnum::Active);
});

test('lgu staff can close an outbreak', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $outbreak = Outbreak::factory()->create();

    $this->actingAs($lgu)
        ->patch(route('outbreaks.close', $outbreak))
        ->assertRedirect();

    $outbreak->refresh();

    expect($outbreak->status)->toBe(OutbreakStatusEnum::Resolved)
        ->and($outbreak->closed_by)->toBe($lgu->id)
        ->and($outbreak->closed_at)->not->toBeNull();
});

test('admin can close an outbreak', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $outbreak = Outbreak::factory()->create();

    $this->actingAs($admin)
        ->patch(route('outbreaks.close', $outbreak))
        ->assertRedirect();

    expect($outbreak->fresh()->status)->toBe(OutbreakStatusEnum::Resolved);
});

test('closing an already resolved outbreak is rejected', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $outbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Resolved]);

    $this->actingAs($lgu)
        ->patch(route('outbreaks.close', $outbreak))
        ->assertStatus(409);
});
