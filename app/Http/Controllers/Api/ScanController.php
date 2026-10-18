<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScanRequest;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\Treatment;
use App\Services\CreateScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Scan::class);

        $user = $request->user();
        $query = Scan::query()
            ->with([
                'disease:id,name',
                'farmer:id,full_name,contact_number',
                'outbreak:id,municipality,status',
            ])
            ->latest();

        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;
            abort_unless($profileId !== null, 403);

            $query->where('farmer_id', $profileId);
        } else {
            if ($request->filled('farmer_id')) {
                $query->where('farmer_id', (int) $request->query('farmer_id'));
            }

            if ($request->filled('municipality')) {
                $municipality = $request->query('municipality');
                $query->whereHas('farmer', fn ($q) => $q->where('municipality', $municipality));
            }

            if ($request->filled('disease_id')) {
                $query->where('disease_id', (int) $request->query('disease_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }
        }

        $scans = $query->paginate(15)->through(
            fn (Scan $scan): array => $this->scanData(
                $scan,
                $user->role !== UserRole::Farmer,
                false,
                false,
            ),
        );

        return response()->json($scans);
    }

    public function store(StoreScanRequest $request, CreateScan $createScan): JsonResponse
    {
        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $validated = $request->validated();
        $farmer = $user->role === UserRole::Farmer
            ? $user->farmerProfile
            : FarmerProfile::findOrFail((int) $validated['farmer_id']);

        abort_unless($farmer !== null, 403);

        $scan = $createScan->create(
            $user,
            $farmer,
            $request->file('image'),
            isset($validated['gps_lat']) ? (float) $validated['gps_lat'] : null,
            isset($validated['gps_long']) ? (float) $validated['gps_long'] : null,
        );

        $scan->loadMissing([
            'disease.treatments' => fn ($query) => $query->orderBy('title'),
            'outbreak',
        ]);

        return response()->json($this->scanData($scan, false, true, false), 201);
    }

    public function show(Scan $scan): JsonResponse
    {
        Gate::authorize('view', $scan);

        $scan->load([
            'disease.treatments' => fn ($query) => $query->orderBy('title'),
            'outbreak',
        ]);

        return response()->json($this->scanData($scan, false, true, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function scanData(
        Scan $scan,
        bool $includeFarmer,
        bool $includeDiseaseDetails,
        bool $includeOutbreak,
    ): array {
        $imageUrl = $scan->image_url ? Storage::disk('public')->url($scan->image_url) : null;

        $scanDate = $scan->scan_date ?? $scan->created_at;

        $data = [
            'id' => $scan->id,
            'status' => $scan->status->value,
            'image_url' => $imageUrl,
            'disease' => $scan->disease === null ? null : [
                'id' => $scan->disease->id,
                'name' => $scan->disease->name,
                ...($includeDiseaseDetails ? [
                    'description' => $scan->disease->description,
                    'causes' => $scan->disease->causes,
                    'symptoms' => $scan->disease->symptoms,
                    'prevention_tips' => $scan->disease->prevention_tips,
                    'treatments' => $scan->disease->treatments
                        ->map(fn (Treatment $treatment): array => [
                            'id' => $treatment->id,
                            'title' => $treatment->title,
                            'description' => $treatment->description,
                            'type' => $treatment->type->value,
                        ])
                        ->values()
                        ->all(),
                ] : []),
            ],
            'confidence' => $scan->confidence_score === null
                ? null
                : (float) $scan->confidence_score,
            'top_3' => $this->extractTop3($scan),
            'gps_lat' => (float) $scan->gps_lat,
            'gps_lng' => (float) $scan->gps_long,
            'outbreak_id' => $scan->outbreak_id,
            'created_at' => $scanDate?->toIso8601String(),
        ];

        if ($includeFarmer) {
            $data['farmer'] = [
                'id' => $scan->farmer->id,
                'name' => $scan->farmer->full_name,
                'contact_number' => $scan->farmer->contact_number,
            ];
        }

        if ($includeOutbreak) {
            $data['outbreak'] = $scan->outbreak === null ? null : [
                'id' => $scan->outbreak->id,
                'municipality' => $scan->outbreak->municipality->value,
                'status' => $scan->outbreak->status->value,
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array{disease_id: int, disease_name: string, confidence: ?float}>
     */
    private function extractTop3(Scan $scan): array
    {
        $items = [];
        $raw = $scan->raw_predictions;

        if (is_array($raw)) {
            $candidates = $raw['data']['top_predictions']
                ?? $raw['top_predictions']
                ?? $raw['data']['top_3']
                ?? $raw['top_3']
                ?? $raw['data']['predictions']
                ?? $raw['predictions']
                ?? null;

            if (is_array($candidates)) {
                $diseases = Disease::all();

                foreach ($candidates as $candidate) {
                    if (! is_array($candidate)) {
                        continue;
                    }

                    $className = $candidate['class_name'] ?? $candidate['disease'] ?? $candidate['name'] ?? null;
                    if (! $className) {
                        continue;
                    }

                    $matched = $diseases->first(
                        fn (Disease $d) => Str::slug($d->name, '_') === $className || Str::slug($d->name, '_') === Str::slug((string) $className, '_')
                    );

                    if (! $matched) {
                        continue;
                    }

                    $conf = $candidate['confidence'] ?? null;
                    $items[] = [
                        'disease_id' => $matched->id,
                        'disease_name' => $matched->name,
                        'confidence' => $conf !== null ? round(((float) $conf) / 100, 4) : null,
                    ];

                    if (count($items) === 3) {
                        break;
                    }
                }
            }
        }

        if (empty($items) && ($scan->disease !== null || $scan->disease_id !== null)) {
            $disease = $scan->disease ?? Disease::find($scan->disease_id);
            if ($disease !== null) {
                $items[] = [
                    'disease_id' => $disease->id,
                    'disease_name' => $disease->name,
                    'confidence' => $scan->confidence_score !== null ? (float) $scan->confidence_score : null,
                ];
            }
        }

        return $items;
    }
}
