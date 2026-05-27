<?php

namespace App\Http\Controllers;

use App\Models\LandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Web portal admin for the mobile landing "Services" section.
 */
class LandingServiceController extends Controller
{
    private const IMAGE_DIR = 'landing/services'; // under storage/app/public

    public function index()
    {
        $services = LandingService::orderBy('sort_order')->orderBy('id')->get();
        return view('pages.landing.services', compact('services'));
    }

    public function store(Request $request)
    {
        $this->validateInput($request);
        $service = new LandingService();
        $this->applyFields($service, $request);
        $this->storeImage($service, $request);
        $service->save();

        $this->notify('Service added successfully', 'Added!', 'success');
        return redirect()->route('landing_services');
    }

    public function update(Request $request, int $id)
    {
        $service = LandingService::findOrFail($id);
        $this->validateInput($request);
        $this->applyFields($service, $request);
        $this->storeImage($service, $request);
        $service->save();

        $this->notify('Service updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_services');
    }

    public function destroy(int $id)
    {
        $service = LandingService::findOrFail($id);
        $this->deleteStoredFile($service->image);
        $service->delete();
        $this->notify('Service deleted', 'Deleted!', 'success');
        return redirect()->route('landing_services');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    private function applyFields(LandingService $service, Request $request): void
    {
        $service->title = $this->mergeLocale($service->title, $request->input('title'));
        $service->short_description = $this->mergeLocale($service->short_description, $request->input('short_description'));
        $service->full_description = $this->mergeLocale($service->full_description, $request->input('full_description'));
        $service->features = array_values(array_filter(
            array_map('trim', $request->input('features', [])),
            fn ($f) => $f !== ''
        ));
        $service->sort_order = (int) $request->input('sort_order', 0);
        $service->is_published = $request->boolean('is_published');
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

    private function storeImage(LandingService $service, Request $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if (!$file->isValid()) {
            return;
        }
        $this->deleteStoredFile($service->image);
        // Safe, random server-generated filename (never trust the client name).
        $path = $file->store(self::IMAGE_DIR, 'public');
        $service->image = '/storage/' . $path;
    }

    private function deleteStoredFile(?string $publicPath): void
    {
        if (!$publicPath) {
            return;
        }
        $relative = preg_replace('#^/storage/#', '', $publicPath);
        // Defense-in-depth: only ever delete files under the landing/ tree.
        if ($relative && str_starts_with($relative, 'landing/')
            && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
