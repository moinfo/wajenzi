<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\LandingProjectResource;
use App\Models\LandingProject;
use App\Models\LandingProjectLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PUBLIC (unauthenticated) portfolio endpoints for the mobile landing screen.
 * Only published rows are ever exposed here.
 */
class LandingPortfolioController extends Controller
{
    private function withVisitorLikes(Request $request, $query)
    {
        $deviceId = $request->query('device_id');

        $query->with(['images', 'amenities']);
        if ($deviceId) {
            $query->with(['likes' => fn ($q) => $q->where('device_id', $deviceId)]);
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = LandingProject::where('is_published', true)
                ->orderByDesc('is_featured')
                ->orderBy('sort_order')
                ->orderByDesc('created_at');

            $projects = $this->withVisitorLikes($request, $query)->get();

            return response()->json([
                'success' => true,
                'data' => LandingProjectResource::collection($projects),
                'meta' => ['total' => $projects->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load portfolio',
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $query = LandingProject::where('is_published', true)->where('id', $id);
            $project = $this->withVisitorLikes($request, $query)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new LandingProjectResource($project),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }
    }

    /**
     * Toggle a like for an anonymous visitor (keyed by device_id).
     * Returns the fresh like count and whether the visitor now likes it.
     */
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'device_id' => 'required|string|max:255',
            ]);
            $deviceId = $validated['device_id'];

            // 404 (not 500) when the project doesn't exist or isn't published.
            $project = LandingProject::where('is_published', true)->find($id);
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            // Atomic toggle: firstOrCreate avoids a check-then-act race on
            // rapid double-taps; a concurrent duplicate insert is treated as liked.
            try {
                $like = LandingProjectLike::firstOrCreate([
                    'landing_project_id' => $project->id,
                    'device_id' => $deviceId,
                ]);
                if ($like->wasRecentlyCreated) {
                    $liked = true;
                } else {
                    $like->delete();
                    $liked = false;
                }
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $liked = true; // lost the race to a concurrent like — it is liked
            }

            $count = $project->syncLikesCount();

            return response()->json([
                'success' => true,
                'data' => ['liked' => $liked, 'likes_count' => $count],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'device_id is required',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Landing portfolio like error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update like',
            ], 500);
        }
    }
}
