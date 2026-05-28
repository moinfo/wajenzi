<?php

namespace App\Http\Controllers;

use App\Models\LandingStat;
use Illuminate\Http\Request;

/**
 * Web portal admin for the mobile landing hero stats.
 */
class LandingStatController extends Controller
{
    public function index()
    {
        $stats = LandingStat::orderBy('sort_order')->orderBy('id')->get();
        return view('pages.landing.stats', compact('stats'));
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $stat = new LandingStat();
        $this->applyFields($stat, $request);
        $stat->save();

        $this->notify('Stat added successfully', 'Added!', 'success');
        return redirect()->route('landing_stats');
    }

    public function update(Request $request, int $id)
    {
        $stat = LandingStat::findOrFail($id);
        $request->validate($this->rules());
        $this->applyFields($stat, $request);
        $stat->save();

        $this->notify('Stat updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_stats');
    }

    public function destroy(int $id)
    {
        LandingStat::findOrFail($id)->delete();
        $this->notify('Stat deleted', 'Deleted!', 'success');
        return redirect()->route('landing_stats');
    }

    private function rules(): array
    {
        return [
            'value' => 'required|string|max:20',
            'label' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    private function applyFields(LandingStat $stat, Request $request): void
    {
        $stat->value = $request->input('value');
        $existing = is_array($stat->label) ? $stat->label : [];
        $existing['en'] = $request->input('label');
        $stat->label = $existing;
        $stat->sort_order = (int) $request->input('sort_order', 0);
        $stat->is_published = $request->boolean('is_published');
    }
}
