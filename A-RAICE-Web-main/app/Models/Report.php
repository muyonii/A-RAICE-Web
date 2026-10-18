<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = ['summary_data', 'municipality', 'range_start', 'range_end', 'generated_by'];

    protected function casts(): array
    {
        return [
            'municipality' => MunicipalityEnum::class,
            'summary_data' => 'array',
            'range_start' => 'date',
            'range_end' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
