<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use App\Models\Disease;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TreatmentController extends Controller
{
    public function store(StoreTreatmentRequest $request, Disease $disease): RedirectResponse
    {
        $disease->treatments()->create($request->validated());

        return to_route('admin.diseases.edit', $disease);
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        $treatment->update($request->validated());

        return to_route('admin.diseases.edit', $treatment->disease);
    }

    public function destroy(Treatment $treatment): RedirectResponse
    {
        Gate::authorize('delete', $treatment);

        $disease = $treatment->disease;
        $treatment->delete();

        return to_route('admin.diseases.edit', $disease);
    }
}
