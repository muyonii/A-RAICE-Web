<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use Database\Factories\OutbreakFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $disease_id
 * @property MunicipalityEnum $municipality
 * @property OutbreakStatusEnum $status
 * @property int|null $closed_by
 * @property Carbon|null $closed_at
 */
class Outbreak extends Model
{
    /** @use HasFactory<OutbreakFactory> */
    use HasFactory;

    protected $fillable = ['municipality', 'status', 'disease_id', 'closed_by', 'closed_at'];

    protected function casts(): array
    {
        return [
            'status' => OutbreakStatusEnum::class,
            'municipality' => MunicipalityEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Disease, $this>
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
