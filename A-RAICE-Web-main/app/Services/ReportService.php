<?php

namespace App\Services;

use App\Enums\ScanStatusEnum;
use App\Models\Disease;
use App\Models\Scan;
use Carbon\CarbonImmutable;

class ReportService
{
    /**
     * @return array{total_scans: int, confirmed_cases: int, per_disease: list<array{disease: string, count: int}>, top_disease: string|null}
     */
    public function generate(string $municipality, string $start, string $end, ?int $diseaseId = null): array
    {
        $scans = Scan::query()
            ->where('status', ScanStatusEnum::Completed)
            ->whereHas('farmer', fn ($query) => $query->where('municipality', $municipality))
            ->whereBetween('scan_date', [
                CarbonImmutable::parse($start)->startOfDay(),
                CarbonImmutable::parse($end)->endOfDay(),
            ])
            ->when($diseaseId, fn ($query) => $query->where('disease_id', $diseaseId));

        $counts = $scans->clone()
            ->whereNotNull('disease_id')
            ->selectRaw('disease_id, count(*) as total')
            ->groupBy('disease_id')
            ->orderByDesc('total')
            ->pluck('total', 'disease_id');

        $names = Disease::whereIn('id', $counts->keys())->pluck('name', 'id');

        $perDisease = array_values(
            $counts
                ->map(fn ($total, $id) => ['disease' => (string) $names[$id], 'count' => (int) $total])
                ->all()
        );

        return [
            'total_scans' => $scans->count(),
            'confirmed_cases' => (int) $counts->sum(),
            'per_disease' => $perDisease,
            'top_disease' => $perDisease[0]['disease'] ?? null,
        ];
    }
}
