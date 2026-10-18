<?php

use App\Models\Alert;
use App\Models\User;

it('shows a user only their own alerts', function () {
    $farmer = User::factory()->create();
    $other = User::factory()->create();

    $mine = Alert::factory()->create();
    $theirs = Alert::factory()->create();
    $mine->users()->attach($farmer);
    $theirs->users()->attach($other);

    $this->actingAs($farmer)
        ->get(route('alerts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('alerts/index')
            ->has('alerts.data', 1)
            ->where('alerts.data.0.id', $mine->id));
});

it('returns 404 when opening someone else\'s alert', function () {
    $farmer = User::factory()->create();
    $theirs = Alert::factory()->create();
    $theirs->users()->attach(User::factory()->create());

    $this->actingAs($farmer)->get(route('alerts.show', $theirs))->assertNotFound();
});

it('marks an alert read once and keeps the first read time', function () {
    $farmer = User::factory()->create();
    $alert = Alert::factory()->create();
    $alert->users()->attach($farmer);

    $this->actingAs($farmer)->get(route('alerts.show', $alert))->assertOk();
    $first = $farmer->alerts()->first()->pivot->read_at;
    expect($first)->not->toBeNull();

    $this->travel(5)->minutes();
    $this->actingAs($farmer)->get(route('alerts.show', $alert))->assertOk();

    expect($farmer->alerts()->first()->pivot->read_at)->toEqual($first);
});
