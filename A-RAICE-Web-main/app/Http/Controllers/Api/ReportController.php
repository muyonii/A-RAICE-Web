<?php

namespace App\Http\Controllers\Api;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Report::class);

        $reports = Report::query()
            ->with('generatedBy:id,name')
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function store(Request $request, ReportService $service): JsonResponse
    {
        Gate::authorize('create', Report::class);

        $validated = $request->validate([
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'range_start' => ['required', 'date'],
            'range_end' => ['required', 'date', 'after_or_equal:range_start'],
            'disease_id' => ['nullable', 'exists:diseases,id'],
        ]);

        $summary = $service->generate(
            $validated['municipality'],
            $validated['range_start'],
            $validated['range_end'],
            isset($validated['disease_id']) ? (int) $validated['disease_id'] : null,
        );

        $report = Report::create([
            'generated_by' => $request->user()->id,
            'municipality' => $validated['municipality'],
            'range_start' => $validated['range_start'],
            'range_end' => $validated['range_end'],
            'summary_data' => $summary,
        ]);

        $report->load('generatedBy:id,name');

        return response()->json($report, 201);
    }

    public function show(Report $report): JsonResponse
    {
        Gate::authorize('view', $report);

        $report->load('generatedBy:id,name');

        return response()->json($report);
    }
}
