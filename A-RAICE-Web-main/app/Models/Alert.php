<?php

namespace App\Models;

use App\Enums\SeverityEnum;
use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property SeverityEnum $severity
 */
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    protected $fillable = ['message', 'severity', 'outbreak_id'];

    protected function casts(): array
    {
        return [
            'severity' => SeverityEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Outbreak, $this>
     */
    public function outbreak(): BelongsTo
    {
        return $this->belongsTo(Outbreak::class);
    }

    /**
     * @return BelongsToMany<User, $this, AlertUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alert_user')
            ->withPivot('read_at')
            ->using(AlertUser::class);
    }
}
