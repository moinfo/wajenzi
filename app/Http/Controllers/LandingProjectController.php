<?php

namespace App\Http\Controllers;

use App\Models\LandingProject;
use App\Models\LandingProjectAmenity;
use App\Models\LandingProjectImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Web portal admin for the mobile landing "Our Portfolio" section.
 *
 * Uses dedicated actions (not the generic handleCrud) because portfolio items
 * carry a multi-image gallery, amenity chips, multilingual JSON fields, and
 * YouTube / 3D links — none of which the generic CRUD understands.
 */
class LandingProjectController extends Controller
{
    private const IMAGE_DIR = 'landing/portfolio'; // under storage/app/public

    public function index()
    {
        $projects = LandingProject::with(['images', 'amenities'])
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return view('pages.landing.portfolio', compact('projects'));
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        $project = new LandingProject();
        $this->applyFields($project, $request, $data);
        $project->save();

        $this->syncAmenities($project, $request->input('amenities', []));
        $this->storeUploadedImages($project, $request);

        $this->notify('Portfolio project added successfully', 'Added!', 'success');
        return redirect()->route('landing_portfolio');
    }

    public function update(Request $request, int $id)
    {
        $project = LandingProject::findOrFail($id);
        $data = $this->validateInput($request);

        $this->applyFields($project, $request, $data);
        $project->save();

        $this->syncAmenities($project, $request->input('amenities', []));
        $this->storeUploadedImages($project, $request);

        $this->notify('Portfolio project updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_portfolio');
    }

    public function destroy(int $id)
    {
        $project = LandingProject::with('images')->findOrFail($id);
        foreach ($project->images as $image) {
            $this->deleteStoredFile($image->file);
        }
        $project->delete(); // cascades amenities/images/likes via FK
        $this->notify('Portfolio project deleted', 'Deleted!', 'success');
        return redirect()->route('landing_portfolio');
    }

    public function deleteImage(int $imageId)
    {
        $image = LandingProjectImage::findOrFail($imageId);
        $projectId = $image->landing_project_id;
        $this->deleteStoredFile($image->file);
        $wasPrimary = $image->is_primary;
        $image->delete();

        // If we removed the primary image, promote the next one.
        if ($wasPrimary) {
            $next = LandingProjectImage::where('landing_project_id', $projectId)
                ->orderBy('sort_order')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        $this->notify('Image removed', 'Removed!', 'success');
        return back();
    }

    public function setPrimaryImage(int $imageId)
    {
        $image = LandingProjectImage::findOrFail($imageId);
        LandingProjectImage::where('landing_project_id', $image->landing_project_id)
            ->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        $this->notify('Primary image updated', 'Updated!', 'success');
        return back();
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price_tzs' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'youtube_url' => 'nullable|url|max:500',
            'model_3d_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'images.*' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    /**
     * Apply scalar + multilingual fields, preserving existing non-English
     * translations on the JSON columns (English-first editing).
     */
    private function applyFields(LandingProject $project, Request $request, array $data): void
    {
        $project->title = $this->mergeLocale($project->title, $data['title']);
        $project->category = $this->mergeLocale($project->category, $request->input('category'));
        $project->description = $this->mergeLocale($project->description, $request->input('description'));
        $project->price_tzs = $request->filled('price_tzs') ? $request->input('price_tzs') : null;
        $project->price_usd = $request->filled('price_usd') ? $request->input('price_usd') : null;
        $project->youtube_url = $request->input('youtube_url') ?: null;
        $project->model_3d_url = $request->input('model_3d_url') ?: null;
        $project->sort_order = (int) $request->input('sort_order', 0);
        $project->is_featured = $request->boolean('is_featured');
        $project->is_published = $request->boolean('is_published');
    }

    private function mergeLocale($existing, ?string $english): ?array
    {
        if ($english === null || $english === '') {
            return is_array($existing) ? $existing : null;
        }
        $map = is_array($existing) ? $existing : [];
        $map['en'] = $english;
        return $map;
    }

    /**
     * Rebuild amenity chips from the submitted English labels, reusing any
     * existing translation maps whose English label still matches.
     */
    private function syncAmenities(LandingProject $project, array $labels): void
    {
        $labels = array_values(array_filter(array_map('trim', $labels), fn ($l) => $l !== ''));

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

        foreach ($request->file('images') as $file) {
            if (!$file->isValid()) {
                continue;
            }
            // Safe, random server-generated filename (never trust the client name).
            $path = $file->store(self::IMAGE_DIR, 'public');

            $image = LandingProjectImage::create([
                'landing_project_id' => $project->id,
                'file' => '/storage/' . $path,
                'file_name' => $file->getClientOriginalName(),
                'is_primary' => !$hasPrimary,
                'sort_order' => $nextOrder++,
            ]);

            $hasPrimary = true; // first uploaded becomes primary when none existed
        }
    }

    private function deleteStoredFile(?string $publicPath): void
    {
        if (!$publicPath) {
            return;
        }
        // stored as /storage/landing/portfolio/x.png → public disk path landing/portfolio/x.png
        $relative = preg_replace('#^/storage/#', '', $publicPath);
        // Defense-in-depth: only ever delete files under the landing/ tree.
        if ($relative && str_starts_with($relative, 'landing/')
            && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
