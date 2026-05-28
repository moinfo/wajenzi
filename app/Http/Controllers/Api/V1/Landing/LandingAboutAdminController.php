<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingAbout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Singleton About record. Index returns the current record (creating an empty
 * one if needed), update saves it. No store/destroy.
 */
class LandingAboutAdminController extends LandingAdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $about = LandingAbout::current();
            return response()->json([
                'success' => true,
                'data' => $this->transform($about, $lang),
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing about admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load about'], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'founded_year' => 'nullable|string|max:255',
                'tagline' => 'nullable|string',
                'story' => 'nullable|string',
                'mission' => 'nullable|string',
                'vision' => 'nullable|string',
                'address' => 'nullable|string|max:500',
                'phone' => 'nullable|string|max:100',
                'email' => 'nullable|string|max:255',
                'working_hours' => 'nullable|string',
            ]);

            $lang = (string) $request->input('lang', 'en');
            $about = LandingAbout::current();

            if ($request->has('founded_year')) {
                $about->founded_year = $request->input('founded_year');
            }
            $about->tagline = $this->mergeLocaleLang($about->tagline, $request->input('tagline'), $lang);
            $about->story = $this->mergeLocaleLang($about->story, $request->input('story'), $lang);
            $about->mission = $this->mergeLocaleLang($about->mission, $request->input('mission'), $lang);
            $about->vision = $this->mergeLocaleLang($about->vision, $request->input('vision'), $lang);
            $about->working_hours = $this->mergeLocaleLang($about->working_hours, $request->input('working_hours'), $lang);

            if ($request->has('address')) {
                $about->address = $request->input('address');
            }
            if ($request->has('phone')) {
                $about->phone = $request->input('phone');
            }
            if ($request->has('email')) {
                $about->email = $request->input('email');
            }
            $about->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($about, $lang),
                'message' => 'About section updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing about admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update about: ' . $e->getMessage()], 500);
        }
    }

    private function transform(LandingAbout $about, string $lang): array
    {
        return [
            'id' => $about->id,
            'founded_year' => $about->founded_year,
            'tagline' => LandingAbout::localize($about->tagline, $lang),
            'tagline_i18n' => is_array($about->tagline) ? $about->tagline : ($about->tagline ? ['en' => $about->tagline] : null),
            'story' => LandingAbout::localize($about->story, $lang),
            'story_i18n' => is_array($about->story) ? $about->story : ($about->story ? ['en' => $about->story] : null),
            'mission' => LandingAbout::localize($about->mission, $lang),
            'mission_i18n' => is_array($about->mission) ? $about->mission : ($about->mission ? ['en' => $about->mission] : null),
            'vision' => LandingAbout::localize($about->vision, $lang),
            'vision_i18n' => is_array($about->vision) ? $about->vision : ($about->vision ? ['en' => $about->vision] : null),
            'address' => $about->address,
            'phone' => $about->phone,
            'email' => $about->email,
            'working_hours' => LandingAbout::localize($about->working_hours, $lang),
            'working_hours_i18n' => is_array($about->working_hours) ? $about->working_hours : ($about->working_hours ? ['en' => $about->working_hours] : null),
            'updated_at' => optional($about->updated_at)->toIso8601String(),
        ];
    }
}
