<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingStatResource;
use App\Models\LandingStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** PUBLIC (unauthenticated) hero stats for the landing screen. */
class LandingStatController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $stats = LandingStat::where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => LandingStatResource::collection($stats),
                'meta' => ['total' => $stats->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing stats index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load stats',
            ], 500);
        }
    }
}
