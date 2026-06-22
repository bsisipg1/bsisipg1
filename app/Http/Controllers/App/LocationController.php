<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->with('gallery')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->latest()
            ->get()
            ->map(fn (Location $location) => $location->toApiArray());

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function show(Location $location): JsonResponse
    {
        $location->load('gallery');
        $location->loadCount('ratings');
        $location->loadAvg('ratings', 'rating');

        return response()->json([
            'data' => $location->toApiArray(),
        ]);
    }
}
