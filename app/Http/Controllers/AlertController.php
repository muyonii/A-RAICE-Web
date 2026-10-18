<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts): Response
    {
        $page = $alerts->forUser($request->user())
            ->paginate(15)
            ->through(fn (Alert $alert): array => $alerts->present($alert, $alert->pivot->read_at));

        return Inertia::render('alerts/index', ['alerts' => $page]);
    }

    public function show(Request $request, int $alert, AlertService $alerts): Response
    {
        $model = $alerts->markRead($request->user(), $alert);

        return Inertia::render('alerts/show', ['alert' => $alerts->present($model, $model->pivot->read_at)]);
    }
}
