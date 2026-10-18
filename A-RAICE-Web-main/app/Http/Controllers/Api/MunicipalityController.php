<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeoLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    public function index(Request $request, GeoLocationService $geo): JsonResponse
    {
        $municipalities = $geo->getMunicipalities();

        if ($request->boolean('values_only')) {
            return response()->json(array_column($municipalities, 'value'));
        }

        return response()->json($municipalities);
    }
}
