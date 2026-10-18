<?php

namespace App\Models;

use App\Enums\ScanStatusEnum;
use Database\Factories\ScanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $farmer_id
 * @property int|null $disease_id
 * @property int|null $outbreak_id
 * @property ScanStatusEnum $status
 * @property Carbon|null $scan_date
 */
class Scan extends Model
{
    /** @use HasFactory<ScanFactory> */
    use HasFactory;

    protected $fillable = ['farmer_id', 'uploaded_by_id', 'disease_id',
        'outbreak_id', 'image_url', 'confidence_score', 'raw_predictions', 'status', 'gps_lat',
        'gps_long', 'scan_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScanStatusEnum::class,
            'raw_predictions' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FarmerProfile, $this>
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    /**
     * @return BelongsTo<Disease, $this>
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    /**
     * @return BelongsTo<Outbreak, $this>
     */
    public function outbreak(): BelongsTo
    {
        return $this->belongsTo(Outbreak::class);
    }
}
