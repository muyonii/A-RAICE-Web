<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use Database\Factories\FarmerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property MunicipalityEnum $municipality
 */
class FarmerProfile extends Model
{
    /** @use HasFactory<FarmerProfileFactory> */
    use HasFactory;

    protected $fillable = ['full_name', 'barangay', 'municipality', 'contact_number',
        'farm_lat', 'farm_long'];

    protected function casts(): array
    {
        return [
            'municipality' => MunicipalityEnum::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class, 'farmer_id');
    }
}
