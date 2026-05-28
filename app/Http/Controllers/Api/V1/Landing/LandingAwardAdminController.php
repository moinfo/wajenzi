<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingAward;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingAwardAdminController extends LandingAdminBaseController
{
    private const IMAGE_DIR = 'landing/awards';

    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $awards = LandingAward::orderBy('sort_order')->orderByDesc('year')->get();
            return response()->json([
                'success' => true,
                'data' => $awards->map(fn ($a) => $this->transform($a, $lang))->values(),
                'meta' => ['total' => $awards->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing award admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load awards'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $award = LandingAward::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($award, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Award not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $award = new LandingAward();
            $this->applyFields($award, $request);

            $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
            if ($url) {
                $award->image = $url;
            }
            if (!$award->image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['image' => ['An image is required for a new award.']],
                ], 422);
            }
            $award->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($award, (string) $request->input('lang', 'en')),
                'message' => 'Award created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing award admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create award: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $award = LandingAward::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($award, $request);

            if ($request->hasFile('image')) {
                $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
                if ($url) {
                    $this->deleteStoredFile($award->image);
                    $award->image = $url;
                }
            }
            $award->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($award, (string) $request->input('lang', 'en')),
                'message' => 'Award updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing award admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update award: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $award = LandingAward::findOrFail($id);
            $this->deleteStoredFile($award->image);
            $award->delete();
            return response()->json(['success' => true, 'message' => 'Award deleted']);
        } catch (\Throwable $e) {
            Log::error('Landing award admin destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete award'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingAward::where('id', $id)->update(['sort_order' => $order]);
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
            'year' => 'nullable|string|max:10',
            'subtitle' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ];
        $request->validate($rules);
    }

    private function applyFields(LandingAward $award, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        $award->year = $request->input('year');
        $award->title = $this->mergeLocaleLang($award->title, $request->input('title'), $lang);
        $award->subtitle = $this->mergeLocaleLang($award->subtitle, $request->input('subtitle'), $lang);
        $award->organization = $this->mergeLocaleLang($award->organization, $request->input('organization'), $lang);
        $award->description = $this->mergeLocaleLang($award->description, $request->input('description'), $lang);
        if ($request->has('sort_order')) {
            $award->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $award->is_published = $request->boolean('is_published');
        }
    }

    private function transform(LandingAward $award, string $lang): array
    {
        return [
            'id' => $award->id,
            'year' => $award->year,
            'title' => LandingAward::localize($award->title, $lang),
            'title_i18n' => is_array($award->title) ? $award->title : ['en' => $award->title],
            'subtitle' => LandingAward::localize($award->subtitle, $lang),
            'subtitle_i18n' => is_array($award->subtitle) ? $award->subtitle : ($award->subtitle ? ['en' => $award->subtitle] : null),
            'organization' => LandingAward::localize($award->organization, $lang),
            'organization_i18n' => is_array($award->organization) ? $award->organization : ($award->organization ? ['en' => $award->organization] : null),
            'description' => LandingAward::localize($award->description, $lang),
            'description_i18n' => is_array($award->description) ? $award->description : ($award->description ? ['en' => $award->description] : null),
            'image' => $award->image,
            'is_published' => (bool) $award->is_published,
            'sort_order' => (int) $award->sort_order,
            'created_at' => optional($award->created_at)->toIso8601String(),
            'updated_at' => optional($award->updated_at)->toIso8601String(),
        ];
    }
}
