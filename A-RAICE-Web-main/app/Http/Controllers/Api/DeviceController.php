<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $validated['token']],
            ['platform' => $validated['platform'] ?? 'android'],
        );

        return response()->json([
            'message' => 'Device token registered successfully.',
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $request->user()->deviceTokens()
            ->where('token', $validated['token'])
            ->delete();

        return response()->json([
            'message' => 'Device token unregistered successfully.',
        ]);
    }
}
