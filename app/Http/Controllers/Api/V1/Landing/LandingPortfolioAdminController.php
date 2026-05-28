<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingProject;
use App\Models\LandingProjectAmenity;
use App\Models\LandingProjectImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mobile admin CRUD for landing portfolio projects.
 * Mirrors the web `LandingProjectController` behaviour.
 */
class LandingPortfolioAdminController extends LandingAdminBaseController
{
    private const IMAGE_DIR = 'landing/portfolio';

    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $projects = LandingProject::with(['images', 'amenities'])
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $projects->map(fn (LandingProject $p) => $this->transform($p, $lang))->values(),
                'meta' => ['total' => $projects->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load portfolio'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $project = LandingProject::with(['images', 'amenities'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($project, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Project not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request);
            $project = new LandingProject();
            $this->applyFields($project, $request);
            $project->save();

            $this->syncAmenities($project, $this->readAmenities($request));
            $this->storeUploadedImages($project, $request);

            $project->load(['images', 'amenities']);
            return response()->json([
                'success' => true,
                'data' => $this->transform($project, (string) $request->input('lang', 'en')),
                'message' => 'Portfolio project created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create project: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $project = LandingProject::findOrFail($id);
            $this->validateInput($request);
            $this->applyFields($project, $request);
            $project->save();

            if ($request->has('amenities')) {
                $this->syncAmenities($project, $this->readAmenities($request));
            }
            $this->storeUploadedImages($project, $request);

            $project->load(['images', 'amenities']);
            return response()->json([
                'success' => true,
                'data' => $this->transform($project, (string) $request->input('lang', 'en')),
                'message' => 'Portfolio project updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update project: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $project = LandingProject::with('images')->findOrFail($id);
            foreach ($project->images as $image) {
                $this->deleteStoredFile($image->file);
            }
            $project->delete(); // cascades amenities/images/likes via FK
            return response()->json(['success' => true, 'message' => 'Project deleted']);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio admin destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete project'], 500);
        }
    }

    public function deleteImage(int $imageId): JsonResponse
    {
        try {
            $image = LandingProjectImage::findOrFail($imageId);
            $projectId = $image->landing_project_id;
            $this->deleteStoredFile($image->file);
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary) {
                $next = LandingProjectImage::where('landing_project_id', $projectId)
                    ->orderBy('sort_order')->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
            return response()->json(['success' => true, 'message' => 'Image removed']);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio admin delete image error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove image'], 500);
        }
    }

    public function setPrimaryImage(int $imageId): JsonResponse
    {
        try {
            $image = LandingProjectImage::findOrFail($imageId);
            LandingProjectImage::where('landing_project_id', $image->landing_project_id)
                ->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
            return response()->json(['success' => true, 'message' => 'Primary image updated']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to set primary image'], 500);
        }
    }

    /** Bulk reorder: payload = [{id, sort_order}, ...] */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $items = (array) $request->input('items', []);
            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingProject::where('id', $id)->update(['sort_order' => $order]);
                }
            }
            return response()->json(['success' => true, 'message' => 'Order updated']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reorder'], 500);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price_tzs' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'youtube_url' => 'nullable|url|max:500',
            'model_3d_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    private function applyFields(LandingProject $project, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        $project->title = $this->mergeLocaleLang($project->title, $request->input('title'), $lang);
        $project->category = $this->mergeLocaleLang($project->category, $request->input('category'), $lang);
        $project->description = $this->mergeLocaleLang($project->description, $request->input('description'), $lang);
        $project->price_tzs = $request->filled('price_tzs') ? $request->input('price_tzs') : null;
        $project->price_usd = $request->filled('price_usd') ? $request->input('price_usd') : null;
        $project->youtube_url = $request->input('youtube_url') ?: null;
        $project->model_3d_url = $request->input('model_3d_url') ?: null;
        if ($request->has('sort_order')) {
            $project->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_featured')) {
            $project->is_featured = $request->boolean('is_featured');
        }
        if ($request->has('is_published')) {
            $project->is_published = $request->boolean('is_published');
        }
    }

    /** Accepts amenities[] as separate form fields, JSON, or comma string. */
    private function readAmenities(Request $request): array
    {
        $raw = $request->input('amenities', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        return array_values(array_filter(array_map('trim', (array) $raw), fn ($l) => $l !== ''));
    }

    private function syncAmenities(LandingProject $project, array $labels): void
    {
        $existingByEn = $project->amenities()->get()
            ->keyBy(fn ($a) => $a->label['en'] ?? null);
        $project->amenities()->delete();
        foreach ($labels as $i => $label) {
            $prior = $existingByEn->get($label);
            LandingProjectAmenity::create([
                'landing_project_id' => $project->id,
                'label' => $prior ? $prior->label : ['en' => $label],
                'sort_order' => $i,
            ]);
        }
    }

    private function storeUploadedImages(LandingProject $project, Request $request): void
    {
        if (!$request->hasFile('images')) {
            return;
        }
        $hasPrimary = $project->images()->where('is_primary', true)->exists();
        $nextOrder = (int) $project->images()->max('sort_order') + 1;

        foreach ((array) $request->file('images') as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }
            $url = $this->storeUploadedImage($file, self::IMAGE_DIR);
            if (!$url) {
                continue;
            }
            LandingProjectImage::create([
                'landing_project_id' => $project->id,
                'file' => $url,
                'file_name' => $file->getClientOriginalName(),
                'is_primary' => !$hasPrimary,
                'sort_order' => $nextOrder++,
            ]);
            $hasPrimary = true;
        }
    }

    private function transform(LandingProject $project, string $lang): array
    {
        $primary = $project->images->firstWhere('is_primary', true) ?? $project->images->first();
        return [
            'id' => $project->id,
            // Localized strings for display, plus the raw JSON for editing.
            'title' => LandingProject::localize($project->title, $lang),
            'title_i18n' => is_array($project->title) ? $project->title : ['en' => $project->title],
            'category' => LandingProject::localize($project->category, $lang),
            'category_i18n' => is_array($project->category) ? $project->category : ($project->category ? ['en' => $project->category] : null),
            'description' => LandingProject::localize($project->description, $lang),
            'description_i18n' => is_array($project->description) ? $project->description : ($project->description ? ['en' => $project->description] : null),
            'price_tzs' => $project->price_tzs !== null ? (float) $project->price_tzs : null,
            'price_usd' => $project->price_usd !== null ? (float) $project->price_usd : null,
            'youtube_url' => $project->youtube_url,
            'model_3d_url' => $project->model_3d_url,
            'likes_count' => (int) $project->likes_count,
            'is_featured' => (bool) $project->is_featured,
            'is_published' => (bool) $project->is_published,
            'sort_order' => (int) $project->sort_order,
            'image' => $primary?->file,
            'images' => $project->images->map(fn ($img) => [
                'id' => $img->id,
                'file' => $img->file,
                'is_primary' => (bool) $img->is_primary,
                'sort_order' => (int) $img->sort_order,
            ])->values(),
            'amenities' => $project->amenities->map(fn ($a) => [
                'id' => $a->id,
                'label' => LandingProject::localize($a->label, $lang),
                'label_i18n' => is_array($a->label) ? $a->label : ['en' => (string) $a->label],
                'sort_order' => (int) $a->sort_order,
            ])->values(),
            'created_at' => optional($project->created_at)->toIso8601String(),
            'updated_at' => optional($project->updated_at)->toIso8601String(),
        ];
    }
}
