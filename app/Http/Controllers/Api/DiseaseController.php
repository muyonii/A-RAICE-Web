<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiseaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Disease::query()
            ->withCount('treatments')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        $diseases = $query->get()->map(fn (Disease $disease): array => $this->presentDisease($disease, false));

        return response()->json($diseases);
    }

    public function show(Disease $disease): JsonResponse
    {
        $disease->load(['treatments' => fn ($query) => $query->orderBy('type')->orderBy('title')]);

        return response()->json($this->presentDisease($disease, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDisease(Disease $disease, bool $includeTreatments): array
    {
        $imageUrl = null;
        if (! empty($disease->image_path)) {
            $imageUrl = str_starts_with($disease->image_path, 'http')
                ? $disease->image_path
                : (Storage::disk('public')->exists($disease->image_path)
                    ? Storage::disk('public')->url($disease->image_path)
                    : asset($disease->image_path));
        }

        $data = [
            'id' => $disease->id,
            'name' => $disease->name,
            'description' => $disease->description,
            'causes' => $disease->causes,
            'symptoms' => $disease->symptoms,
            'prevention_tips' => $disease->prevention_tips,
            'image_path' => $disease->image_path,
            'image_url' => $imageUrl,
            'treatments_count' => $disease->treatments_count ?? $disease->treatments()->count(),
        ];

        if ($includeTreatments) {
            $data['history'] = $disease->history;
            $data['sources'] = $disease->sources;
            $data['treatments'] = $disease->treatments
                ->map(fn (Treatment $treatment): array => [
                    'id' => $treatment->id,
                    'title' => $treatment->title,
                    'description' => $treatment->description,
                    'type' => $treatment->type->value,
                ])
                ->values()
                ->all();
        }

        return $data;
    }
}
