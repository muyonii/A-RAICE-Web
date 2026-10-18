<?php

use App\Enums\MunicipalityEnum;

test('anyone can fetch the list of Bataan municipalities', function () {
    $response = $this->getJson('/api/municipalities');

    $response->assertOk()
        ->assertJsonCount(count(MunicipalityEnum::cases()))
        ->assertJsonStructure([
            '*' => [
                'name',
                'slug',
                'value',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Balanga',
            'slug' => 'balanga',
            'value' => 'balanga',
        ]);
});

test('municipalities endpoint supports returning raw string values', function () {
    $response = $this->getJson('/api/municipalities?values_only=1');

    $response->assertOk()
        ->assertJsonCount(count(MunicipalityEnum::cases()))
        ->assertJson(array_column(MunicipalityEnum::cases(), 'value'));
});
