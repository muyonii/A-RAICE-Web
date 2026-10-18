<?php

namespace App\Http\Controllers;

use App\Services\MapDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request, MapDataService $mapData): Response
    {
        return Inertia::render('map/index', $mapData->forUser($request->user()));
    }
}
