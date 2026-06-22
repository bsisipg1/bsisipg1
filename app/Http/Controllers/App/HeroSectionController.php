<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AppHeroSection;
use Illuminate\Http\JsonResponse;

class HeroSectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = AppHeroSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (AppHeroSection $section) => $section->toApiArray());

        return response()->json([
            'data' => $sections,
        ]);
    }
}
