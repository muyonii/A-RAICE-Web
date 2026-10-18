<?php

namespace App\Http\Controllers\Api;

use App\Enums\MunicipalityEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts): JsonResponse
    {
        $page = $alerts->forUser($request->user(), $request->boolean('unread'))
            ->paginate(15)
            ->through(fn (Alert $alert): array => $alerts->present($alert, $alert->pivot->read_at));

        return response()->json($page);
    }

    public function markRead(Request $request, int $alert, AlertService $alerts): JsonResponse
    {
        $model = $alerts->markRead($request->user(), $alert);

        return response()->json($alerts->present($model, $model->pivot->read_at));
    }

    public function broadcast(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role === UserRole::Farmer, 403, 'Farmers are not authorized to broadcast alerts.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'severity' => ['sometimes', Rule::enum(SeverityEnum::class)],
            'municipality' => ['nullable', Rule::enum(MunicipalityEnum::class)],
            'outbreak_id' => ['nullable', 'exists:outbreaks,id'],
        ]);

        $severity = isset($validated['severity'])
            ? SeverityEnum::from($validated['severity'])
            : SeverityEnum::Medium;

        $alert = Alert::create([
            'message' => $validated['message'],
            'severity' => $severity,
            'outbreak_id' => $validated['outbreak_id'] ?? null,
            'municipality' => $validated['municipality'] ?? null,
        ]);

        $query = FarmerProfile::query();
        if (! empty($validated['municipality'])) {
            $query->where('municipality', $validated['municipality']);
        }
        $farmerUserIds = $query->pluck('user_id')->all();

        $staffIds = User::query()
            ->whereIn('role', [UserRole::LguStaff, UserRole::Admin])
            ->pluck('id')
            ->all();

        $recipientIds = array_values(array_unique(array_merge($farmerUserIds, $staffIds)));

        $alert->users()->attach($recipientIds);

        return response()->json([
            'message' => 'Broadcast alert sent successfully.',
            'alert' => [
                'id' => $alert->id,
                'message' => $alert->message,
                'severity' => $alert->severity->value,
                'outbreak_id' => $alert->outbreak_id,
                'municipality' => $validated['municipality'] ?? null,
                'recipients_count' => count($recipientIds),
                'created_at' => $alert->created_at,
            ],
        ], 201);
    }
}
