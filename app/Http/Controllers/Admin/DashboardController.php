<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationRating;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $appUserRatings = fn ($query) => $query
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser));

        $topRatedLocations = Location::query()
            ->whereHas('ratings', $appUserRatings)
            ->withCount(['ratings' => $appUserRatings])
            ->withAvg(['ratings' => $appUserRatings], 'rating')
            ->orderByDesc('ratings_avg_rating')
            ->orderByDesc('ratings_count')
            ->limit(8)
            ->get()
            ->map(fn (Location $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'average_rating' => round((float) $location->ratings_avg_rating, 1),
                'ratings_count' => (int) $location->ratings_count,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'locations_count' => Location::query()->count(),
                'app_users_count' => User::query()
                    ->where('role', UserRole::AppUser)
                    ->count(),
                'reviews_count' => LocationRating::query()
                    ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser))
                    ->count(),
            ],
            'topRatedLocations' => $topRatedLocations,
        ]);
    }
}
