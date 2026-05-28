<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectServiceDesign;
use App\Models\ProjectServiceDesignStage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Mobile API for the Engineering Design > Service Design feature.
 *
 * Mirrors `App\Http\Controllers\ProjectServiceDesignController` but returns
 * JSON for the Wajenzi mobile app.
 */
class ServiceDesignApiController extends Controller
{
    /** GET /api/v1/service-designs */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = ProjectServiceDesign::with([
                'project:id,project_name',
                'assignedEngineer:id,name',
                'stages',
            ]);

            if ($request->filled('project_id')) {
                $query->where('project_id', $request->integer('project_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            $isManager = $user && $user->hasAnyRole([
                'Managing Director',
                'CEO',
                'Chief Executive Officer',
                'System Administrator',
                'Admin',
            ]);

            if (!$isManager || $request->boolean('assigned_to_me')) {
                $query->where('assigned_engineer_id', $user?->id);
            }

            $designs = $query->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 20));

            $items = collect($designs->items())->map(fn ($d) => $this->summary($d));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $designs->currentPage(),
                        'last_page'    => $designs->lastPage(),
                        'per_page'     => $designs->perPage(),
                        'total'        => $designs->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service designs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** GET /api/v1/service-designs/reference-data */
    public function referenceData(): JsonResponse
    {
        try {
            $engineers = User::whereHas('roles', fn ($q) =>
                $q->whereIn('name', ['Service Engineer'])
            )->orderBy('name')->get(['id', 'name']);

            $projects = DB::table('projects')
                ->leftJoin('project_service_designs', 'project_service_designs.project_id', '=', 'projects.id')
                ->whereNull('project_service_designs.id')
                ->orderBy('projects.project_name')
                ->limit(200)
                ->get(['projects.id', 'projects.project_name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'engineers' => $engineers,
                    'projects'  => $projects,
                    'statuses'  => ['pending', 'in_progress', 'submitted', 'approved', 'rejected'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi reference-data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** GET /api/v1/service-designs/{id} */
    public function show(int $id): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::with([
                'project:id,project_name',
                'assignedEngineer:id,name',
                'stages.completedByUser:id,name',
                'creator:id,name',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $this->detail($design),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service design: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** POST /api/v1/service-designs */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id'           => 'required|exists:projects,id',
                'assigned_engineer_id' => 'nullable|exists:users,id',
                'notes'                => 'nullable|string|max:1000',
            ]);

            if (ProjectServiceDesign::where('project_id', $validated['project_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A service design already exists for this project.',
                ], 422);
            }

            $design = DB::transaction(function () use ($validated, $request) {
                $design = ProjectServiceDesign::create([
                    'project_id'           => $validated['project_id'],
                    'assigned_engineer_id' => $validated['assigned_engineer_id'] ?? null,
                    'notes'                => $validated['notes'] ?? null,
                    'status'               => 'pending',
                    'created_by'           => $request->user()->id,
                ]);

                foreach (ProjectServiceDesignStage::defaultStages() as $stage) {
                    ProjectServiceDesignStage::create(array_merge(
                        $stage,
                        ['service_design_id' => $design->id, 'status' => 'pending']
                    ));
                }

                return $design;
            });

            $design->load(['project:id,project_name', 'assignedEngineer:id,name', 'stages']);

            return response()->json([
                'success' => true,
                'message' => 'Service design created successfully.',
                'data'    => $this->detail($design),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service design: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** PUT /api/v1/service-designs/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::findOrFail($id);

            $validated = $request->validate([
                'assigned_engineer_id' => 'nullable|exists:users,id',
                'notes'                => 'nullable|string|max:1000',
            ]);

            $design->update($validated);
            $design->load(['project:id,project_name', 'assignedEngineer:id,name', 'stages']);

            return response()->json([
                'success' => true,
                'message' => 'Service design updated.',
                'data'    => $this->detail($design),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** DELETE /api/v1/service-designs/{id} */
    public function destroy(int $id): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::findOrFail($id);

            if (in_array($design->status, ['submitted', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a submitted or approved service design.',
                ], 422);
            }

            $design->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service design deleted.',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** POST /api/v1/service-designs/{id}/submit */
    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::with('stages')->findOrFail($id);

            if (!$design->scheduleApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The work schedule must be approved before submitting.',
                ], 422);
            }

            $unapproved = $design->stages->where('approval_status', '!=', 'approved')->count();
            if ($unapproved > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'All stages must be individually approved before submitting the final design.',
                ], 422);
            }

            if ($design->status === 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'This service design has already been submitted.',
                ], 422);
            }

            $design->submit(Auth::user());
            $design->update(['submitted_at' => now(), 'status' => 'submitted']);

            return response()->json([
                'success' => true,
                'message' => 'Service design submitted for CEO/MD approval.',
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** POST /api/v1/service-designs/{id}/schedule */
    public function submitSchedule(Request $request, int $id): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::findOrFail($id);

            $validated = $request->validate([
                'schedule_description'   => 'required|string|max:3000',
                'schedule_planned_start' => 'required|date',
                'schedule_planned_end'   => 'required|date|after_or_equal:schedule_planned_start',
            ]);

            if ($design->scheduleApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work schedule is already approved.',
                ], 422);
            }

            $design->update(array_merge($validated, [
                'schedule_status'          => 'submitted',
                'schedule_submitted_at'    => now(),
                'schedule_rejection_notes' => null,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Work schedule submitted for management approval.',
                'data'    => $this->detail($design->fresh(['project:id,project_name', 'assignedEngineer:id,name', 'stages'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi submitSchedule error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit schedule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** POST /api/v1/service-designs/{id}/stages/{stageId} (multipart for file) */
    public function updateStage(Request $request, int $id, int $stageId): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::findOrFail($id);
            $stage  = ProjectServiceDesignStage::where('service_design_id', $id)
                ->findOrFail($stageId);

            $validated = $request->validate([
                'status' => 'required|in:pending,in_progress,completed',
                'notes'  => 'nullable|string|max:1000',
                'file'   => 'nullable|file|max:20480|mimes:pdf,dwg,dxf,jpg,jpeg,png,zip',
            ]);

            if (!$design->scheduleApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work schedule must be approved by management before stage work can begin.',
                ], 422);
            }

            if (in_array($stage->approval_status, ['submitted', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This stage has already been submitted for approval and cannot be edited.',
                ], 422);
            }

            $data = [
                'status' => $validated['status'],
                'notes'  => $validated['notes'] ?? $stage->notes,
            ];

            if ($validated['status'] === 'completed') {
                $data['completed_at'] = now();
                $data['completed_by'] = Auth::id();
            }

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('service_designs/' . $id, 'public');
                $data['file_path'] = $path;
                $data['file_name'] = $request->file('file')->getClientOriginalName();
            }

            $stage->update($data);

            if ($validated['status'] === 'in_progress' && $design->status === 'pending') {
                $design->update(['status' => 'in_progress']);
            }

            return response()->json([
                'success' => true,
                'message' => "Stage '{$stage->name}' updated.",
                'data'    => $this->stagePayload($stage->fresh('completedByUser')),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi updateStage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** POST /api/v1/service-designs/{id}/stages/{stageId}/submit */
    public function submitStage(Request $request, int $id, int $stageId): JsonResponse
    {
        try {
            $design = ProjectServiceDesign::findOrFail($id);
            $stage  = ProjectServiceDesignStage::where('service_design_id', $id)
                ->findOrFail($stageId);

            if (!$design->scheduleApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work schedule must be approved before submitting stages.',
                ], 422);
            }
            if ($stage->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stage must be marked as completed before submitting for approval.',
                ], 422);
            }
            if (!$stage->file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please upload the stage document before submitting for approval.',
                ], 422);
            }
            if ($stage->approval_status === 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'This stage is already awaiting approval.',
                ], 422);
            }

            $stage->update([
                'approval_status' => 'submitted',
                'submitted_at'    => now(),
                'rejected_at'     => null,
                'rejection_notes' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Stage \"{$stage->name}\" submitted for management approval.",
                'data'    => $this->stagePayload($stage->fresh('completedByUser')),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignApi submitStage error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit stage: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Transformers ─────────────────────────────────────────────────────────

    private function summary(ProjectServiceDesign $d): array
    {
        $stages    = $d->relationLoaded('stages') ? $d->stages : collect();
        $total     = $stages->count();
        $completed = $stages->where('status', 'completed')->count();

        return [
            'id'                  => $d->id,
            'document_number'     => 'SVC-' . str_pad((string) $d->id, 4, '0', STR_PAD_LEFT),
            'project_id'          => $d->project_id,
            'project_name'        => $d->project?->project_name,
            'assigned_engineer_id' => $d->assigned_engineer_id,
            'assigned_engineer'   => $d->assignedEngineer?->name,
            'status'              => $d->status,
            'schedule_status'     => $d->schedule_status,
            'stages_total'        => $total,
            'stages_completed'    => $completed,
            'created_at'          => $d->created_at?->toIso8601String(),
            'submitted_at'        => $d->submitted_at?->toIso8601String(),
            'approved_at'         => $d->approved_at?->toIso8601String(),
        ];
    }

    private function detail(ProjectServiceDesign $d): array
    {
        return array_merge($this->summary($d), [
            'notes'                    => $d->notes,
            'created_by'               => $d->created_by,
            'creator_name'             => $d->creator?->name,
            'schedule_description'     => $d->schedule_description,
            'schedule_planned_start'   => $d->schedule_planned_start?->toDateString(),
            'schedule_planned_end'     => $d->schedule_planned_end?->toDateString(),
            'schedule_submitted_at'    => $d->schedule_submitted_at?->toIso8601String(),
            'schedule_approved_at'     => $d->schedule_approved_at?->toIso8601String(),
            'schedule_rejection_notes' => $d->schedule_rejection_notes,
            'stages'                   => $d->stages->map(fn ($s) => $this->stagePayload($s))->values(),
        ]);
    }

    private function stagePayload(ProjectServiceDesignStage $s): array
    {
        $fileUrl = $s->file_path ? url(Storage::disk('public')->url($s->file_path)) : null;

        return [
            'id'              => $s->id,
            'name'            => $s->name,
            'stage_order'     => $s->stage_order,
            'status'          => $s->status,
            'approval_status' => $s->approval_status,
            'file_path'       => $s->file_path,
            'file_name'       => $s->file_name,
            'file_url'        => $fileUrl,
            'notes'           => $s->notes,
            'completed_at'    => $s->completed_at?->toIso8601String(),
            'completed_by'    => $s->completed_by,
            'completed_by_name' => $s->completedByUser?->name,
            'submitted_at'    => $s->submitted_at?->toIso8601String(),
            'approved_at'     => $s->approved_at?->toIso8601String(),
            'rejected_at'     => $s->rejected_at?->toIso8601String(),
            'rejection_notes' => $s->rejection_notes,
        ];
    }
}
