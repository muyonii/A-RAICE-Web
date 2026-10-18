<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MapDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request, MapDataService $mapData): JsonResponse
    {
        $data = $mapData->forUser($request->user());

        return response()->json([
            'municipalities' => array_map(
                fn (array $status): array => [
                    'slug' => $status['municipality'],
                    'severity' => $status['severity'],
                ],
                $data['municipalityStatuses'],
            ),
            'outbreaks' => $data['outbreaks'],
            'farms' => $data['farms'],
        ]);
    }
}
