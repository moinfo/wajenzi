<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    private function resolveMenuUrl(?string $route): ?string
    {
        if (blank($route)) {
            return null;
        }

        try {
            return route($route);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $menus = Menu::with('children')
            ->whereNull('parent_id')
            ->where('status', 'ACTIVE')
            ->orderBy('list_order')
            ->get();

        $filtered = $menus->filter(fn($menu) => $user->can($menu->name))
            ->map(function ($menu) use ($user) {
                $children = $menu->children
                    ->filter(fn($child) => $user->can($child->name))
                    ->values()
                    ->map(fn($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'icon' => $child->icon,
                        'route' => $child->route,
                        'url' => $this->resolveMenuUrl($child->route),
                    ]);

                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'icon' => $menu->icon,
                    'route' => $menu->route,
                    'url' => $this->resolveMenuUrl($menu->route),
                    'children' => $children,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $filtered,
        ]);
    }
}
