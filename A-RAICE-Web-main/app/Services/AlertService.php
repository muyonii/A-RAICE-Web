<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AlertService
{
    /**
     * @return BelongsToMany<Alert, User, AlertUser>
     */
    public function forUser(User $user, bool $unreadOnly = false): BelongsToMany
    {
        $alerts = $user->alerts()
            ->with('outbreak.disease')
            ->latest('alerts.created_at');

        if ($unreadOnly) {
            $alerts->wherePivotNull('read_at');
        }

        return $alerts;
    }

    /**
     * @return Alert&object{pivot: AlertUser}
     */
    public function markRead(User $user, int $alertId): Alert
    {
        $alert = $this->forUser($user)->findOrFail($alertId);

        if ($alert->pivot->read_at === null) {
            $user->alerts()->updateExistingPivot($alert->id, ['read_at' => now()]);
        }

        return $this->forUser($user)->findOrFail($alertId);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Alert $alert, ?string $readAt): array
    {
        return [
            'id' => $alert->id,
            'message' => $alert->message,
            'severity' => $alert->severity->value,
            'disease' => $alert->outbreak?->disease?->name,
            'municipality' => $alert->outbreak?->municipality?->value,
            'outbreak_id' => $alert->outbreak_id,
            'created_at' => $alert->created_at,
            'read_at' => $readAt,
        ];
    }
}
