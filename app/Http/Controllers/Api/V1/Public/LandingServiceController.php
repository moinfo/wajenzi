<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingServiceResource;
use App\Models\LandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** PUBLIC (unauthenticated) services endpoint for the landing screen. */
class LandingServiceController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $services = LandingService::where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => LandingServiceResource::collection($services),
                'meta' => ['total' => $services->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing services index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load services',
            ], 500);
        }
    }
}
