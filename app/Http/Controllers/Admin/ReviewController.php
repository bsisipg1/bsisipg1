<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationRating;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(): Response
    {
        $appUserRatings = fn ($query) => $query
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser));

        return Inertia::render('admin/Reviews', [
            'locations' => Location::query()
                ->whereHas('ratings', $appUserRatings)
                ->withCount(['ratings' => $appUserRatings])
                ->withAvg(['ratings' => $appUserRatings], 'rating')
                ->with([
                    'ratings' => function ($query) use ($appUserRatings) {
                        $appUserRatings($query)
                            ->with('user:id,name,profile_photo')
                            ->latest();
                    },
                ])
                ->latest()
                ->get()
                ->map(fn (Location $location) => $this->formatLocation($location)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLocation(Location $location): array
    {
        $data = $location->toApiArray();

        $data['created_at'] = $location->created_at?->format('M j, Y');
        $data['reviews'] = $location->ratings
            ->map(fn (LocationRating $rating) => $rating->toAdminApiArray())
            ->values()
            ->all();

        return $data;
    }
}
