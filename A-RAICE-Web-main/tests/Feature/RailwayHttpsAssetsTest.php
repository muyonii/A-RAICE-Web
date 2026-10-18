<?php

use Illuminate\Support\Facades\Route;

it('generates HTTPS asset URLs from Railway forwarded headers', function () {
    Route::get('/__test/railway-https', fn () => response()->json([
        'secure' => request()->isSecure(),
        'asset' => asset('build/assets/app.js'),
    ]));

    $response = $this->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'araice.up.railway.app',
    ])->get('/__test/railway-https');

    $response->assertOk();
    $response->assertJsonPath('secure', true);
    $response->assertJsonPath('asset', 'https://araice.up.railway.app/build/assets/app.js');
});
