<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\ProjectClient;
use App\Models\ProjectType;
use App\Models\ProjectMaterialInventory;
use App\Models\ServiceType;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

class ProjectController extends Controller
{
    public function referenceData(): JsonResponse
    {
        $salespersons = User::whereHas('roles', function ($q) {
            $q->where('name', 'like', '%Sales%');
        })->orderBy('name')->get(['id', 'name']);

        $projectManagers = User::whereHas('roles', function ($q) {
            $q->where('name', 'like', '%Manager%')
                ->orWhere('name', 'like', '%Architect%');
        })->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'clients' => ProjectClient::orderBy('first_name')
                    ->get(['id', 'first_name', 'last_name'])
                    ->map(fn ($client) => [
                        'id' => $client->id,
                        'name' => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
                    ]),
                'project_types' => ProjectType::orderBy('name')->get(['id', 'name']),
                'service_types' => ServiceType::orderBy('name')->get(['id', 'name']),
                'salespersons' => $salespersons,
                'project_managers' => $projectManagers,
                'statuses' => [
                    ['id' => 'pending', 'name' => 'Pending'],
                    ['id' => 'APPROVED', 'name' => 'Approved'],
                    ['id' => 'in_progress', 'name' => 'In Progress'],
                    ['id' => 'COMPLETED', 'name' => 'Completed'],
                    ['id' => 'on_hold', 'name' => 'On Hold'],
                ],
                'priorities' => [
                    ['id' => 'low', 'name' => 'Low'],
                    ['id' => 'normal', 'name' => 'Normal'],
                    ['id' => 'high', 'name' => 'High'],
                    ['id' => 'urgent', 'name' => 'Urgent'],
                ],
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        $projects = Project::all();

        $stats = [
            'total' => $projects->count(),
            'active' => $projects->whereIn('status', ['pending', 'in_progress', 'APPROVED', 'Active'])->count(),
            'completed' => $projects->where('status', 'COMPLETED')->count(),
            'delayed' => $projects->filter(fn($p) => method_exists($p, 'isDelayed') && $p->isDelayed())->count(),
            'total_value' => $projects->sum('contract_value'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus']);

        // Apply search (mobile API - same structure as project_site_visits)
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%")
                                   ->orWhere('phone_number', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('salesperson', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('projectManager', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->project_type_id) {
            $query->where('project_type_id', $request->project_type_id);
        }

        if ($request->service_type_id) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->salesperson_id) {
            $query->where('salesperson_id', $request->salesperson_id);
        }

        if ($request->project_manager_id) {
            $query->where('project_manager_id', $request->project_manager_id);
        }

        // Filter by date range (same as project_site_visits)
        if ($request->start_date) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->whereDate('start_date', '<=', $request->end_date);
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 100);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => ProjectResource::collection($projects),
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with([
            'client',
            'projectType',
            'serviceType',
            'salesperson',
            'projectManager',
            'approvalStatus',
            'approvals.user',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatProjectDetail($project),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'client_id' => 'required|exists:project_clients,id',
                'project_type_id' => 'required|exists:project_types,id',
                'service_type_id' => 'nullable|exists:service_types,id',
                'salesperson_id' => 'nullable|exists:users,id',
                'project_manager_id' => 'nullable|exists:users,id',
                'start_date' => 'required|date',
                'expected_end_date' => 'required|date',
                'actual_end_date' => 'nullable|date',
                'contract_value' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:500',
                'priority' => 'nullable|string|max:50',
            ]);

            // Map 'name' to 'project_name' for database
            if (isset($validated['name'])) {
                $validated['project_name'] = $validated['name'];
                unset($validated['name']);
            }
            
            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = $validated['status'] ?? 'PENDING';

            $project = Project::create($validated);
            $project->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.',
                'data' => new ProjectResource($project),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'client_id' => 'nullable|exists:project_clients,id',
                'project_type_id' => 'nullable|exists:project_types,id',
                'service_type_id' => 'nullable|exists:service_types,id',
                'salesperson_id' => 'nullable|exists:users,id',
                'project_manager_id' => 'nullable|exists:users,id',
                'start_date' => 'nullable|date',
                'expected_end_date' => 'nullable|date',
                'actual_end_date' => 'nullable|date',
                'contract_value' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:500',
                'priority' => 'nullable|string|max:50',
            ]);

            // Map 'name' to 'project_name' for database
            if (isset($validated['name'])) {
                $validated['project_name'] = $validated['name'];
                unset($validated['name']);
            }

            $project->update($validated);
            $project->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.',
                'data' => new ProjectResource($project),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user'])->findOrFail($id);
            if (!$project->canBeSubmittedBy(auth()->user())) {
                return response()->json(['success' => false, 'message' => 'This document cannot be submitted.'], 422);
            }
            $project->submit(auth()->user());
            $project->refresh()->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user']);
            return response()->json([
                'success' => true,
                'message' => 'Project submitted for approval.',
                'data' => $this->formatProjectDetail($project),
            ]);
        } catch (\Throwable $e) {
            Log::error('Project submit error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to submit project: ' . $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user'])->findOrFail($id);
            if (!$project->canBeApprovedBy(auth()->user())) {
                return response()->json(['success' => false, 'message' => 'You cannot approve this document at the current step.'], 422);
            }
            $project->approve($request->input('comment'), auth()->user());
            $project->refresh()->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user']);
            return response()->json([
                'success' => true,
                'message' => 'Project approved successfully.',
                'data' => $this->formatProjectDetail($project),
            ]);
        } catch (\Throwable $e) {
            Log::error('Project approve error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to approve project: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user'])->findOrFail($id);
            if (!$project->canBeApprovedBy(auth()->user())) {
                return response()->json(['success' => false, 'message' => 'You cannot reject this document at the current step.'], 422);
            }
            $request->validate(['comment' => 'required|string|min:1']);
            $project->reject($request->input('comment'), auth()->user());
            $project->refresh()->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user']);
            return response()->json([
                'success' => true,
                'message' => 'Project rejected successfully.',
                'data' => $this->formatProjectDetail($project),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Project reject error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to reject project: ' . $e->getMessage()], 500);
        }
    }

    public function returnForCorrection(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user'])->findOrFail($id);
            if (!$project->canBeApprovedBy(auth()->user())) {
                return response()->json(['success' => false, 'message' => 'You cannot return this document at the current step.'], 422);
            }
            $request->validate(['comment' => 'required|string|min:1']);
            $project->return($request->input('comment'), auth()->user());
            $project->refresh()->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user']);
            return response()->json([
                'success' => true,
                'message' => 'Project returned successfully.',
                'data' => $this->formatProjectDetail($project),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('Project return error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to return project: ' . $e->getMessage()], 500);
        }
    }

    public function discard(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user'])->findOrFail($id);
            if (!$project->canBeApprovedBy(auth()->user()) || !$project->isRejected()) {
                return response()->json(['success' => false, 'message' => 'This document cannot be discarded right now.'], 422);
            }
            $project->discard($request->input('comment'), auth()->user());
            $project->refresh()->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager', 'approvalStatus', 'approvals.user']);
            return response()->json([
                'success' => true,
                'message' => 'Project discarded successfully.',
                'data' => $this->formatProjectDetail($project),
            ]);
        } catch (\Throwable $e) {
            Log::error('Project discard error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to discard project: ' . $e->getMessage()], 500);
        }
    }

    private function formatProjectDetail(Project $project): array
    {
        $resource = (new ProjectResource($project))->toArray(request());
        return array_merge($resource, [
            'project_details' => [
                'Project Name' => $project->project_name,
                'Client Name' => trim(($project->client?->first_name ?? '') . ' ' . ($project->client?->last_name ?? '')),
                'Project Type' => $project->projectType?->name,
                'Service Type' => $project->serviceType?->name,
                'Phone Number' => $project->client?->phone_number,
                'Start Date' => optional($project->start_date)->toDateString(),
                'Expected End Date' => optional($project->expected_end_date)->toDateString(),
                'Actual End Date' => optional($project->actual_end_date)->toDateString(),
                'Status' => $project->approvalStatus?->status ?? $project->status,
                'Contract Value' => $project->formatted_contract_value,
                'Salesperson' => $project->salesperson?->name,
                'Project Manager' => $project->projectManager?->name,
            ],
            'approval_flow' => $this->buildApprovalFlow($project),
        ]);
    }

    private function buildApprovalFlow(Project $project): array
    {
        $nextStep = $project->nextApprovalStep();
        $canApprove = auth()->check() ? (bool) $project->canBeApprovedBy(auth()->user()) : false;
        $steps = collect($project->approvalStatus?->steps ?? [])->map(function ($step) use ($project) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id']);
            $approval = !empty($step['process_approval_id'])
                ? $project->approvals->firstWhere('id', $step['process_approval_id'])
                : null;
            return [
                'step_id' => $step['id'] ?? null,
                'role_name' => $flowStep?->role?->name ?? ('Step ' . ($step['id'] ?? '')),
                'action' => $step['process_approval_action'] ?? 'Pending',
                'approver_name' => $approval?->user?->name ?? $approval?->approver_name,
                'date' => $approval?->created_at?->format('d F, Y'),
                'comment' => $approval?->comment,
            ];
        })->values();

        $isCompleted = $project->isApprovalCompleted();
        $isSubmitted = $project->isSubmitted();
        $isRejected = $project->isRejected();
        $isReturned = $project->isReturned();
        $isDiscarded = $project->isDiscarded();
        $nextRole = $nextStep?->role?->name;
        $nextAction = strtoupper((string) ($nextStep?->action ?? 'APPROVE'));

        if (!$isSubmitted) {
            $statusLabel = 'In Progress';
            $message = $project->canBeSubmittedBy(auth()->user())
                ? 'This document is not yet submitted. You can submit this document for approvals.'
                : 'This document is not yet submitted. Waiting for the creator to submit.';
        } elseif ($isDiscarded) {
            $statusLabel = 'Discarded';
            $message = 'This request was discarded.';
        } elseif ($isCompleted) {
            $statusLabel = 'Approval completed!';
            $message = 'Approval completed!';
        } elseif ($canApprove) {
            $statusLabel = 'In Progress';
            if ($isRejected) {
                $message = 'This request was rejected. You can re-approve this as ' . ($nextRole ?? 'Approver');
            } elseif ($isReturned) {
                $message = 'This request was returned back. You can re-approve this as ' . ($nextRole ?? 'Approver');
            } else {
                $message = 'You Can approve this as ' . ($nextRole ?? 'Approver');
            }
        } else {
            $statusLabel = 'In Progress';
            $message = $nextRole ? 'Waiting for approval from ' . $nextRole : 'Approval is currently in progress.';
        }

        return [
            'status_label' => $statusLabel,
            'is_submitted' => $isSubmitted,
            'is_completed' => $isCompleted,
            'is_rejected' => $isRejected,
            'is_returned' => $isReturned,
            'is_discarded' => $isDiscarded,
            'can_be_submitted' => auth()->check() ? (bool) $project->canBeSubmittedBy(auth()->user()) : false,
            'can_be_approved' => $canApprove,
            'can_be_rejected' => $canApprove && !$isRejected,
            'can_be_returned' => $canApprove && !$isRejected,
            'can_be_discarded' => $canApprove && $isRejected,
            'next_role' => $nextRole,
            'next_action' => $nextAction,
            'message' => $message,
            'steps' => $steps,
        ];
    }

    public function boq(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        // Get the latest BOQ for this project
        $boq = ProjectBoq::where('project_id', $id)
            ->orderBy('version', 'desc')
            ->first();

        if (!$boq) {
            return response()->json([
                'success' => true,
                'data' => [
                    'project_id' => $id,
                    'project_name' => $project->project_name,
                    'boq_id' => null,
                    'version' => null,
                    'items' => [],
                    'total_amount' => 0,
                ],
            ]);
        }

        // Get items for this BOQ
        $boqItems = ProjectBoqItem::where('boq_id', $boq->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'item_type' => $item->item_type,
                'procurement_status' => $item->procurement_status,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'project_id' => $id,
                'project_name' => $project->project_name,
                'boq_id' => $boq->id,
                'version' => $boq->version,
                'status' => $boq->status,
                'items' => $boqItems,
                'total_amount' => $boq->total_amount,
            ],
        ]);
    }

    public function materials(int $id): JsonResponse
    {
        $materials = ProjectMaterialInventory::where('project_id', $id)
            ->orderBy('material_name')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'material_name' => $m->material_name,
                'unit' => $m->unit,
                'quantity_available' => $m->quantity_available,
                'quantity_used' => $m->quantity_used,
                'unit_price' => $m->unit_price,
                'total_value' => $m->total_value,
                'supplier' => $m->supplier,
                'last_restocked' => $m->last_restocked_at?->toDateString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $materials,
        ]);
    }

    public function sites(int $id): JsonResponse
    {
        $sites = Site::where('project_id', $id)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'location' => $s->location,
                'address' => $s->address,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
                'status' => $s->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $sites,
        ]);
    }

    public function team(int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);

            $team = [];
            
            // Try to get team from project_manager and salesperson if team_members table doesn't exist
            if ($project->project_manager_id) {
                $manager = User::with(['department'])->find($project->project_manager_id);
                if ($manager) {
                    $team[] = [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                        'designation' => $manager->designation,
                        'department' => $manager->department?->name,
                        'role' => 'Project Manager',
                    ];
                }
            }

            if ($project->salesperson_id) {
                $salesperson = User::with(['department'])->find($project->salesperson_id);
                if ($salesperson) {
                    $team[] = [
                        'id' => $salesperson->id,
                        'name' => $salesperson->name,
                        'email' => $salesperson->email,
                        'designation' => $salesperson->designation,
                        'department' => $salesperson->department?->name,
                        'role' => 'Salesperson',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $team,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team: ' . $e->getMessage(),
            ], 500);
        }
    }
}
