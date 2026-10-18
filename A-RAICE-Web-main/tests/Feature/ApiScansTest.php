<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\TreatmentTypeEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Http::fake([
        '*' => Http::response([
            'data' => ['prediction' => ['class_name' => 'Leaf Spot', 'confidence' => 91.25]],
        ]),
    ]);
});

test('authenticated users of every role can submit a mobile scan', function () {
    $disease = Disease::factory()->create(['name' => 'Leaf Spot']);

    foreach (UserRole::cases() as $role) {
        $user = User::factory()->create(['role' => $role]);
        $farmer = $role === UserRole::Farmer
            ? FarmerProfile::factory()->for($user)->create()
            : FarmerProfile::factory()->create();

        $payload = [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
            'gps_lat' => 14.6123456,
            'gps_lng' => 120.4987654,
        ];

        if ($role !== UserRole::Farmer) {
            $payload['farmer_id'] = $farmer->id;
        }

        $this->app['auth']->forgetGuards();
        $response = $this->withToken($user->createToken('mobile')->plainTextToken)
            ->withHeader('Accept', 'application/json')
            ->post('/api/scans', $payload);

        $response->assertCreated()
            ->assertJsonPath('status', ScanStatusEnum::Completed->value)
            ->assertJsonPath('disease.id', $disease->id)
            ->assertJsonPath('disease.name', 'Leaf Spot')
            ->assertJsonPath('confidence', 0.9125)
            ->assertJsonPath('gps_lat', 14.6123456)
            ->assertJsonPath('gps_lng', 120.4987654)
            ->assertJsonPath('outbreak_id', null);

        $scan = Scan::findOrFail($response->json('id'));
        expect($scan->farmer_id)->toBe($farmer->id)
            ->and($scan->uploaded_by_id)->toBe($user->id);
    }
});

test('mobile scan validation rejects invalid GPS and farmer selection for farmers', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $ownProfile = FarmerProfile::factory()->for($user)->create();
    $otherProfile = FarmerProfile::factory()->create();

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
            'gps_lat' => 91,
            'farmer_id' => $otherProfile->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['gps_lat', 'farmer_id']);

    expect(Scan::count())->toBe(0)
        ->and($ownProfile->exists)->toBeTrue();

    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);
    $this->app['auth']->forgetGuards();

    $this->withToken($lgu->createToken('mobile')->plainTextToken)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['farmer_id']);
});

test('all mobile scan endpoints require authentication', function () {
    $scan = Scan::factory()->create();

    $this->getJson('/api/scans')->assertUnauthorized();
    $this->getJson("/api/scans/{$scan->id}")->assertUnauthorized();
    $this->post('/api/scans', [
        'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
    ])->assertUnauthorized();
});

test('mobile scan creation is limited to five requests per user per minute', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($user)->create();
    $otherUser = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($otherUser)->create();
    $token = $user->createToken('mobile')->plainTextToken;
    $otherToken = $otherUser->createToken('mobile')->plainTextToken;
    $payload = [
        'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
    ];

    for ($request = 0; $request < 5; $request++) {
        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/scans', $payload)
            ->assertCreated();
    }

    $this->withToken($token)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', $payload)
        ->assertTooManyRequests();

    $this->app['auth']->forgetGuards();

    $this->withToken($otherToken)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', $payload)
        ->assertCreated();
});

test('a farmer cannot access another farmers mobile scan detail', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($user)->create();
    $otherFarmer = FarmerProfile::factory()->create();
    $scan = Scan::factory()->for($otherFarmer, 'farmer')->create([
        'uploaded_by_id' => $otherFarmer->user_id,
    ]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson("/api/scans/{$scan->id}")
        ->assertForbidden();
});

test('scan detail includes title-ordered treatments while the scan list omits them', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $disease = Disease::factory()->create(['name' => 'Leaf Spot']);
    $scan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $user->id,
    ]);
    $zebraTreatment = Treatment::factory()->for($disease)->create([
        'title' => 'Zebra treatment',
        'description' => 'Apply after symptoms appear.',
        'type' => TreatmentTypeEnum::Chemical,
    ]);
    $alphaTreatment = Treatment::factory()->for($disease)->create([
        'title' => 'Alpha treatment',
        'description' => 'Use resistant seed.',
        'type' => TreatmentTypeEnum::Cultural,
    ]);
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/scans/{$scan->id}")
        ->assertOk()
        ->assertJsonPath('disease.treatments.0', [
            'id' => $alphaTreatment->id,
            'title' => 'Alpha treatment',
            'description' => 'Use resistant seed.',
            'type' => TreatmentTypeEnum::Cultural->value,
        ])
        ->assertJsonPath('disease.treatments.1', [
            'id' => $zebraTreatment->id,
            'title' => 'Zebra treatment',
            'description' => 'Apply after symptoms appear.',
            'type' => TreatmentTypeEnum::Chemical->value,
        ]);

    $this->app['auth']->forgetGuards();
    $this->withToken($token)
        ->getJson('/api/scans')
        ->assertOk()
        ->assertJsonMissingPath('data.0.disease.treatments');
});

test('scan detail returns an empty treatments array when the disease has none', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $disease = Disease::factory()->create();
    $scan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson("/api/scans/{$scan->id}")
        ->assertOk()
        ->assertJsonPath('disease.treatments', []);
});

test('scan detail keeps disease null when no disease was detected', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $scan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => null,
        'uploaded_by_id' => $user->id,
    ]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson("/api/scans/{$scan->id}")
        ->assertOk()
        ->assertJsonPath('disease', null);
});

test('an LGU scan counts the selected farmer toward outbreak detection', function () {
    $disease = Disease::factory()->create(['name' => 'Leaf Spot']);

    foreach ([1, 2] as $_) {
        $farmer = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();
        Scan::factory()->for($farmer, 'farmer')->create([
            'disease_id' => $disease->id,
            'uploaded_by_id' => $farmer->user_id,
        ]);
    }

    $targetFarmer = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);

    $response = $this->withToken($lgu->createToken('mobile')->plainTextToken)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
            'farmer_id' => $targetFarmer->id,
        ]);

    $response->assertCreated()->assertJsonPath('status', ScanStatusEnum::Completed->value);
    $scan = Scan::findOrFail($response->json('id'));

    expect($scan->farmer_id)->toBe($targetFarmer->id)
        ->and($scan->uploaded_by_id)->toBe($lgu->id)
        ->and(Outbreak::count())->toBe(1)
        ->and($scan->outbreak_id)->toBe(Outbreak::sole()->id)
        ->and($scan->outbreak->status)->toBe(OutbreakStatusEnum::Active);
});

test('mobile scan uses farmer farm GPS when device GPS is not supplied', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create([
        'farm_lat' => 14.6543210,
        'farm_long' => 120.5432100,
    ]);

    $response = $this->withToken($user->createToken('mobile')->plainTextToken)
        ->withHeader('Accept', 'application/json')
        ->post('/api/scans', [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
        ]);

    $response->assertCreated()
        ->assertJsonPath('gps_lat', 14.654321)
        ->assertJsonPath('gps_lng', 120.54321);

    expect(Scan::sole()->farmer_id)->toBe($farmer->id);
});

test('mobile scan lists are paginated and scoped by role and detail includes disease and outbreak', function () {
    $disease = Disease::factory()->create(['name' => 'Leaf Spot']);
    $farmerUser = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($farmerUser)->create();
    $otherFarmer = FarmerProfile::factory()->create();

    $ownScan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $farmerUser->id,
    ]);
    Scan::factory()->for($otherFarmer, 'farmer')->create([
        'uploaded_by_id' => $otherFarmer->user_id,
    ]);

    $farmerList = $this->withToken($farmerUser->createToken('mobile')->plainTextToken)
        ->getJson('/api/scans');

    $farmerList->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownScan->id);

    $staff = User::factory()->create(['role' => UserRole::LguStaff]);
    $this->app['auth']->forgetGuards();
    $staffList = $this->withToken($staff->createToken('mobile')->plainTextToken)
        ->getJson('/api/scans');

    $staffList->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment([
            'farmer' => [
                'id' => $otherFarmer->id,
                'name' => $otherFarmer->full_name,
                'contact_number' => $otherFarmer->contact_number,
            ],
        ]);

    $detail = $this->withToken($farmerUser->createToken('detail')->plainTextToken)
        ->getJson("/api/scans/{$ownScan->id}");

    $detail->assertOk()
        ->assertJsonPath('disease.id', $disease->id)
        ->assertJsonPath('confidence', 0.95)
        ->assertJsonPath('gps_lat', 14.5)
        ->assertJsonPath('gps_lng', 120.5)
        ->assertJsonPath('outbreak_id', null)
        ->assertJsonPath('outbreak', null);
});
