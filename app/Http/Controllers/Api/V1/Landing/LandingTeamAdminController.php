<?php

namespace App\Http\Controllers\Api\V1\Landing;

use App\Models\LandingTeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LandingTeamAdminController extends LandingAdminBaseController
{
    private const IMAGE_DIR = 'landing/team';

    public function index(Request $request): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $items = LandingTeamMember::orderBy('sort_order')->orderBy('id')->get();
            return response()->json([
                'success' => true,
                'data' => $items->map(fn ($t) => $this->transform($t, $lang))->values(),
                'meta' => ['total' => $items->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('Landing team admin index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load team'], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $lang = (string) $request->query('lang', 'en');
            $member = LandingTeamMember::findOrFail($id);
            return response()->json(['success' => true, 'data' => $this->transform($member, $lang)]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Team member not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->validateInput($request, true);
            $member = new LandingTeamMember();
            $this->applyFields($member, $request);

            $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
            if ($url) {
                $member->image = $url;
            }
            $member->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($member, (string) $request->input('lang', 'en')),
                'message' => 'Team member created',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing team admin store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create team member: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $member = LandingTeamMember::findOrFail($id);
            $this->validateInput($request, false);
            $this->applyFields($member, $request);

            if ($request->hasFile('image')) {
                $url = $this->storeUploadedImage($request->file('image'), self::IMAGE_DIR);
                if ($url) {
                    $this->deleteStoredFile($member->image);
                    $member->image = $url;
                }
            }
            $member->save();

            return response()->json([
                'success' => true,
                'data' => $this->transform($member, (string) $request->input('lang', 'en')),
                'message' => 'Team member updated',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Landing team admin update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update team member: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $member = LandingTeamMember::findOrFail($id);
            $this->deleteStoredFile($member->image);
            $member->delete();
            return response()->json(['success' => true, 'message' => 'Team member deleted']);
        } catch (\Throwable $e) {
            Log::error('Landing team admin destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete team member'], 500);
        }
    }

    public function reorder(Request $request): JsonResponse
    {
        try {
            foreach ((array) $request->input('items', []) as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['sort_order'] ?? 0);
                if ($id > 0) {
                    LandingTeamMember::where('id', $id)->update(['sort_order' => $order]);
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
            'name' => ($isStore ? 'required' : 'sometimes|required') . '|string|max:255',
            'role' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
        ]);
    }

    private function applyFields(LandingTeamMember $member, Request $request): void
    {
        $lang = (string) $request->input('lang', 'en');
        if ($request->has('name')) {
            $member->name = (string) $request->input('name');
        }
        $member->role = $this->mergeLocaleLang($member->role, $request->input('role'), $lang);
        $member->bio = $this->mergeLocaleLang($member->bio, $request->input('bio'), $lang);
        if ($request->has('sort_order')) {
            $member->sort_order = (int) $request->input('sort_order', 0);
        }
        if ($request->has('is_published')) {
            $member->is_published = $request->boolean('is_published');
        }
    }

    private function transform(LandingTeamMember $member, string $lang): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'role' => LandingTeamMember::localize($member->role, $lang),
            'role_i18n' => is_array($member->role) ? $member->role : ($member->role ? ['en' => $member->role] : null),
            'bio' => LandingTeamMember::localize($member->bio, $lang),
            'bio_i18n' => is_array($member->bio) ? $member->bio : ($member->bio ? ['en' => $member->bio] : null),
            'image' => $member->image,
            'is_published' => (bool) $member->is_published,
            'sort_order' => (int) $member->sort_order,
            'created_at' => optional($member->created_at)->toIso8601String(),
            'updated_at' => optional($member->updated_at)->toIso8601String(),
        ];
    }
}
