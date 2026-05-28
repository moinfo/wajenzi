<?php

namespace App\Http\Controllers;

use App\Models\LandingPoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Web portal admin for the mobile home-screen banners (Posters).
 */
class LandingPosterController extends Controller
{
    private const IMAGE_DIR = 'landing/posters';

    public function index()
    {
        $posters = LandingPoster::orderBy('sort_order')->orderBy('id')->get();
        return view('pages.landing.posters', compact('posters'));
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(true));
        $poster = new LandingPoster();
        $this->applyFields($poster, $request);
        $this->storeImage($poster, $request);
        if (!$poster->image) {
            return back()->withErrors(['image' => 'An image is required.']);
        }
        $poster->save();

        $this->notify('Poster added successfully', 'Added!', 'success');
        return redirect()->route('landing_posters');
    }

    public function update(Request $request, int $id)
    {
        $poster = LandingPoster::findOrFail($id);
        $request->validate($this->rules(false));
        $this->applyFields($poster, $request);
        $this->storeImage($poster, $request);
        $poster->save();

        $this->notify('Poster updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_posters');
    }

    public function destroy(int $id)
    {
        $poster = LandingPoster::findOrFail($id);
        $this->deleteStoredFile($poster->image);
        $poster->delete();
        $this->notify('Poster deleted', 'Deleted!', 'success');
        return redirect()->route('landing_posters');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function rules(bool $creating): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link_url' => 'nullable|url|max:500',
            'youtube_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'image' => ($creating ? 'required' : 'nullable') . '|image|mimes:png,jpg,jpeg,webp|max:8192',
        ];
    }

    private function applyFields(LandingPoster $poster, Request $request): void
    {
        $poster->title = $this->mergeLocale($poster->title, $request->input('title'));
        $poster->subtitle = $this->mergeLocale($poster->subtitle, $request->input('subtitle'));
        $poster->link_url = $request->input('link_url') ?: null;
        $poster->youtube_url = $request->input('youtube_url') ?: null;
        $poster->sort_order = (int) $request->input('sort_order', 0);
        $poster->is_published = $request->boolean('is_published');
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

    private function storeImage(LandingPoster $poster, Request $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if (!$file->isValid()) {
            return;
        }
        $this->deleteStoredFile($poster->image);
        // Safe, random server-generated filename (never trust the client name).
        $path = $file->store(self::IMAGE_DIR, 'public');
        $poster->image = '/storage/' . $path;
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
