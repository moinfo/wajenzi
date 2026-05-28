<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingAwardResource;
use App\Models\LandingAward;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** PUBLIC (unauthenticated) awards endpoint for the landing screen. */
class LandingAwardController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $awards = LandingAward::where('is_published', true)
                ->orderBy('sort_order')
                ->orderByDesc('year')
                ->get();

            return response()->json([
                'success' => true,
                'data' => LandingAwardResource::collection($awards),
                'meta' => ['total' => $awards->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing awards index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load awards',
            ], 500);
        }
    }
}
