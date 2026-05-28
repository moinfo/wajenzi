<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingPoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingPosterAdminController extends LandingAdminBaseController
{
    private const IMAGE_DIR = 'landing/posters';

    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $items = LandingPoster::orderBy('sort_order')->orderByDesc('id')->get();
            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($p) => $this->transform($p, $lang))->values(),
                'meta' => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing poster admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load posters'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $poster = LandingPoster::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($poster, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Poster not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $poster = new LandingPoster();
            $this->applyFields($poster, $request);

            $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
            if ($url) {
                $poster->image = $url;
            }
            if (!$poster->image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['image' => ['An image is required for a poster.']],
                ], 422);
            }
            $poster->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($poster, (string) $request->input('lang', 'en')),
                'message' => 'Banner created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing poster admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create banner: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $poster = LandingPoster::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($poster, $request);

            if ($request->hasFile('image')) {
                $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
                if ($url) {
                    $this->deleteStoredFile($poster->image);
                    $poster->image = $url;
                }
            }
            $poster->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($poster, (string) $request->input('lang', 'en')),
                'message' => 'Banner updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing poster admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update banner: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $poster = LandingPoster::findOrFail($id);
            $this->deleteStoredFile($poster->image);
            $poster->delete();
            return response()->json(['success' => true, 'message' => 'Banner deleted']);
        } catch (\Throwable $e) {
            Log::error('Landing poster admin destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete banner'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingPoster::where('id', $id)->update(['sort_order' => $order]);
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
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link_url' => 'nullable|url|max:500',
            'youtube_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ];
        $request->validate($rules);
    }

    private function applyFields(LandingPoster $poster, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        $poster->title = $this->mergeLocaleLang($poster->title, $request->input('title'), $lang);
        $poster->subtitle = $this->mergeLocaleLang($poster->subtitle, $request->input('subtitle'), $lang);
        $poster->link_url = $request->input('link_url') ?: null;
        $poster->youtube_url = $request->input('youtube_url') ?: null;
        if ($request->has('sort_order')) {
            $poster->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $poster->is_published = $request->boolean('is_published');
        }
    }

    private function transform(LandingPoster $poster, string $lang): array
    {
        return [
            'id' => $poster->id,
            'title' => LandingPoster::localize($poster->title, $lang),
            'title_i18n' => is_array($poster->title) ? $poster->title : ($poster->title ? ['en' => $poster->title] : null),
            'subtitle' => LandingPoster::localize($poster->subtitle, $lang),
            'subtitle_i18n' => is_array($poster->subtitle) ? $poster->subtitle : ($poster->subtitle ? ['en' => $poster->subtitle] : null),
            'image' => $poster->image,
            'link_url' => $poster->link_url,
            'youtube_url' => $poster->youtube_url,
            'is_published' => (bool) $poster->is_published,
            'sort_order' => (int) $poster->sort_order,
            'created_at' => optional($poster->created_at)->toIso8601String(),
            'updated_at' => optional($poster->updated_at)->toIso8601String(),
        ];
    }
}
