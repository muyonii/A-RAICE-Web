<?php

namespace App\Services;

use App\Enums\ScanStatusEnum;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateScan
{
    public function __construct(
        private readonly ViTService $vit,
        private readonly OutbreakDetectionService $outbreaks,
    ) {}

    public function create(
        User $user,
        FarmerProfile $farmer,
        UploadedFile $image,
        ?float $gpsLat,
        ?float $gpsLong,
    ): Scan {
        set_time_limit(180);

        $path = $image->store('scans', 'public');
        abort_unless(is_string($path), 500, 'Failed to store image.');

        $scan = Scan::create([
            'farmer_id' => $farmer->id,
            'uploaded_by_id' => $user->id,
            'image_url' => $path,
            'status' => ScanStatusEnum::Processing,
            'gps_lat' => $gpsLat ?? (float) $farmer->farm_lat,
            'gps_long' => $gpsLong ?? (float) $farmer->farm_long,
        ]);

        try {
            $result = $this->vit->predict(Storage::disk('public')->path($path));

            $scan->fill([
                'disease_id' => $result['disease']?->id,
                'confidence_score' => $result['confidence'],
                'raw_predictions' => $result['raw'],
                'status' => ScanStatusEnum::Completed,
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $scan->update(['status' => ScanStatusEnum::Failed]);
        }

        if ($scan->status === ScanStatusEnum::Completed) {
            try {
                $this->outbreaks->handle($scan);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $scan->load(['disease', 'outbreak']);
    }
}
