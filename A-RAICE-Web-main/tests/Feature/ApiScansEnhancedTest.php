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
use Illuminate\Http\UploadedFile;
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

test('scan response includes image_url, top_3 predictions, treatments, and status', function () {
    $disease = Disease::factory()->create(['name' => 'Brown Spot']);
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
        ->assertJsonPath('top_3.0.disease_name', 'Brown Spot')
        ->assertJsonPath('top_3.0.confidence', 0.885)
        ->assertJsonPath('top_3.1.disease_name', 'Leaf Blast')
        ->assertJsonPath('top_3.2.disease_name', 'Sheath Blight')
        ->assertJsonPath('disease.treatments.0.title', 'Fungicide application');

    expect($response->json('image_url'))->toBeString()->not->toBeEmpty();

    $scanId = $response->json('id');

    // Detail view
    $detailResponse = $this->withToken($token)->getJson("/api/scans/{$scanId}");
    $detailResponse->assertOk()
        ->assertJsonPath('status', ScanStatusEnum::Completed->value)
        ->assertJsonPath('top_3.0.disease_name', 'Brown Spot')
        ->assertJsonPath('disease.treatments.0.title', 'Fungicide application');

    expect($detailResponse->json('image_url'))->toBeString()->not->toBeEmpty();
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
