<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiseaseRequest;
use App\Http\Requests\UpdateDiseaseRequest;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DiseaseController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Disease::class);

        return Inertia::render('admin/diseases/create');
    }

    public function store(StoreDiseaseRequest $request): RedirectResponse
    {
        $disease = Disease::create($request->validated());

        return to_route('encyclopedia.show', $disease)
            ->with('success', 'Disease added.');
    }

    public function edit(Disease $disease): Response
    {
        Gate::authorize('update', $disease);

        return Inertia::render('admin/diseases/edit', [
            'disease' => $disease->load([
                'treatments' => fn (HasMany $query) => $query->orderBy('title'),
            ]),
        ]);
    }

    public function update(UpdateDiseaseRequest $request, Disease $disease): RedirectResponse
    {
        $disease->update($request->validated());

        return to_route('encyclopedia.show', $disease)
            ->with('success', 'Disease updated.');
    }

    public function destroy(Disease $disease): RedirectResponse
    {
        Gate::authorize('delete', $disease);

        if (Scan::where('disease_id', $disease->id)->exists()
            || Outbreak::where('disease_id', $disease->id)->exists()) {
            return back()->withErrors([
                'delete' => 'This disease has scans or outbreaks linked to it and cannot be deleted.',
            ]);
        }

        $disease->delete();

        return to_route('encyclopedia.index')
            ->with('success', 'Disease deleted.');
    }
}
