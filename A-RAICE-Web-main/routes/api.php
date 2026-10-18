<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\MunicipalityController;
use App\Http\Controllers\Api\OutbreakController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1');

Route::post('/auth/firebase', [AuthController::class, 'firebaseLogin'])
    ->middleware('throttle:10,1');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');

Route::get('/municipalities', [MunicipalityController::class, 'index']);
Route::get('/diseases', [DiseaseController::class, 'index']);
Route::get('/diseases/{disease}', [DiseaseController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authenticated API Routes (Sanctum Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::match(['patch', 'put'], '/user', [AuthController::class, 'updateProfile']);
    Route::match(['put', 'post'], '/user/password', [AuthController::class, 'updatePassword']);

    // Scans
    Route::post('/scans', [ScanController::class, 'store'])->middleware('throttle:scans');
    Route::get('/scans', [ScanController::class, 'index']);
    Route::get('/scans/{scan}', [ScanController::class, 'show']);

    // Alerts & Broadcasts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markRead']);
    Route::post('/alerts/broadcast', [AlertController::class, 'broadcast']);

    // Map
    Route::get('/map', [MapController::class, 'index']);

    // Reports (Admin & LGU Staff)
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/{report}', [ReportController::class, 'show']);

    // Outbreaks (Admin & LGU Staff)
    Route::get('/outbreaks', [OutbreakController::class, 'index']);
    Route::get('/outbreaks/{outbreak}', [OutbreakController::class, 'show']);
    Route::match(['patch', 'post'], '/outbreaks/{outbreak}/close', [OutbreakController::class, 'close']);

    // Push Notification Devices
    Route::post('/devices', [DeviceController::class, 'store']);
    Route::delete('/devices', [DeviceController::class, 'destroy']);
});
