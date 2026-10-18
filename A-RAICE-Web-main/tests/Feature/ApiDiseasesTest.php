<?php

use App\Enums\TreatmentTypeEnum;
use App\Models\Disease;
use App\Models\Treatment;

test('anyone can view the disease list via GET /api/diseases', function () {
    $disease1 = Disease::factory()->create(['name' => 'Bacterial Leaf Blight']);
    $disease2 = Disease::factory()->create(['name' => 'Rice Blast']);

    Treatment::factory()->for($disease1)->count(2)->create();

    $response = $this->getJson('/api/diseases');

    $response->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'description',
                'causes',
                'symptoms',
                'prevention_tips',
                'image_path',
                'image_url',
                'treatments_count',
            ],
        ])
        ->assertJsonFragment(['name' => 'Bacterial Leaf Blight', 'treatments_count' => 2])
        ->assertJsonFragment(['name' => 'Rice Blast', 'treatments_count' => 0]);
});

test('disease list can be searched by keyword', function () {
    Disease::factory()->create(['name' => 'Sheath Blight']);
    Disease::factory()->create(['name' => 'Brown Spot']);

    $response = $this->getJson('/api/diseases?search=Sheath');

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Sheath Blight');
});

test('disease detail returns full information and ordered treatments', function () {
    $disease = Disease::factory()->create([
        'name' => 'Tungro Virus',
        'history' => 'First documented in Southeast Asia.',
        'sources' => 'IRRI Rice Knowledge Bank',
    ]);

    $t1 = Treatment::factory()->for($disease)->create([
        'title' => 'Zebra Control',
        'type' => TreatmentTypeEnum::Chemical,
    ]);

    $t2 = Treatment::factory()->for($disease)->create([
        'title' => 'Alpha Prevention',
        'type' => TreatmentTypeEnum::Cultural,
    ]);

    $response = $this->getJson("/api/diseases/{$disease->id}");

    $response->assertOk()
        ->assertJsonPath('id', $disease->id)
        ->assertJsonPath('name', 'Tungro Virus')
        ->assertJsonPath('history', 'First documented in Southeast Asia.')
        ->assertJsonPath('sources', 'IRRI Rice Knowledge Bank')
        ->assertJsonStructure([
            'treatments' => [
                '*' => ['id', 'title', 'description', 'type'],
            ],
        ]);

    expect($response->json('treatments'))->toHaveCount(2);
});
