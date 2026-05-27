<?php

namespace App\Http\Controllers;

use App\Models\LandingAward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Web portal admin for the mobile landing "Awards" section.
 */
class LandingAwardController extends Controller
{
    private const IMAGE_DIR = 'landing/awards'; // under storage/app/public

    public function index()
    {
        $awards = LandingAward::orderBy('sort_order')->orderByDesc('year')->get();
        return view('pages.landing.awards', compact('awards'));
    }

    public function store(Request $request)
    {
        $this->validateInput($request);
        $award = new LandingAward();
        $this->applyFields($award, $request);
        $this->storeImage($award, $request);
        if (!$award->image) {
            return back()->withInput()->withErrors(['image' => 'An image is required for a new award.']);
        }
        $award->save();

        $this->notify('Award added successfully', 'Added!', 'success');
        return redirect()->route('landing_awards');
    }

    public function update(Request $request, int $id)
    {
        $award = LandingAward::findOrFail($id);
        $this->validateInput($request);
        $this->applyFields($award, $request);
        $this->storeImage($award, $request);
        $award->save();

        $this->notify('Award updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_awards');
    }

    public function destroy(int $id)
    {
        $award = LandingAward::findOrFail($id);
        $this->deleteStoredFile($award->image);
        $award->delete();
        $this->notify('Award deleted', 'Deleted!', 'success');
        return redirect()->route('landing_awards');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'nullable|string|max:10',
            'subtitle' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    private function applyFields(LandingAward $award, Request $request): void
    {
        $award->year = $request->input('year');
        $award->title = $this->mergeLocale($award->title, $request->input('title'));
        $award->subtitle = $this->mergeLocale($award->subtitle, $request->input('subtitle'));
        $award->organization = $this->mergeLocale($award->organization, $request->input('organization'));
        $award->description = $this->mergeLocale($award->description, $request->input('description'));
        $award->sort_order = (int) $request->input('sort_order', 0);
        $award->is_published = $request->boolean('is_published');
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

    private function storeImage(LandingAward $award, Request $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if (!$file->isValid()) {
            return;
        }
        $this->deleteStoredFile($award->image); // replace old image
        // Safe, random server-generated filename (never trust the client name).
        $path = $file->store(self::IMAGE_DIR, 'public');
        $award->image = '/storage/' . $path;
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
