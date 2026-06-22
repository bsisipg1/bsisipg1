<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LocationGalleryType;
use App\Http\Controllers\Controller;
use App\Models\AppHeroSection;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Settings', [
            'appDownloadUrl' => AppSetting::get(AppSetting::APP_DOWNLOAD_URL, AppSetting::DEFAULT_APP_DOWNLOAD_URL),
            'heroSections' => AppHeroSection::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (AppHeroSection $section) => $this->formatHeroSection($section)),
        ]);
    }

    public function updateAppSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_download_url' => ['nullable', 'url', 'max:2048'],
        ]);

        AppSetting::set(
            AppSetting::APP_DOWNLOAD_URL,
            $validated['app_download_url'] ?? null,
        );

        return redirect()
            ->route('admin.settings')
            ->with('success', 'App settings saved successfully.');
    }

    public function editHeroSection(AppHeroSection $heroSection): Response
    {
        return Inertia::render('admin/hero-sections/Edit', [
            'heroSection' => $this->formatHeroSection($heroSection),
        ]);
    }

    public function storeHeroSection(Request $request): RedirectResponse
    {
        $validated = $this->validateHeroSection($request);

        $mediaPath = $request->file('media')->store('app/hero', 'public');

        AppHeroSection::create([
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'type' => $this->resolveMediaType($request->file('media')),
            'media_path' => $mediaPath,
            'sort_order' => (int) AppHeroSection::query()->max('sort_order') + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Hero section added successfully.');
    }

    public function updateHeroSection(Request $request, AppHeroSection $heroSection): RedirectResponse
    {
        $validated = $this->validateHeroSection($request, updating: true);

        $payload = [
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('media')) {
            Storage::disk('public')->delete($heroSection->media_path);
            $payload['media_path'] = $request->file('media')->store('app/hero', 'public');
            $payload['type'] = $this->resolveMediaType($request->file('media'));
        }

        $heroSection->update($payload);

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Hero section updated successfully.');
    }

    public function destroyHeroSection(AppHeroSection $heroSection): RedirectResponse
    {
        Storage::disk('public')->delete($heroSection->media_path);
        $heroSection->delete();

        return redirect()
            ->route('admin.settings')
            ->with('success', 'Hero section removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatHeroSection(AppHeroSection $section): array
    {
        $data = $section->toApiArray();

        $data['is_active'] = $section->is_active;
        $data['created_at'] = $section->created_at?->format('M j, Y');
        $data['updated_at'] = $section->updated_at?->format('M j, Y');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateHeroSection(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'media' => [
                $updating ? 'nullable' : 'required',
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
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function resolveMediaType(UploadedFile $file): LocationGalleryType
    {
        return str_starts_with($file->getMimeType() ?? '', 'video/')
            ? LocationGalleryType::Video
            : LocationGalleryType::Image;
    }
}
