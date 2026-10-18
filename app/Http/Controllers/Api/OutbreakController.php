<?php

namespace App\Http\Controllers\Api;

use App\Enums\OutbreakStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Outbreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OutbreakController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Outbreak::class);

        $query = Outbreak::query()
            ->with([
                'disease:id,name',
                'closedBy:id,name',
                'alerts' => fn ($q) => $q->latest('id'),
            ])
            ->withCount('scans')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', $request->query('municipality'));
        }

        if ($request->filled('disease_id')) {
            $query->where('disease_id', (int) $request->query('disease_id'));
        }

        $outbreaks = $query->paginate(15)->through(fn (Outbreak $o): array => [
            'id' => $o->id,
            'disease' => [
                'id' => $o->disease->id,
                'name' => $o->disease->name,
            ],
            'municipality' => $o->municipality->value,
            'status' => $o->status->value,
            'severity' => $o->alerts->first()?->severity->value,
            'scans_count' => $o->scans_count,
            'created_at' => $o->created_at,
            'closed_at' => $o->closed_at,
            'closed_by' => $o->closedBy === null ? null : [
                'id' => $o->closedBy->id,
                'name' => $o->closedBy->name,
            ],
        ]);

        return response()->json($outbreaks);
    }

    public function show(Outbreak $outbreak): JsonResponse
    {
        Gate::authorize('viewAny', Outbreak::class);

        $outbreak->load([
            'disease',
            'closedBy:id,name',
            'alerts' => fn ($q) => $q->latest('id'),
        ])->loadCount('scans');

        return response()->json([
            'id' => $outbreak->id,
            'disease' => [
                'id' => $outbreak->disease->id,
                'name' => $outbreak->disease->name,
            ],
            'municipality' => $outbreak->municipality->value,
            'status' => $outbreak->status->value,
            'severity' => $outbreak->alerts->first()?->severity->value,
            'scans_count' => $outbreak->scans_count,
            'created_at' => $outbreak->created_at,
            'closed_at' => $outbreak->closed_at,
            'closed_by' => $outbreak->closedBy === null ? null : [
                'id' => $outbreak->closedBy->id,
                'name' => $outbreak->closedBy->name,
            ],
        ]);
    }

    public function close(Request $request, Outbreak $outbreak): JsonResponse
    {
        Gate::authorize('close', $outbreak);

        abort_if(
            $outbreak->status === OutbreakStatusEnum::Resolved,
            409,
            'Outbreak is already resolved.',
        );

        $outbreak->update([
            'status' => OutbreakStatusEnum::Resolved,
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);

        $outbreak->load(['disease:id,name', 'closedBy:id,name']);

        return response()->json([
            'message' => 'Outbreak resolved successfully.',
            'outbreak' => [
                'id' => $outbreak->id,
                'disease' => $outbreak->disease->name,
                'municipality' => $outbreak->municipality->value,
                'status' => $outbreak->status->value,
                'closed_at' => $outbreak->closed_at,
                'closed_by' => $outbreak->closedBy?->name,
            ],
        ]);
    }
}
