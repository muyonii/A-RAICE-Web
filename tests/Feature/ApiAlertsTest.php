<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Database\Seeders\ApiTestSeeder;

test('users of each role list only their own alerts with alert details', function () {
    foreach ([UserRole::Farmer, UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $otherUser = User::factory()->create();
        $disease = Disease::factory()->create(['name' => 'Rice Blast']);
        $outbreak = Outbreak::factory()->for($disease)->create([
            'municipality' => MunicipalityEnum::Balanga,
        ]);
        $ownAlert = Alert::factory()->for($outbreak)->create([
            'message' => 'Smoke test alert for '.$role->value,
        ]);
        $otherAlert = Alert::factory()->create();
        $ownAlert->users()->attach($user);
        $otherAlert->users()->attach($otherUser);
        $token = $user->createToken('mobile')->plainTextToken;
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/alerts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $ownAlert->id)
            ->assertJsonPath('data.0.message', $ownAlert->message)
            ->assertJsonPath('data.0.severity', $ownAlert->severity->value)
            ->assertJsonPath('data.0.disease', 'Rice Blast')
            ->assertJsonPath('data.0.municipality', MunicipalityEnum::Balanga->value)
            ->assertJsonPath('data.0.outbreak_id', $outbreak->id)
            ->assertJsonPath('data.0.read_at', null)
            ->assertJsonMissing(['id' => $otherAlert->id])
            ->assertJsonStructure(['current_page', 'data', 'last_page', 'per_page', 'total']);
    }
});

test('unread alert filter returns only the current user unread alerts', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $otherUser = User::factory()->create();
    $unread = Alert::factory()->create();
    $read = Alert::factory()->create();
    $anotherUsersAlert = Alert::factory()->create();
    $unread->users()->attach($user);
    $read->users()->attach($user, ['read_at' => now()]);
    $anotherUsersAlert->users()->attach($otherUser);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson('/api/alerts?unread=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $unread->id);
});

test('marking an alert read affects only this user and is idempotent', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $otherUser = User::factory()->create();
    $alert = Alert::factory()->create();
    $alert->users()->attach($user);
    $alert->users()->attach($otherUser);
    $token = $user->createToken('mobile')->plainTextToken;

    $firstResponse = $this->withToken($token)
        ->postJson("/api/alerts/{$alert->id}/read")
        ->assertOk()
        ->assertJsonPath('id', $alert->id);

    expect($firstResponse->json('read_at'))->not->toBeNull();

    $firstReadAt = $user->alerts()->whereKey($alert->id)->firstOrFail()->pivot->read_at;
    expect($firstReadAt)->not->toBeNull()
        ->and($otherUser->alerts()->whereKey($alert->id)->firstOrFail()->pivot->read_at)->toBeNull();

    $this->travel(5)->minutes();
    $this->app['auth']->forgetGuards();
    $secondResponse = $this->withToken($token)
        ->postJson("/api/alerts/{$alert->id}/read")
        ->assertOk();

    expect($secondResponse->json('read_at'))->toBe($firstReadAt);
});

test('mobile alert endpoints require authentication', function () {
    $alert = Alert::factory()->create();

    $this->getJson('/api/alerts')->assertUnauthorized();
    $this->postJson("/api/alerts/{$alert->id}/read")->assertUnauthorized();
});

test('a user receives not found when marking another users alert read', function () {
    $owner = User::factory()->create(['role' => UserRole::Farmer]);
    $otherUser = User::factory()->create(['role' => UserRole::Farmer]);
    $alert = Alert::factory()->create();
    $alert->users()->attach($owner);

    $this->withToken($otherUser->createToken('mobile')->plainTextToken)
        ->postJson("/api/alerts/{$alert->id}/read")
        ->assertNotFound();

    expect($owner->alerts()->whereKey($alert->id)->firstOrFail()->pivot->read_at)->toBeNull();
});

test('local API seeder creates a repeatable outbreak alert for seeded accounts', function () {
    $this->seed(ApiTestSeeder::class);

    $outbreak = Outbreak::query()
        ->whereHas('disease', fn ($query) => $query->where('name', 'API Smoke Test Disease'))
        ->where('municipality', MunicipalityEnum::Balanga)
        ->firstOrFail();
    $alert = $outbreak->alerts()->firstOrFail();
    $farmer = User::query()->where('email', 'api-farmer@test.local')->firstOrFail();
    $farmer2 = User::query()->where('email', 'api-farmer2@test.local')->firstOrFail();
    $lgu = User::query()->where('email', 'api-lgu@test.local')->firstOrFail();
    $admin = User::query()->where('email', 'api-admin@test.local')->firstOrFail();
    $alertRecipients = $alert->users()->pluck('users.id');

    expect(Scan::query()->where('disease_id', $outbreak->disease_id)->count())->toBe(3)
        ->and($alertRecipients)->toContain($farmer->id)
        ->and($alertRecipients)->toContain($lgu->id)
        ->and($alertRecipients)->toContain($admin->id)
        ->and($farmer2->alerts()->whereKey($alert->id)->exists())->toBeFalse()
        ->and($farmer->alerts()->whereKey($alert->id)->firstOrFail()->pivot->read_at)->toBeNull();

    $this->seed(ApiTestSeeder::class);

    expect(Outbreak::count())->toBe(1)
        ->and($outbreak->alerts()->count())->toBe(1)
        ->and(Scan::query()->where('disease_id', $outbreak->disease_id)->count())->toBe(3)
        ->and($farmer->alerts()->whereKey($alert->id)->firstOrFail()->pivot->read_at)->toBeNull();
});
