<?php

use App\Enums\TreatmentTypeEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\Treatment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login for treatment routes', function () {
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->for($disease)->create();

    $this->post(route('admin.diseases.treatments.store', $disease), [
        'title' => 'Organic control',
        'description' => 'Apply an organic treatment.',
        'type' => TreatmentTypeEnum::Organic->value,
    ])->assertRedirect(route('login'));

    $this->put(route('admin.treatments.update', $treatment), [
        'title' => 'Updated control',
        'description' => 'Apply the updated treatment.',
        'type' => TreatmentTypeEnum::Chemical->value,
    ])->assertRedirect(route('login'));

    $this->delete(route('admin.treatments.destroy', $treatment))
        ->assertRedirect(route('login'));

    $this->assertDatabaseCount('treatments', 1);
});

test('farmers and LGU staff are forbidden from treatment routes without changing data', function () {
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->for($disease)->create();
    $original = $treatment->only(['disease_id', 'title', 'description', 'type']);

    foreach ([UserRole::Farmer, UserRole::LguStaff] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->post(route('admin.diseases.treatments.store', $disease), [
                'title' => 'Unauthorized treatment',
                'description' => 'This should not be saved.',
                'type' => TreatmentTypeEnum::Organic->value,
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->put(route('admin.treatments.update', $treatment), [
                'title' => 'Unauthorized update',
                'description' => 'This should not be saved.',
                'type' => TreatmentTypeEnum::Chemical->value,
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('admin.treatments.destroy', $treatment))
            ->assertForbidden();
    }

    $this->assertDatabaseCount('treatments', 1);
    expect($treatment->fresh()->only(['disease_id', 'title', 'description', 'type']))
        ->toBe($original);
});

test('an admin can create a treatment for its disease', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.diseases.treatments.store', $disease), [
            'title' => 'Organic control',
            'description' => 'Apply an organic treatment.',
            'type' => TreatmentTypeEnum::Organic->value,
        ])
        ->assertRedirect(route('admin.diseases.edit', $disease));

    $treatment = Treatment::query()->where('title', 'Organic control')->firstOrFail();

    expect($treatment->disease_id)->toBe($disease->id)
        ->and($treatment->description)->toBe('Apply an organic treatment.')
        ->and($treatment->type)->toBe(TreatmentTypeEnum::Organic);
});

test('an admin can create treatments of every valid type', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();

    foreach (TreatmentTypeEnum::cases() as $type) {
        $this->actingAs($admin)
            ->post(route('admin.diseases.treatments.store', $disease), [
                'title' => "Treatment {$type->value}",
                'description' => "Description for {$type->value}.",
                'type' => $type->value,
            ])
            ->assertRedirect(route('admin.diseases.edit', $disease));
    }

    expect($disease->treatments()->count())->toBe(count(TreatmentTypeEnum::cases()));
});

test('treatment creation validates required fields type and title length', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.diseases.treatments.store', $disease))
        ->assertSessionHasErrors(['title', 'description', 'type']);

    $this->actingAs($admin)
        ->post(route('admin.diseases.treatments.store', $disease), [
            'title' => 'Invalid treatment type',
            'description' => 'This treatment type is invalid.',
            'type' => 'unknown',
        ])
        ->assertSessionHasErrors('type');

    $this->actingAs($admin)
        ->post(route('admin.diseases.treatments.store', $disease), [
            'title' => str_repeat('a', 256),
            'description' => 'A title that is too long.',
            'type' => TreatmentTypeEnum::Chemical->value,
        ])
        ->assertSessionHasErrors('title');

    $this->assertDatabaseCount('treatments', 0);
});

test('an admin can update a treatment and is redirected to its disease', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->for($disease)->create();

    $this->actingAs($admin)
        ->put(route('admin.treatments.update', $treatment), [
            'title' => 'Updated treatment',
            'description' => 'Updated treatment details.',
            'type' => TreatmentTypeEnum::Biological->value,
        ])
        ->assertRedirect(route('admin.diseases.edit', $disease));

    expect($treatment->fresh()->title)->toBe('Updated treatment')
        ->and($treatment->fresh()->description)->toBe('Updated treatment details.')
        ->and($treatment->fresh()->type)->toBe(TreatmentTypeEnum::Biological);
});

test('invalid treatment updates leave the row unchanged', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->for($disease)->create();
    $original = $treatment->only(['disease_id', 'title', 'description', 'type']);

    $this->actingAs($admin)
        ->put(route('admin.treatments.update', $treatment), [
            'title' => '',
            'description' => '',
            'type' => 'unknown',
        ])
        ->assertSessionHasErrors(['title', 'description', 'type']);

    expect($treatment->fresh()->only(['disease_id', 'title', 'description', 'type']))
        ->toBe($original);
});

test('an admin can delete a treatment without deleting its disease or other treatments', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->for($disease)->create();
    $otherTreatment = Treatment::factory()->for($disease)->create();

    $this->actingAs($admin)
        ->delete(route('admin.treatments.destroy', $treatment))
        ->assertRedirect(route('admin.diseases.edit', $disease));

    $this->assertDatabaseMissing('treatments', ['id' => $treatment->id]);
    $this->assertDatabaseHas('treatments', ['id' => $otherTreatment->id]);
    $this->assertDatabaseHas('diseases', ['id' => $disease->id]);
});

test('the disease edit page receives only its treatments ordered by title', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $otherDisease = Disease::factory()->create();
    $zebraTreatment = Treatment::factory()->for($disease)->create([
        'title' => 'Zebra treatment',
    ]);
    $alphaTreatment = Treatment::factory()->for($disease)->create([
        'title' => 'Alpha treatment',
    ]);
    Treatment::factory()->for($otherDisease)->create([
        'title' => 'Other disease treatment',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.diseases.edit', $disease))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/diseases/edit')
            ->has('disease.treatments', 2)
            ->where('disease.treatments.0.id', $alphaTreatment->id)
            ->where('disease.treatments.1.id', $zebraTreatment->id)
            ->where('disease.treatments.0.disease_id', $disease->id)
            ->where('disease.treatments.1.disease_id', $disease->id)
            ->missing('disease.treatments.2'));
});
