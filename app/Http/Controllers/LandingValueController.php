<?php

namespace App\Http\Controllers;

use App\Models\LandingValue;
use Illuminate\Http\Request;

/**
 * Web portal admin for the mobile landing "Core Values" section.
 */
class LandingValueController extends Controller
{
    public function index()
    {
        $values = LandingValue::orderBy('sort_order')->orderBy('id')->get();
        return view('pages.landing.values', compact('values'));
    }

    public function store(Request $request)
    {
        $this->validateInput($request);
        $value = new LandingValue();
        $this->applyFields($value, $request);
        $value->save();

        $this->notify('Core value added successfully', 'Added!', 'success');
        return redirect()->route('landing_values');
    }

    public function update(Request $request, int $id)
    {
        $value = LandingValue::findOrFail($id);
        $this->validateInput($request);
        $this->applyFields($value, $request);
        $value->save();

        $this->notify('Core value updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_values');
    }

    public function destroy(int $id)
    {
        LandingValue::findOrFail($id)->delete();
        $this->notify('Core value deleted', 'Deleted!', 'success');
        return redirect()->route('landing_values');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function validateInput(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function applyFields(LandingValue $value, Request $request): void
    {
        $value->title = $this->mergeLocale($value->title, $request->input('title'));
        $value->description = $this->mergeLocale($value->description, $request->input('description'));
        $value->sort_order = (int) $request->input('sort_order', 0);
        $value->is_published = $request->boolean('is_published');
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
}
