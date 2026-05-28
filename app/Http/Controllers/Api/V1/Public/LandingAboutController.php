<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingAboutResource;
use App\Models\LandingAbout;
use App\Models\LandingTeamMember;
use App\Models\LandingValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** PUBLIC (unauthenticated) about endpoint for the landing screen. */
class LandingAboutController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $about = LandingAbout::query()->first();

            $values = LandingValue::where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $team = LandingTeamMember::where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => new LandingAboutResource([
                    'about' => $about,
                    'values' => $values,
                    'team' => $team,
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing about index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load about',
            ], 500);
        }
    }
}
