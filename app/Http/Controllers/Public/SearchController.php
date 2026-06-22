<?php

namespace App\Http\Controllers\Public;

use App\Enums\LocationGalleryType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\FormatsPublicLocations;
use App\Models\AppSetting;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    use FormatsPublicLocations;

    public function __invoke(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));

        $appUserRatings = fn ($ratingQuery) => $ratingQuery
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser));

        $locations = Location::query()
            ->with([
                'gallery' => fn ($galleryQuery) => $galleryQuery
                    ->where('type', LocationGalleryType::Image)
                    ->orderBy('sort_order'),
            ])
            ->withCount(['ratings' => $appUserRatings])
            ->withAvg(['ratings' => $appUserRatings], 'rating')
            ->latest()
            ->get()
            ->map(fn (Location $location) => $this->formatPublicLocation($location));

        $normalizedQuery = mb_strtolower($query);

        $results = $locations
            ->filter(function (array $location) use ($normalizedQuery) {
                if ($normalizedQuery === '') {
                    return false;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $location['name'],
                    $location['category_label'],
                    $location['summary'],
                    $location['description'],
                    $location['category'],
                ]));

                return str_contains($haystack, $normalizedQuery);
            })
            ->values()
            ->all();

        return Inertia::render('public/search', [
            'query' => $query,
            'results' => $results,
            'appDownloadUrl' => AppSetting::appDownloadUrl(),
        ]);
    }
}
