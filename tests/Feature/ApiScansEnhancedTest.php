<?php

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\TreatmentTypeEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Http::fake([
        '*' => Http::response([
            'data' => [
                'prediction' => ['class_name' => 'Brown Spot', 'confidence' => 88.50],
                'top_3' => [
                    ['class_name' => 'Brown Spot', 'confidence' => 88.50],
                    ['class_name' => 'Leaf Blast', 'confidence' => 8.20],
                    ['class_name' => 'Sheath Blight', 'confidence' => 3.30],
                ],
            ],
        ]),
    ]);
});

test('scan response includes image_url, top_3 predictions, treatments, status, and created_at', function () {
    $disease = Disease::factory()->create(['name' => 'Brown Spot']);
    $diseaseBlast = Disease::factory()->create(['name' => 'Leaf Blast']);
    $diseaseBlight = Disease::factory()->create(['name' => 'Sheath Blight']);

    $treatment = Treatment::factory()->for($disease)->create([
        'title' => 'Fungicide application',
        'type' => TreatmentTypeEnum::Chemical,
    ]);

    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create([
        'farm_lat' => 14.6760,
        'farm_long' => 120.5360,
        'municipality' => MunicipalityEnum::Balanga,
    ]);

    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)->post('/api/scans', [
        'image' => UploadedFile::fake()->create('leaf.jpg', 150, 'image/jpeg'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('status', ScanStatusEnum::Completed->value)
        ->assertJsonPath('disease.id', $disease->id)
        ->assertJsonPath('disease.name', 'Brown Spot')
        ->assertJsonPath('confidence', 0.885)
        ->assertJsonPath('top_3.0.disease_id', $disease->id)
        ->assertJsonPath('top_3.0.disease_name', 'Brown Spot')
        ->assertJsonPath('top_3.0.confidence', 0.885)
        ->assertJsonPath('top_3.1.disease_id', $diseaseBlast->id)
        ->assertJsonPath('top_3.1.disease_name', 'Leaf Blast')
        ->assertJsonPath('top_3.1.confidence', 0.082)
        ->assertJsonPath('top_3.2.disease_id', $diseaseBlight->id)
        ->assertJsonPath('top_3.2.disease_name', 'Sheath Blight')
        ->assertJsonPath('top_3.2.confidence', 0.033)
        ->assertJsonPath('disease.treatments.0.title', 'Fungicide application');

    expect($response->json('image_url'))->toBeString()->not->toBeEmpty();
    expect($response->json('created_at'))->toBeString()->not->toBeEmpty();

    $scanId = $response->json('id');

    // Detail view
    $detailResponse = $this->withToken($token)->getJson("/api/scans/{$scanId}");
    $detailResponse->assertOk()
        ->assertJsonPath('status', ScanStatusEnum::Completed->value)
        ->assertJsonPath('top_3.0.disease_id', $disease->id)
        ->assertJsonPath('top_3.0.disease_name', 'Brown Spot')
        ->assertJsonPath('disease.treatments.0.title', 'Fungicide application');

    expect($detailResponse->json('image_url'))->toBeString()->not->toBeEmpty();
    expect($detailResponse->json('created_at'))->toBeString()->not->toBeEmpty();
});

test('admins can list other farmers scans and filter by farmer_id or municipality', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $farmer1 = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Balanga]);
    $farmer2 = FarmerProfile::factory()->create(['municipality' => MunicipalityEnum::Orion]);

    $scan1 = Scan::factory()->for($farmer1, 'farmer')->create(['uploaded_by_id' => $farmer1->user_id]);
    $scan2 = Scan::factory()->for($farmer2, 'farmer')->create(['uploaded_by_id' => $farmer2->user_id]);

    $token = $admin->createToken('mobile')->plainTextToken;

    // Admin lists all
    $allScans = $this->withToken($token)->getJson('/api/scans');
    $allScans->assertOk()->assertJsonCount(2, 'data');
    expect($allScans->json('data.0.created_at'))->toBeString()->not->toBeEmpty();

    // Filter by farmer_id
    $farmerFilter = $this->withToken($token)->getJson("/api/scans?farmer_id={$farmer1->id}");
    $farmerFilter->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $scan1->id);

    // Filter by municipality
    $muniFilter = $this->withToken($token)->getJson('/api/scans?municipality=orion');
    $muniFilter->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $scan2->id);
});

test('created_at is present in index, show, and store scan payloads as ISO 8601 string', function () {
    $disease = Disease::factory()->create(['name' => 'Brown Spot']);
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $token = $user->createToken('mobile')->plainTextToken;

    // Store
    $storeResponse = $this->withToken($token)->post('/api/scans', [
        'image' => UploadedFile::fake()->create('leaf.jpg', 150, 'image/jpeg'),
    ])->assertCreated();

    $storeCreatedAt = $storeResponse->json('created_at');
    expect($storeCreatedAt)->toBeString()->not->toBeEmpty();
    expect(Carbon::parse($storeCreatedAt)->toIso8601String())->toBe($storeCreatedAt);

    $scanId = $storeResponse->json('id');

    // Show
    $showResponse = $this->withToken($token)->getJson("/api/scans/{$scanId}")->assertOk();
    $showCreatedAt = $showResponse->json('created_at');
    expect($showCreatedAt)->toBe($storeCreatedAt);

    // Index
    $indexResponse = $this->withToken($token)->getJson('/api/scans')->assertOk();
    $indexCreatedAt = $indexResponse->json('data.0.created_at');
    expect($indexCreatedAt)->toBe($storeCreatedAt);
});

test('top_3 maps snake_case class names to real disease names with 0-1 confidence', function () {
    $blight = Disease::factory()->create(['name' => 'Sheath Blight']);
    $stemRot = Disease::factory()->create(['name' => 'Stem Rot']);
    $sheathRot = Disease::factory()->create(['name' => 'Sheath Rot']);

    Http::swap(new Factory);
    Http::fake([
        '*' => Http::response([
            'status' => 'success',
            'data' => [
                'prediction' => ['class_name' => 'sheath_blight', 'confidence' => 51.03],
                'top_predictions' => [
                    ['class_name' => 'sheath_blight', 'confidence' => 51.03],
                    ['class_name' => 'stem_rot', 'confidence' => 24.39],
                    ['class_name' => 'sheath_rot', 'confidence' => 18.95],
                ],
            ],
            'metadata' => ['model_version' => 'vit-base'],
        ]),
    ]);

    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $response = $this->withToken($token)->post('/api/scans', [
        'image' => UploadedFile::fake()->create('leaf.jpg', 150, 'image/jpeg'),
    ]);

    $response->assertCreated()
        ->assertJsonPath('top_3.0.disease_id', $blight->id)
        ->assertJsonPath('top_3.0.disease_name', 'Sheath Blight')
        ->assertJsonPath('top_3.0.confidence', 0.5103)
        ->assertJsonPath('top_3.1.disease_id', $stemRot->id)
        ->assertJsonPath('top_3.1.disease_name', 'Stem Rot')
        ->assertJsonPath('top_3.1.confidence', 0.2439)
        ->assertJsonPath('top_3.2.disease_id', $sheathRot->id)
        ->assertJsonPath('top_3.2.disease_name', 'Sheath Rot')
        ->assertJsonPath('top_3.2.confidence', 0.1895);
});

test('top_3 skips class names matching no disease and falls back to scan disease if none match', function () {
    $disease = Disease::factory()->create(['name' => 'Brown Spot']);
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $token = $user->createToken('mobile')->plainTextToken;

    // Scan with unknown class names in top_predictions falls back to scan's own disease
    $scan = Scan::factory()->create([
        'farmer_id' => $farmer->id,
        'uploaded_by_id' => $user->id,
        'disease_id' => $disease->id,
        'confidence_score' => 0.95,
        'raw_predictions' => [
            'data' => [
                'top_predictions' => [
                    ['class_name' => 'unknown_disease_xyz', 'confidence' => 80.0],
                ],
            ],
        ],
    ]);

    $response = $this->withToken($token)->getJson("/api/scans/{$scan->id}");
    $response->assertOk()
        ->assertJsonPath('top_3.0.disease_id', $disease->id)
        ->assertJsonPath('top_3.0.disease_name', 'Brown Spot')
        ->assertJsonPath('top_3.0.confidence', 0.95);
});
