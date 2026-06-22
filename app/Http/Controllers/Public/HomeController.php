<?php

namespace App\Http\Controllers\Public;

use App\Enums\LocationCategory;
use App\Enums\LocationGalleryType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Event;
use App\Models\Location;
use App\Models\LocationRating;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $appUserRatings = fn ($query) => $query
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser));

        $locations = Location::query()
            ->with([
                'gallery' => fn ($query) => $query
                    ->where('type', LocationGalleryType::Image)
                    ->orderBy('sort_order'),
            ])
            ->withCount(['ratings' => $appUserRatings])
            ->withAvg(['ratings' => $appUserRatings], 'rating')
            ->latest()
            ->get()
            ->map(fn (Location $location) => $this->formatLocation($location));

        $usedCategories = $locations->pluck('category')->unique()->values()->all();

        $categories = collect([
            ['key' => 'all', 'label' => 'All'],
        ])->merge(
            collect(LocationCategory::options())
                ->filter(fn (array $option) => in_array($option['value'], $usedCategories, true))
                ->map(fn (array $option) => [
                    'key' => $option['value'],
                    'label' => $option['label'],
                ]),
        )->values()->all();

        return Inertia::render('public/home', [
            'appDownloadUrl' => AppSetting::appDownloadUrl(),
            'locations' => $locations->values()->all(),
            'categories' => $categories,
            'events' => Event::query()
                ->where('is_active', true)
                ->orderBy('event_date')
                ->orderBy('id')
                ->limit(6)
                ->get()
                ->map(fn (Event $event) => $event->toApiArray())
                ->values()
                ->all(),
            'reviewSummary' => $this->buildReviewSummary(),
            'recentReviews' => $this->buildRecentReviews(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLocation(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'map_label' => Str::words($location->name, 2, ''),
            'category' => $location->category->value,
            'category_label' => $location->category->label(),
            'description' => $location->description,
            'summary' => Str::limit($location->description, 140),
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'image_url' => $location->image_url,
            'gallery_images' => $location->gallery
                ->map(fn ($item) => $item->url)
                ->values()
                ->all(),
            'average_rating' => $location->ratings_avg_rating !== null
                ? round((float) $location->ratings_avg_rating, 1)
                : null,
            'ratings_count' => (int) $location->ratings_count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewSummary(): array
    {
        $query = LocationRating::query()
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser));

        $totalReviews = (clone $query)->count();

        if ($totalReviews === 0) {
            return [
                'average_rating' => null,
                'total_reviews' => 0,
                'breakdown' => collect(range(5, 1))
                    ->map(fn (int $stars) => [
                        'stars' => (string) $stars,
                        'count' => 0,
                        'percentage' => 0,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        $averageRating = round((float) (clone $query)->avg('rating'), 1);

        $breakdown = collect(range(5, 1))
            ->map(function (int $stars) use ($query, $totalReviews) {
                $count = (clone $query)->where('rating', $stars)->count();

                return [
                    'stars' => (string) $stars,
                    'count' => $count,
                    'percentage' => (int) round(($count / $totalReviews) * 100),
                ];
            })
            ->values()
            ->all();

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRecentReviews(): array
    {
        return LocationRating::query()
            ->whereHas('user', fn ($userQuery) => $userQuery->where('role', UserRole::AppUser))
            ->with(['user:id,name', 'location:id,name'])
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (LocationRating $rating) {
                $nameParts = preg_split('/\s+/', trim($rating->user->name)) ?: [];
                $initials = collect($nameParts)
                    ->take(2)
                    ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                    ->join('');

                return [
                    'name' => $rating->user->name,
                    'initials' => $initials !== '' ? $initials : 'U',
                    'age' => $rating->created_at?->diffForHumans() ?? '',
                    'rating' => $rating->rating,
                    'text' => $rating->comment,
                    'location_name' => $rating->location->name,
                ];
            })
            ->values()
            ->all();
    }
}
