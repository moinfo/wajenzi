<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Efd;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EfdApiController extends Controller
{
    public function referenceData(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'systems' => System::orderBy('name')->get(['id', 'name'])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('EFD reference data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch EFD reference data',
                'data' => [
                    'systems' => [],
                ],
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $items = Efd::with('system:id,name')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Efd $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('EFD index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch EFDs',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'system_id' => 'required|exists:systems,id',
            ]);

            $item = Efd::create($validated)->load('system:id,name');

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'EFD created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('EFD store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create EFD: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Efd::with('system:id,name')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('EFD show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'EFD not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Efd::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'system_id' => 'required|exists:systems,id',
            ]);
            $item->update($validated);
            $item->load('system:id,name');

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh('system:id,name')),
                'message' => 'EFD updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('EFD update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update EFD: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Efd::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'EFD deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('EFD destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete EFD',
            ], 500);
        }
    }

    private function formatItem(Efd $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'system_id' => $item->system_id,
            'system_name' => $item->system?->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
