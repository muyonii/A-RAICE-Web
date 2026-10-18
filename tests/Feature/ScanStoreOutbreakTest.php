<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\SeverityEnum;
use App\Models\Alert;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function priorScanFor(Disease $disease, MunicipalityEnum $municipality): Scan
{
    $farmer = FarmerProfile::factory()->inMunicipality($municipality)->create();

    return Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $farmer->user_id,
    ]);
}

test('the third distinct farmer scan through the endpoint fires an outbreak', function () {
    $disease = Disease::factory()->create(['name' => 'Stem Rot']);

    $earlier = [
        priorScanFor($disease, MunicipalityEnum::Orion),
        priorScanFor($disease, MunicipalityEnum::Orion),
    ];

    $third = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();

    Http::fake([
        '*' => Http::response([
            'data' => ['prediction' => ['class_name' => 'Stem Rot', 'confidence' => 98.4]],
        ]),
    ]);

    $this->actingAs($third->user)
        ->post(route('scans.store'), [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
        ])
        ->assertRedirect();

    $newScan = Scan::where('farmer_id', $third->id)->sole();
    $outbreak = Outbreak::sole();

    expect($newScan->status)->toBe(ScanStatusEnum::Completed)
        ->and($newScan->disease_id)->toBe($disease->id)
        ->and($outbreak->status)->toBe(OutbreakStatusEnum::Active)
        ->and($outbreak->municipality)->toBe(MunicipalityEnum::Orion)
        ->and(Alert::sole()->severity)->toBe(SeverityEnum::Low)
        ->and($newScan->outbreak_id)->toBe($outbreak->id);

    foreach ($earlier as $scan) {
        expect($scan->fresh()->outbreak_id)->toBe($outbreak->id);
    }
});

test('a failed AI call saves the scan as failed and does not touch outbreaks', function () {
    $disease = Disease::factory()->create(['name' => 'Stem Rot']);

    priorScanFor($disease, MunicipalityEnum::Orion);
    priorScanFor($disease, MunicipalityEnum::Orion);

    $third = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();

    Http::fake(['*' => Http::response([], 500)]);

    $this->actingAs($third->user)
        ->post(route('scans.store'), [
            'image' => UploadedFile::fake()->create('leaf.jpg', 100, 'image/jpeg'),
        ])
        ->assertRedirect();

    expect(Scan::where('farmer_id', $third->id)->sole()->status)->toBe(ScanStatusEnum::Failed)
        ->and(Outbreak::count())->toBe(0)
        ->and(Alert::count())->toBe(0);
});
