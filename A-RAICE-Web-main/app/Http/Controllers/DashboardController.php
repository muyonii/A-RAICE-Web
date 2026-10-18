<?php

namespace App\Http\Controllers;

use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->role !== UserRole::Farmer) {
            $recentScans = Scan::query()
                ->with(['disease:id,name', 'farmer:id,full_name'])
                ->latest('scan_date')
                ->limit(5)
                ->get([
                    'id',
                    'farmer_id',
                    'disease_id',
                    'status',
                    'confidence_score',
                    'scan_date',
                ])
                ->map(fn (Scan $scan) => [
                    'id' => $scan->id,
                    'status' => $scan->status->value,
                    'confidence_score' => $scan->confidence_score,
                    'scan_date' => $scan->scan_date,
                    'disease' => $scan->disease?->name,
                    'farmer_name' => $scan->farmer?->full_name,
                ]);

            $props = [
                'stats' => [
                    'active_outbreaks' => Outbreak::query()
                        ->where('status', OutbreakStatusEnum::Active)
                        ->count(),
                    'scans_last_7_days' => Scan::query()
                        ->where('scan_date', '>=', now()->subDays(7))
                        ->count(),
                ],
                'recent_scans' => $recentScans,
            ];

            if ($user->role === UserRole::Admin) {
                $props['admin_stats'] = [
                    'users_count' => User::count(),
                    'diseases_count' => Disease::count(),
                ];
            }

            return Inertia::render('Dashboard', $props);
        }

        $farmerProfile = $user->farmerProfile;
        abort_unless($farmerProfile !== null, 403);

        $scans = Scan::query()->where('farmer_id', $farmerProfile->id);

        $recentScans = Scan::query()
            ->with('disease:id,name')
            ->where('farmer_id', $farmerProfile->id)
            ->latest('scan_date')
            ->limit(5)
            ->get(['id', 'disease_id', 'status', 'confidence_score', 'scan_date'])
            ->map(fn (Scan $scan) => [
                'id' => $scan->id,
                'status' => $scan->status->value,
                'confidence_score' => $scan->confidence_score,
                'scan_date' => $scan->scan_date,
                'disease' => $scan->disease?->name,
            ]);

        $outbreak = Outbreak::query()
            ->with(['disease:id,name', 'alerts'])
            ->where('municipality', $farmerProfile->municipality)
            ->where('status', OutbreakStatusEnum::Active)
            ->first();

        return Inertia::render('Dashboard', [
            'scans_count' => $scans->count(),
            'recent_scans' => $recentScans,
            'active_outbreak' => $outbreak === null ? null : [
                'disease' => $outbreak->disease->name,
                'severity' => $outbreak->alerts
                    ->sortByDesc(fn ($alert) => $alert->severity->rank())
                    ->first()?->severity->value ?? 'low',
                'started_at' => $outbreak->created_at?->toDateString(),
            ],
        ]);
    }
}
