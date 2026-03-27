<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProcessApprovalFlow;
use App\Models\ProcessApprovalFlowStep;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessApprovalFlowStepApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $steps = ProcessApprovalFlowStep::with(['process_approval_flow:id,name', 'role:id,name'])
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $steps->map(fn (ProcessApprovalFlowStep $step) => $this->formatStep($step))->values(),
                'meta' => [
                    'total' => $steps->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval flow steps',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $flows = ProcessApprovalFlow::orderBy('name')->get(['id', 'name']);
            $roles = Role::orderBy('name')->get(['id', 'name']);
            $actions = [
                ['name' => 'APPROVE', 'value' => 'APPROVE'],
                ['name' => 'REJECT', 'value' => 'REJECT'],
                ['name' => 'RECOMMEND', 'value' => 'RECOMMEND'],
                ['name' => 'VERIFY', 'value' => 'VERIFY'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'flows' => $flows->map(fn ($flow) => [
                        'id' => $flow->id,
                        'name' => $flow->name,
                        'label' => $flow->name,
                        'value' => $flow->id,
                    ])->values(),
                    'roles' => $roles->map(fn ($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'label' => $role->name,
                        'value' => $role->id,
                    ])->values(),
                    'actions' => $actions,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval flow step references',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $step = ProcessApprovalFlowStep::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatStep($step->load(['process_approval_flow:id,name', 'role:id,name'])),
                'message' => 'Approval flow step created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create approval flow step: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $step = ProcessApprovalFlowStep::with(['process_approval_flow:id,name', 'role:id,name'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatStep($step),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Approval flow step not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $step = ProcessApprovalFlowStep::findOrFail($id);
            $validated = $this->validatePayload($request);
            $step->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatStep($step->load(['process_approval_flow:id,name', 'role:id,name'])),
                'message' => 'Approval flow step updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval flow step: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $step = ProcessApprovalFlowStep::findOrFail($id);
            $step->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approval flow step deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlowStep destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete approval flow step: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'process_approval_flow_id' => 'required|exists:process_approval_flows,id',
            'role_id' => 'required|exists:roles,id',
            'action' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'permission' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);
    }

    private function formatStep(ProcessApprovalFlowStep $step): array
    {
        return [
            'id' => $step->id,
            'process_approval_flow_id' => $step->process_approval_flow_id,
            'process_approval_flow_name' => $step->process_approval_flow?->name,
            'role_id' => $step->role_id,
            'role_name' => $step->role?->name,
            'action' => $step->action,
            'order' => (int) ($step->order ?? 0),
            'description' => $step->description ?? null,
            'permission' => $step->permission ?? null,
            'active' => (bool) ($step->active ?? false),
            'created_at' => optional($step->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($step->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
