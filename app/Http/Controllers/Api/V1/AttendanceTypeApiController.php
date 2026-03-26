<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AttendanceType::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (AttendanceType $item) => $this->transform($item));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = AttendanceType::query()->withCount('users')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transform($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attendance_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        $item = AttendanceType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance type created successfully.',
            'data' => $this->transform($item->fresh()->loadCount('users')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = AttendanceType::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:attendance_types,name,' . $item->id,
            'description' => 'nullable|string|max:255',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance type updated successfully.',
            'data' => $this->transform($item->fresh()->loadCount('users')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AttendanceType::query()->withCount('users')->findOrFail($id);

        if ($item->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete attendance type that is assigned to users.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance type deleted successfully.',
        ]);
    }

    private function transform(AttendanceType $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'users_count' => (int) ($item->users_count ?? 0),
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
