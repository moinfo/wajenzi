<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApprovalDocumentType;
use App\Models\ApprovalLevel;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalLevelApiController extends Controller
{
    public function referenceData(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'approval_document_types' => ApprovalDocumentType::orderBy('name')
                        ->get(['id', 'name'])
                        ->values(),
                    'user_groups' => UserGroup::orderBy('name')
                        ->get(['id', 'name', 'keyword'])
                        ->values(),
                    'actions' => [
                        ['value' => 'CHECK', 'label' => 'CHECK'],
                        ['value' => 'VERIFY', 'label' => 'VERIFY'],
                        ['value' => 'APPROVE', 'label' => 'APPROVE'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel reference data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval level reference data',
                'data' => [
                    'approval_document_types' => [],
                    'user_groups' => [],
                    'actions' => [],
                ],
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $items = ApprovalLevel::with([
                'approvalDocumentType:id,name',
                'userGroup:id,name,keyword',
            ])->orderBy('approval_document_types_id')
                ->orderBy('order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ApprovalLevel $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval levels',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'approval_document_types_id' => 'required|exists:approval_document_types,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'description' => 'nullable|string',
                'order' => 'required|integer|min:0',
                'action' => 'required|string|in:CHECK,VERIFY,APPROVE',
            ]);

            $item = ApprovalLevel::create($validated)->load([
                'approvalDocumentType:id,name',
                'userGroup:id,name,keyword',
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Approval level created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create approval level: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ApprovalLevel::with([
                'approvalDocumentType:id,name',
                'userGroup:id,name,keyword',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Approval level not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ApprovalLevel::findOrFail($id);
            $validated = $request->validate([
                'approval_document_types_id' => 'required|exists:approval_document_types,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'description' => 'nullable|string',
                'order' => 'required|integer|min:0',
                'action' => 'required|string|in:CHECK,VERIFY,APPROVE',
            ]);
            $item->update($validated);
            $item->load([
                'approvalDocumentType:id,name',
                'userGroup:id,name,keyword',
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh([
                    'approvalDocumentType:id,name',
                    'userGroup:id,name,keyword',
                ])),
                'message' => 'Approval level updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval level: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ApprovalLevel::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approval level deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalLevel destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete approval level',
            ], 500);
        }
    }

    private function formatItem(ApprovalLevel $item): array
    {
        return [
            'id' => $item->id,
            'approval_document_types_id' => $item->approval_document_types_id,
            'approval_document_type_name' => $item->approvalDocumentType?->name,
            'user_group_id' => $item->user_group_id,
            'user_group_name' => $item->userGroup?->name,
            'user_group_keyword' => $item->userGroup?->keyword,
            'description' => $item->description,
            'order' => $item->order,
            'action' => $item->action,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
