<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingStatAdminController extends LandingAdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $items = LandingStat::orderBy('sort_order')->orderBy('id')->get();
            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($s) => $this->transform($s, $lang))->values(),
                'meta' => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing stat admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load stats'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $stat = LandingStat::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($stat, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Stat not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $stat = new LandingStat();
            $this->applyFields($stat, $request);
            $stat->save();
            return response()->json([
                'success' => true,
                'data' => $this->transform($stat, (string) $request->input('lang', 'en')),
                'message' => 'Stat created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing stat admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create stat: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $stat = LandingStat::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($stat, $request);
            $stat->save();
            return response()->json([
                'success' => true,
                'data' => $this->transform($stat, (string) $request->input('lang', 'en')),
                'message' => 'Stat updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing stat admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update stat: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            LandingStat::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Stat deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete stat'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingStat::where('id', $id)->update(['sort_order' => $order]);
                }
            }
            return response()->json(['success' => true, 'message' => 'Order updated']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reorder'], 500);
        }
    }

    private function validateInput(Request $request, bool $isStore): void
    {
        $rules = [
            'value' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:50',
            'label' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ];
        $request->validate($rules);
    }

    private function applyFields(LandingStat $stat, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        if ($request->has('value')) {
            $stat->value = (string) $request->input('value');
        }
        $stat->label = $this->mergeLocaleLang($stat->label, $request->input('label'), $lang);
        if ($request->has('sort_order')) {
            $stat->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $stat->is_published = $request->boolean('is_published');
        }
    }

    private function transform(LandingStat $stat, string $lang): array
    {
        return [
            'id' => $stat->id,
            'value' => $stat->value,
            'label' => LandingStat::localize($stat->label, $lang),
            'label_i18n' => is_array($stat->label) ? $stat->label : ['en' => $stat->label],
            'is_published' => (bool) $stat->is_published,
            'sort_order' => (int) $stat->sort_order,
            'created_at' => optional($stat->created_at)->toIso8601String(),
            'updated_at' => optional($stat->updated_at)->toIso8601String(),
        ];
    }
}
