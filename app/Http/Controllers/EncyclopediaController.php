<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use Inertia\Inertia;
use Inertia\Response;

class EncyclopediaController extends Controller
{
    public function index(): Response
    {
        $diseases = Disease::query()
            ->select(['id', 'name', 'description', 'image_path'])
            ->withCount('treatments')
            ->orderBy('name')
            ->get();

        return Inertia::render('encyclopedia/index', [
            'diseases' => $diseases,
        ]);
    }

    public function show(Disease $disease): Response
    {
        $disease->load(['treatments' => fn ($query) => $query->orderBy('type')]);

        return Inertia::render('encyclopedia/show', [
            'disease' => $disease,
        ]);
    }
}
