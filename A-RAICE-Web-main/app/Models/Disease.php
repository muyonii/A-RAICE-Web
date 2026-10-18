<?php

namespace App\Models;

use Database\Factories\DiseaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    /** @use HasFactory<DiseaseFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'causes', 'symptoms', 'history', 'sources', 'prevention_tips', 'image_path'];

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * @return HasMany<Outbreak, $this>
     */
    public function outbreaks(): HasMany
    {
        return $this->hasMany(Outbreak::class);
    }

    /**
     * @return HasMany<Treatment, $this>
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
