<?php

namespace App\Http\Controllers;

use App\Models\LandingAbout;
use Illuminate\Http\Request;

/**
 * Web portal admin for the mobile landing "About" section (singleton record).
 * Only an edit page + save — no create/delete.
 */
class LandingAboutController extends Controller
{
    public function index()
    {
        $about = LandingAbout::current();
        return view('pages.landing.about', compact('about'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'founded_year' => 'nullable|string|max:255',
            'tagline' => 'nullable|string',
            'story' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string',
        ]);

        $about = LandingAbout::current();
        $about->founded_year = $request->input('founded_year');
        $about->tagline = $this->mergeLocale($about->tagline, $request->input('tagline'));
        $about->story = $this->mergeLocale($about->story, $request->input('story'));
        $about->mission = $this->mergeLocale($about->mission, $request->input('mission'));
        $about->vision = $this->mergeLocale($about->vision, $request->input('vision'));
        $about->address = $request->input('address');
        $about->phone = $request->input('phone');
        $about->email = $request->input('email');
        $about->working_hours = $this->mergeLocale($about->working_hours, $request->input('working_hours'));
        $about->save();

        $this->notify('About section updated successfully', 'Updated!', 'success');
        return redirect()->route('landing_about');
    }

    /** Sets the English entry while preserving any other existing languages. */
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
