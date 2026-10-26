<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The tiles of the web Settings page, filtered like the page does
 * (a tile is shown when the user has the permission named like the tile).
 */
class SettingsHubApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tiles = collect(SettingsController::settingsTiles())
            ->filter(fn ($tile) => $user->can($tile['name']))
            ->map(function ($tile) {
                try {
                    $path = route($tile['route'], [], false);
                } catch (\Throwable $e) {
                    $path = null;
                }

                return [
                    'name' => $tile['name'],
                    'route' => $tile['route'],
                    'path' => $path,
                    'icon' => $tile['icon'] ?? null,
                ];
            })
            ->filter(fn ($tile) => $tile['path'] !== null)
            ->values();

        return response()->json(['success' => true, 'data' => $tiles]);
    }
}
