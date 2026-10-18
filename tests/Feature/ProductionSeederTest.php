<?php

use App\Models\Disease;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('does not create the development test account in production', function () {
    app()->instance('env', 'production');

    (new DatabaseSeeder)->run();

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});

it('does not overwrite an existing disease catalog in production', function () {
    app()->instance('env', 'production');

    Disease::create([
        'name' => 'Custom disease',
        'description' => 'A custom description.',
        'prevention_tips' => 'Custom prevention guidance.',
    ]);

    (new DatabaseSeeder)->run();

    expect(Disease::query()->count())->toBe(1);
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});
