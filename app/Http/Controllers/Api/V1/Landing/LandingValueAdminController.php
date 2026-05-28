<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingValueAdminController extends LandingAdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $items = LandingValue::orderBy('sort_order')->orderBy('id')->get();
            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($v) => $this->transform($v, $lang))->values(),
                'meta' => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing value admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load values'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $value = LandingValue::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($value, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Value not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $value = new LandingValue();
            $this->applyFields($value, $request);
            $value->save();
            return response()->json([
                'success' => true,
                'data' => $this->transform($value, (string) $request->input('lang', 'en')),
                'message' => 'Core value created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing value admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create value: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $value = LandingValue::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($value, $request);
            $value->save();
            return response()->json([
                'success' => true,
                'data' => $this->transform($value, (string) $request->input('lang', 'en')),
                'message' => 'Core value updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing value admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update value: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            LandingValue::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Core value deleted']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete value'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingValue::where('id', $id)->update(['sort_order' => $order]);
                }
            }
            return response()->json(['success' => true, 'message' => 'Order updated']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reorder'], 500);
        }
    }

    private function validateInput(Request $request, bool $isStore): void
    {
        $request->validate([
            'title' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);
    }

    private function applyFields(LandingValue $value, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        $value->title = $this->mergeLocaleLang($value->title, $request->input('title'), $lang);
        $value->description = $this->mergeLocaleLang($value->description, $request->input('description'), $lang);
        if ($request->has('sort_order')) {
            $value->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $value->is_published = $request->boolean('is_published');
        }
    }

    private function transform(LandingValue $value, string $lang): array
    {
        return [
            'id' => $value->id,
            'title' => LandingValue::localize($value->title, $lang),
            'title_i18n' => is_array($value->title) ? $value->title : ['en' => $value->title],
            'description' => LandingValue::localize($value->description, $lang),
            'description_i18n' => is_array($value->description) ? $value->description : ($value->description ? ['en' => $value->description] : null),
            'is_published' => (bool) $value->is_published,
            'sort_order' => (int) $value->sort_order,
            'created_at' => optional($value->created_at)->toIso8601String(),
            'updated_at' => optional($value->updated_at)->toIso8601String(),
        ];
    }
}
