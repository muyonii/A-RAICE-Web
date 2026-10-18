<?php

namespace App\Models;

use App\Enums\TreatmentTypeEnum;
use Database\Factories\TreatmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TreatmentTypeEnum $type
 */
class Treatment extends Model
{
    /** @use HasFactory<TreatmentFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'type', 'disease_id'];

    protected function casts(): array
    {
        return [
            'type' => TreatmentTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Disease, $this>
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
