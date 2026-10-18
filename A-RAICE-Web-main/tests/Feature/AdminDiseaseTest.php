<?php

use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\Treatment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function diseaseAttributes(array $overrides = []): array
{
    return array_merge([
        'name' => fake()->unique()->words(2, true),
        'description' => fake()->sentence(),
        'prevention_tips' => fake()->sentence(),
    ], $overrides);
}

test('guests are redirected to login for all admin disease routes', function () {
    $disease = Disease::factory()->create();
    $attributes = diseaseAttributes();

    $this->get(route('admin.diseases.create'))
        ->assertRedirect(route('login'));
    $this->post(route('admin.diseases.store'), $attributes)
        ->assertRedirect(route('login'));
    $this->get(route('admin.diseases.edit', $disease))
        ->assertRedirect(route('login'));
    $this->put(route('admin.diseases.update', $disease), $attributes)
        ->assertRedirect(route('login'));
    $this->delete(route('admin.diseases.destroy', $disease))
        ->assertRedirect(route('login'));
});

test('farmers and LGU staff are forbidden from all admin disease routes', function () {
    $disease = Disease::factory()->create();
    $attributes = diseaseAttributes();

    foreach ([UserRole::Farmer, UserRole::LguStaff] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.diseases.create'))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.diseases.store'), $attributes)
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('admin.diseases.edit', $disease))
            ->assertForbidden();
        $this->actingAs($user)
            ->put(route('admin.diseases.update', $disease), $attributes)
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('admin.diseases.destroy', $disease))
            ->assertForbidden();
    }
});

test('admins can open disease create and edit pages', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.diseases.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/diseases/create'));

    $this->actingAs($admin)
        ->get(route('admin.diseases.edit', $disease))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/diseases/edit')
            ->where('disease.id', $disease->id)
            ->where('disease.name', $disease->name));
});

test('an admin can create a disease with only the required fields', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $attributes = diseaseAttributes();

    $response = $this->actingAs($admin)
        ->post(route('admin.diseases.store'), $attributes)
        ->assertRedirect();

    $disease = Disease::query()->where('name', $attributes['name'])->firstOrFail();

    $this->assertDatabaseHas('diseases', [
        'id' => $disease->id,
        ...$attributes,
        'causes' => null,
        'symptoms' => null,
        'history' => null,
        'sources' => null,
        'image_path' => null,
    ]);

    $response->assertRedirect(route('encyclopedia.show', $disease));
});

test('an admin can create a disease with all fields', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $attributes = diseaseAttributes([
        'causes' => 'Fungal infection',
        'symptoms' => 'Lesions on leaves',
        'history' => 'First observed decades ago',
        'sources' => 'Agricultural research',
        'image_path' => 'images/disease.jpg',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.diseases.store'), $attributes)
        ->assertRedirect();

    $disease = Disease::query()->where('name', $attributes['name'])->firstOrFail();

    $this->assertDatabaseHas('diseases', [
        'id' => $disease->id,
        ...$attributes,
    ]);
    $response->assertRedirect(route('encyclopedia.show', $disease));
});

test('disease creation requires the required fields and a unique name', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post(route('admin.diseases.store'))
        ->assertSessionHasErrors(['name', 'description', 'prevention_tips']);

    $existingDisease = Disease::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.diseases.store'), diseaseAttributes([
            'name' => $existingDisease->name,
        ]))
        ->assertSessionHasErrors('name');
});

test('an admin can update a disease while keeping its name unchanged', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $attributes = diseaseAttributes([
        'name' => $disease->name,
        'description' => 'Updated disease description.',
        'prevention_tips' => 'Updated prevention guidance.',
        'causes' => 'Updated cause.',
        'symptoms' => 'Updated symptoms.',
        'history' => 'Updated history.',
        'sources' => 'Updated sources.',
        'image_path' => 'images/updated-disease.jpg',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.diseases.update', $disease), $attributes)
        ->assertRedirect(route('encyclopedia.show', $disease));

    $this->assertDatabaseHas('diseases', [
        'id' => $disease->id,
        ...$attributes,
    ]);
});

test('an admin cannot update a disease to another disease name', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $otherDisease = Disease::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.diseases.update', $disease), diseaseAttributes([
            'name' => $otherDisease->name,
        ]))
        ->assertSessionHasErrors('name');

    expect($disease->fresh()->name)->toBe($disease->name);
});

test('an admin can delete a disease and its treatments', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $treatment = Treatment::factory()->create(['disease_id' => $disease->id]);

    $this->actingAs($admin)
        ->delete(route('admin.diseases.destroy', $disease))
        ->assertRedirect(route('encyclopedia.index'));

    $this->assertDatabaseMissing('diseases', ['id' => $disease->id]);
    $this->assertDatabaseMissing('treatments', ['id' => $treatment->id]);
});

test('an admin cannot delete a disease referenced by a scan', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    $farmer = FarmerProfile::factory()->create();
    Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $farmer->user_id,
    ]);

    $this->actingAs($admin)
        ->from(route('encyclopedia.show', $disease))
        ->delete(route('admin.diseases.destroy', $disease))
        ->assertRedirect(route('encyclopedia.show', $disease))
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('diseases', ['id' => $disease->id]);
});

test('an admin cannot delete a disease referenced by an outbreak', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $disease = Disease::factory()->create();
    Outbreak::factory()->create(['disease_id' => $disease->id]);

    $this->actingAs($admin)
        ->from(route('encyclopedia.show', $disease))
        ->delete(route('admin.diseases.destroy', $disease))
        ->assertRedirect(route('encyclopedia.show', $disease))
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('diseases', ['id' => $disease->id]);
});
