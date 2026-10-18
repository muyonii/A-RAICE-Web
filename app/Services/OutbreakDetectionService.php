<?php

namespace App\Services;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OutbreakDetectionService
{
    private const THRESHOLD = 3;

    private const WINDOW_DAYS = 7;

    public function handle(Scan $scan): void
    {
        if (
            $scan->status !== ScanStatusEnum::Completed
            || $scan->disease_id === null
        ) {
            return;
        }

        $scan->loadMissing(['farmer', 'disease']);
        $municipality = $scan->farmer->municipality;

        DB::transaction(function () use ($scan, $municipality) {
            $qualifying = $this->qualifyingScans($scan, $municipality);
            $farmerCount = (clone $qualifying)->distinct()->count('farmer_id');

            $outbreak = Outbreak::where('disease_id', $scan->disease_id)
                ->where('municipality', $municipality)
                ->where('status', OutbreakStatusEnum::Active)
                ->lockForUpdate()
                ->first();

            if ($outbreak === null) {
                if ($farmerCount < self::THRESHOLD) {
                    return;
                }

                $outbreak = Outbreak::create([
                    'disease_id' => $scan->disease_id,
                    'municipality' => $municipality,
                    'status' => OutbreakStatusEnum::Active,
                ]);

                (clone $qualifying)
                    ->whereNull('outbreak_id')
                    ->update(['outbreak_id' => $outbreak->id]);
            } else {
                $scan->update(['outbreak_id' => $outbreak->id]);
            }

            $severity = SeverityEnum::fromFarmerCount($farmerCount);
            $latest = $outbreak->alerts()->latest('id')->first();

            if ($latest !== null && $this->rank($severity) <= $this->rank($latest->severity)) {
                return;
            }

            $alert = $outbreak->alerts()->create([
                'message' => sprintf(
                    '%s outbreak in %s: %d farmers affected in the last %d days.',
                    $scan->disease->name,
                    Str::headline($municipality->name),
                    $farmerCount,
                    self::WINDOW_DAYS,
                ),
                'severity' => $severity,
            ]);

            $recipientIds = array_unique(array_merge(
                FarmerProfile::where('municipality', $municipality)->pluck('user_id')->all(),
                User::query()->whereIn('role', [UserRole::LguStaff, UserRole::Admin])->pluck('id')->all(),
            ));

            $alert->users()->attach(array_values($recipientIds));
        });
    }

    /**
     * @return Builder<Scan>
     */
    private function qualifyingScans(Scan $scan, MunicipalityEnum $municipality): Builder
    {
        return Scan::query()
            ->where('disease_id', $scan->disease_id)
            ->where('status', ScanStatusEnum::Completed)
            ->where('scan_date', '>=', now()->subDays(self::WINDOW_DAYS))
            ->whereHas('farmer', fn (Builder $q) => $q->where('municipality', $municipality));
    }

    private function rank(SeverityEnum $severity): int
    {
        return (int) array_search($severity, SeverityEnum::cases(), true);
    }
}
