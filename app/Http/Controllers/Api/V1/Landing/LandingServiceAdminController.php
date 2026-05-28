<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingServiceAdminController extends LandingAdminBaseController
{
    private const IMAGE_DIR = 'landing/services';

    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $items = LandingService::orderBy('sort_order')->orderByDesc('id')->get();
            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($s) => $this->transform($s, $lang))->values(),
                'meta' => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing service admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load services'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $service = LandingService::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($service, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Service not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $service = new LandingService();
            $this->applyFields($service, $request);

            $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
            if ($url) {
                $service->image = $url;
            }
            $service->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($service, (string) $request->input('lang', 'en')),
                'message' => 'Service created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing service admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create service: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $service = LandingService::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($service, $request);

            if ($request->hasFile('image')) {
                $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
                if ($url) {
                    $this->deleteStoredFile($service->image);
                    $service->image = $url;
                }
            }
            $service->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($service, (string) $request->input('lang', 'en')),
                'message' => 'Service updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing service admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update service: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $service = LandingService::findOrFail($id);
            $this->deleteStoredFile($service->image);
            $service->delete();
            return response()->json(['success' => true, 'message' => 'Service deleted']);
        } catch (\Throwable $e) {
            Log::error('Landing service admin destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete service'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingService::where('id', $id)->update(['sort_order' => $order]);
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
            'title' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
            'short_description' => 'nullable|string|max:1000',
            'full_description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ];
        $request->validate($rules);
    }

    private function applyFields(LandingService $service, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        $service->title = $this->mergeLocaleLang($service->title, $request->input('title'), $lang);
        $service->short_description = $this->mergeLocaleLang($service->short_description, $request->input('short_description'), $lang);
        $service->full_description = $this->mergeLocaleLang($service->full_description, $request->input('full_description'), $lang);

        if ($request->has('features')) {
            $service->features = $this->readFeatures($request);
        }
        if ($request->has('sort_order')) {
            $service->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $service->is_published = $request->boolean('is_published');
        }
    }

    private function readFeatures(Request $request): array
    {
        $raw = $request->input('features', []);
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }
        return array_values(array_filter(array_map('trim', (array) $raw), fn ($l) => $l !== ''));
    }

    private function transform(LandingService $service, string $lang): array
    {
        return [
            'id' => $service->id,
            'title' => LandingService::localize($service->title, $lang),
            'title_i18n' => is_array($service->title) ? $service->title : ['en' => $service->title],
            'short_description' => LandingService::localize($service->short_description, $lang),
            'short_description_i18n' => is_array($service->short_description) ? $service->short_description : ($service->short_description ? ['en' => $service->short_description] : null),
            'full_description' => LandingService::localize($service->full_description, $lang),
            'full_description_i18n' => is_array($service->full_description) ? $service->full_description : ($service->full_description ? ['en' => $service->full_description] : null),
            'image' => $service->image,
            'features' => is_array($service->features) ? array_values($service->features) : [],
            'is_published' => (bool) $service->is_published,
            'sort_order' => (int) $service->sort_order,
            'created_at' => optional($service->created_at)->toIso8601String(),
            'updated_at' => optional($service->updated_at)->toIso8601String(),
        ];
    }
}
