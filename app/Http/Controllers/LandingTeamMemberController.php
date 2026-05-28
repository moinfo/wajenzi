<?php

namespace App\Http\Controllers;

use App\Models\LandingTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Web portal admin for the mobile landing "Leadership Team" section.
 */
class LandingTeamMemberController extends Controller
{
    private const IMAGE_DIR = 'landing/team'; // under storage/app/public

    public function index()
    {
        $members = LandingTeamMember::orderBy('sort_order')->orderBy('id')->get();
        return view('pages.landing.team', compact('members'));
    }

    public function store(Request $request)
    {
        $this->validateInput($request);
        $member = new LandingTeamMember();
        $this->applyFields($member, $request);
        $this->storeImage($member, $request);
        $member->save();

        $this->notify('Team member added successfully', 'Added!', 'success');
        return redirect()->route('landing_team');
    }

    public function update(Request $request, int $id)
    {
        $member = LandingTeamMember::findOrFail($id);
        $this->validateInput($request);
        $this->applyFields($member, $request);
        $this->storeImage($member, $request);
        $member->save();

        $this->notify('Team member updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_team');
    }

    public function destroy(int $id)
    {
        $member = LandingTeamMember::findOrFail($id);
        $this->deleteStoredFile($member->image);
        $member->delete();
        $this->notify('Team member deleted', 'Deleted!', 'success');
        return redirect()->route('landing_team');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    private function applyFields(LandingTeamMember $member, Request $request): void
    {
        $member->name = $request->input('name');
        $member->role = $this->mergeLocale($member->role, $request->input('role'));
        $member->bio = $this->mergeLocale($member->bio, $request->input('bio'));
        $member->sort_order = (int) $request->input('sort_order', 0);
        $member->is_published = $request->boolean('is_published');
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

    private function storeImage(LandingTeamMember $member, Request $request): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $file = $request->file('image');
        if (!$file->isValid()) {
            return;
        }
        $this->deleteStoredFile($member->image);
        // Safe, random server-generated filename (never trust the client name).
        $path = $file->store(self::IMAGE_DIR, 'public');
        $member->image = '/storage/' . $path;
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
