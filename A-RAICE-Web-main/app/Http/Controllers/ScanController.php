<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreScanRequest;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Services\CreateScan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize('create', Scan::class);

        return Inertia::render('scans/create', [
            'farmers' => $request->user()->role !== UserRole::Farmer
                ? FarmerProfile::orderBy('full_name')->get(['id', 'full_name', 'barangay'])
                : [],
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Scan::class);

        $user = $request->user();

        $query = Scan::with('disease:id,name')->latest();

        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;
            abort_unless($profileId !== null, 403);

            $query->where('farmer_id', $profileId);
        } else {
            $query->with('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/index', [
            'scans' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(StoreScanRequest $request, CreateScan $createScan): RedirectResponse
    {
        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $validated = $request->validated();
        $profile = $user->role === UserRole::Farmer
            ? $user->farmerProfile
            : FarmerProfile::findOrFail((int) $validated['farmer_id']);

        abort_unless($profile !== null, 403);

        $scan = $createScan->create(
            $user,
            $profile,
            $request->file('image'),
            isset($validated['gps_lat']) ? (float) $validated['gps_lat'] : null,
            isset($validated['gps_long']) ? (float) $validated['gps_long'] : null,
        );

        return to_route('scans.show', $scan);
    }

    public function show(Request $request, Scan $scan): Response
    {
        Gate::authorize('view', $scan);

        $scan->load('disease.treatments');

        if ($request->user()->role !== UserRole::Farmer) {
            $scan->load('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/show', ['scan' => $scan]);
    }
}
