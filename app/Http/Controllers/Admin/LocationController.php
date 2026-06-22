<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LocationCategory;
use App\Enums\LocationGalleryType;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/locations/Index', [
            'locations' => Location::query()
                ->with('gallery')
                ->latest()
                ->get()
                ->map(fn (Location $location) => $this->formatLocation($location)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/locations/Create', [
            'categories' => LocationCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLocation($request);

        $imagePath = $request->file('image')->store('locations', 'public');

        $location = Location::create([
            ...$validated,
            'image' => $imagePath,
        ]);

        $this->storeGalleryItems($location, $request->file('gallery', []));

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function show(Location $location): Response
    {
        return Inertia::render('admin/locations/Show', [
            'location' => $this->formatLocation($location->load('gallery')),
        ]);
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('admin/locations/Edit', [
            'location' => $this->formatLocation($location->load('gallery')),
            'categories' => LocationCategory::options(),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $this->validateLocation($request, updating: true);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($location->image);
            $validated['image'] = $request->file('image')->store('locations', 'public');
        }

        $location->update($validated);

        $this->removeGalleryItems($location, $request->input('remove_gallery_ids', []));
        $this->storeGalleryItems($location, $request->file('gallery', []));

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        Storage::disk('public')->delete($location->image);

        foreach ($location->gallery as $galleryItem) {
            Storage::disk('public')->delete($galleryItem->path);
        }

        $location->delete();

        return redirect()
            ->route('admin.locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLocation(Location $location): array
    {
        $data = $location->toApiArray();

        $data['created_at'] = $location->created_at?->format('M j, Y');
        $data['updated_at'] = $location->updated_at?->format('M j, Y');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLocation(Request $request, bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', array_column(LocationCategory::options(), 'value'))],
            'description' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'image' => [$updating ? 'nullable' : 'required', 'image', 'max:5120'],
            'gallery' => ['nullable', 'array', 'max:20'],
            'gallery.*' => [
                'file',
                'mimetypes:'.implode(',', [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'video/mp4',
                    'video/quicktime',
                    'video/webm',
                ]),
                'max:51200',
            ],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer', 'exists:locations_gallery,id'],
        ];

        $validated = $request->validate($rules);
        unset($validated['image'], $validated['gallery'], $validated['remove_gallery_ids']);

        return $validated;
    }

    /**
     * @param  list<UploadedFile>|UploadedFile|null  $files
     */
    private function storeGalleryItems(Location $location, array|UploadedFile|null $files): void
    {
        if ($files === null) {
            return;
        }

        $files = is_array($files) ? $files : [$files];

        if ($files === []) {
            return;
        }

        $nextSortOrder = (int) $location->gallery()->max('sort_order') + 1;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $type = str_starts_with($file->getMimeType() ?? '', 'video/')
                ? LocationGalleryType::Video
                : LocationGalleryType::Image;

            $location->gallery()->create([
                'type' => $type,
                'path' => $file->store('locations/gallery', 'public'),
                'sort_order' => $nextSortOrder++,
            ]);
        }
    }

    /**
     * @param  list<int|string>|mixed  $galleryIds
     */
    private function removeGalleryItems(Location $location, mixed $galleryIds): void
    {
        if (! is_array($galleryIds) || $galleryIds === []) {
            return;
        }

        $items = $location->gallery()
            ->whereIn('id', $galleryIds)
            ->get();

        foreach ($items as $item) {
            Storage::disk('public')->delete($item->path);
            $item->delete();
        }
    }
}
