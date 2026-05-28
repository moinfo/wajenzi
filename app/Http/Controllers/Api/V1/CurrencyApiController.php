<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CurrencyApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Currency::query();
            if ($request->boolean('active_only')) {
                $query->where('is_active', true);
            }
            $items = $query->orderBy('code')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Currency $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Currency index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch currencies',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:100',
                'symbol'      => 'required|string|max:10',
                'code'        => 'nullable|string|max:10|unique:currencies,code',
                'rate_to_usd' => 'required|numeric|min:0.000001',
                'is_active'   => 'sometimes|boolean',
            ]);
            $item = Currency::create($validated);
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item),
                'message' => 'Currency created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Currency store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create currency: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem(Currency::findOrFail($id)),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Currency::findOrFail($id);
            $validated = $request->validate([
                'name'        => 'required|string|max:100',
                'symbol'      => 'required|string|max:10',
                'code'        => ['nullable', 'string', 'max:10', Rule::unique('currencies', 'code')->ignore($id)],
                'rate_to_usd' => 'required|numeric|min:0.000001',
                'is_active'   => 'sometimes|boolean',
            ]);
            $item->update($validated);
            return response()->json([
                'success' => true,
                'data'    => $this->formatItem($item->fresh()),
                'message' => 'Currency updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Currency update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Currency::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete currency',
            ], 500);
        }
    }

    private function formatItem(Currency $c): array
    {
        return [
            'id'          => $c->id,
            'name'        => $c->name,
            'symbol'      => $c->symbol,
            'code'        => $c->code,
            'rate_to_usd' => (float) $c->rate_to_usd,
            'is_active'   => (bool) $c->is_active,
            'created_at'  => optional($c->created_at)->format('Y-m-d H:i:s'),
            'updated_at'  => optional($c->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
