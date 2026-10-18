<?php

use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;

test('an admin can view a scan belonging to a farmer', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->create();
    $scan = Scan::factory()->create([
        'farmer_id' => $farmer->id,
        'uploaded_by_id' => $farmer->user_id,
    ]);

    $this->actingAs($admin)
        ->get(route('scans.show', $scan))
        ->assertOk();
});

test('an admin can close an outbreak', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $outbreak = Outbreak::factory()->create();

    $this->actingAs($admin)
        ->patch(route('outbreaks.close', $outbreak))
        ->assertRedirect();

    expect($outbreak->fresh()->status)->toBe(OutbreakStatusEnum::Resolved);
});

test('an admin can load restricted index pages', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('reports.index'))->assertOk();
    $this->actingAs($admin)->get(route('outbreaks.index'))->assertOk();
});

test('a farmer remains denied on restricted index pages', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);

    $this->actingAs($farmer)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($farmer)->get(route('outbreaks.index'))->assertForbidden();
});
