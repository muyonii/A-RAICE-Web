<?php

namespace App\Services;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Carbon;

class MapDataService
{
    /**
     * @return array{
     *     outbreaks: array<int, array{id: int, disease: string, municipality: string, created_at: string|null, severity: string}>,
     *     municipalityStatuses: array<int, array{municipality: string, severity: string|null}>,
     *     ownMunicipality: string|null,
     *     farms: array<int, array<string, mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $isFarmer = $user->role === UserRole::Farmer;
        $isAdmin = $user->role === UserRole::Admin;
        $ownMunicipality = $isFarmer ? $user->farmerProfile?->municipality : null;

        $activeOutbreaks = Outbreak::query()
            ->select(['id', 'municipality'])
            ->where('status', OutbreakStatusEnum::Active)
            ->with('alerts:id,outbreak_id,severity')
            ->get()
            ->groupBy(fn (Outbreak $outbreak) => $outbreak->municipality->value)
            ->map(fn ($outbreaks) => $outbreaks
                ->flatMap(fn (Outbreak $outbreak) => $outbreak->alerts)
                ->sortByDesc(fn ($alert) => $alert->severity->rank())
                ->first()?->severity->value ?? 'low');

        $municipalityStatuses = collect(MunicipalityEnum::cases())
            ->map(fn (MunicipalityEnum $municipality) => [
                'municipality' => $municipality->value,
                'severity' => $activeOutbreaks->get($municipality->value),
            ])
            ->all();

        $outbreaks = Outbreak::query()
            ->with(['disease:id,name', 'alerts'])
            ->where('status', OutbreakStatusEnum::Active)
            ->when($isFarmer, fn ($query) => $query->where('municipality', $ownMunicipality))
            ->get()
            ->map(fn (Outbreak $outbreak) => [
                'id' => $outbreak->id,
                'disease' => $outbreak->disease->name,
                'municipality' => $outbreak->municipality->value,
                'created_at' => $outbreak->created_at?->toDateString(),
                'severity' => $outbreak->alerts
                    ->sortByDesc(fn ($alert) => $alert->severity->rank())
                    ->first()?->severity->value ?? 'low',
            ])
            ->all();

        if ($isFarmer) {
            $farmer = $user->farmerProfile;
            $farms = $farmer === null
                ? collect()
                : collect([$farmer->load([
                    'scans' => fn ($query) => $query
                        ->select(['id', 'farmer_id', 'disease_id', 'scan_date'])
                        ->with('disease:id,name')
                        ->latest('scan_date')
                        ->latest('id')
                        ->limit(1),
                ])]);
        } else {
            $farms = FarmerProfile::query()
                ->select([
                    'id',
                    'full_name',
                    'barangay',
                    'municipality',
                    'contact_number',
                    'farm_lat',
                    'farm_long',
                ])
                ->when(
                    $isAdmin,
                    fn ($query) => $query->whereNotNull('farm_lat')->whereNotNull('farm_long'),
                    fn ($query) => $query->whereHas(
                        'scans.outbreak',
                        fn ($outbreakQuery) => $outbreakQuery->where('status', OutbreakStatusEnum::Active),
                    ),
                )
                ->with([
                    'scans' => fn ($query) => $query
                        ->select(['id', 'farmer_id', 'disease_id', 'scan_date'])
                        ->when(
                            ! $isAdmin,
                            fn ($scanQuery) => $scanQuery->whereHas(
                                'outbreak',
                                fn ($outbreakQuery) => $outbreakQuery->where('status', OutbreakStatusEnum::Active),
                            ),
                        )
                        ->with('disease:id,name')
                        ->latest('scan_date')
                        ->latest('id')
                        ->limit(1),
                ])
                ->get();
        }

        $farms = $farms
            ->values()
            ->map(function (FarmerProfile $farm) use ($isFarmer) {
                /** @var Scan|null $scan */
                $scan = $farm->scans->first();

                return [
                    'id' => $farm->id,
                    ...($isFarmer ? [] : [
                        'name' => $farm->full_name,
                        'contact_number' => $farm->contact_number,
                    ]),
                    'barangay' => $farm->barangay,
                    'municipality' => $farm->municipality->value,
                    'lat' => (float) $farm->farm_lat,
                    'lng' => (float) $farm->farm_long,
                    'scan' => $scan === null ? null : [
                        'id' => $scan->id,
                        'disease' => $scan->disease?->name,
                        'scan_date' => $scan->scan_date === null
                            ? null
                            : Carbon::parse($scan->scan_date)->toDateString(),
                    ],
                ];
            })
            ->all();

        return [
            'outbreaks' => $outbreaks,
            'municipalityStatuses' => $municipalityStatuses,
            'ownMunicipality' => $ownMunicipality?->value,
            'farms' => $farms,
        ];
    }
}
