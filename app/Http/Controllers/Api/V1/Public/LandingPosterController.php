<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingPosterResource;
use App\Models\LandingPoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** PUBLIC (unauthenticated) home-banner posters for the landing screen. */
class LandingPosterController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $posters = LandingPoster::where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => LandingPosterResource::collection($posters),
                'meta' => ['total' => $posters->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing posters index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load posters',
            ], 500);
        }
    }
}
