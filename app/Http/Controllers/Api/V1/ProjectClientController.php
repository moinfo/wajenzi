<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectClientResource;
use App\Models\ClientSource;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

class ProjectClientController extends Controller
{
    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'client_sources' => ClientSource::query()
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectClient::with(['client_source', 'user', 'approvalStatus'])
                ->withCount(['projects', 'documents'])
                ->orderBy('created_at', 'desc');

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%")
                      ->orWhereHas('client_source', function ($sourceQuery) use ($search) {
                          $sourceQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $clients = $query->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => ProjectClientResource::collection($clients),
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $clients->count(),
                        'total' => $clients->count(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient index error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $client = ProjectClient::with(['client_source', 'user', 'projects', 'documents', 'approvalStatus', 'approvals.user'])
                ->withCount(['projects', 'documents'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient show error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone_number' => 'required|string|max:20',
                'address' => 'nullable|string|max:500',
                'identification_number' => 'nullable|string|max:50',
                'client_source_id' => 'required|exists:client_sources,id',
                'portal_access_enabled' => 'nullable|boolean',
                'password' => 'nullable|string|min:4|confirmed',
            ]);

            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = 'PENDING';
            $validated['portal_access_enabled'] = (bool) ($validated['portal_access_enabled'] ?? false);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $client = ProjectClient::create($validated);
            $client->load(['client_source', 'user', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully.',
                'data' => new ProjectClientResource($client),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:100',
                'last_name' => 'sometimes|string|max:100',
                'email' => 'nullable|email|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'address' => 'nullable|string|max:500',
                'identification_number' => 'nullable|string|max:50',
                'client_source_id' => 'nullable|exists:client_sources,id',
                'portal_access_enabled' => 'nullable|boolean',
                'password' => 'nullable|string|min:4|confirmed',
            ]);

            if (array_key_exists('portal_access_enabled', $validated)) {
                $validated['portal_access_enabled'] = (bool) $validated['portal_access_enabled'];
            }

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $client->update($validated);
            $client->load(['client_source', 'user', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully.',
                'data' => new ProjectClientResource($client),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient destroy error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            if (!method_exists($client, 'canBeSubmittedBy') || !$client->canBeSubmittedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'This document cannot be submitted.',
                ], 422);
            }

            $client->submit(auth()->user());
            $client->load(['client_source', 'user', 'approvalStatus', 'approvals.user'])
                ->loadCount(['projects', 'documents']);

            return response()->json([
                'success' => true,
                'message' => 'Project client submitted for approval.',
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient submit error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            if (!$client->canBeApprovedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot approve this document at the current step.',
                ], 422);
            }

            $client->approve($request->input('comment'), auth()->user());
            $client->load(['client_source', 'user', 'approvalStatus', 'approvals.user'])
                ->loadCount(['projects', 'documents']);

            return response()->json([
                'success' => true,
                'message' => 'Project client approved successfully.',
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient approve error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            if (!$client->canBeApprovedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot reject this document at the current step.',
                ], 422);
            }

            $request->validate([
                'comment' => 'required|string|min:1',
            ]);

            $client->reject($request->input('comment'), auth()->user());
            $client->load(['client_source', 'user', 'approvalStatus', 'approvals.user'])
                ->loadCount(['projects', 'documents']);

            return response()->json([
                'success' => true,
                'message' => 'Project client rejected successfully.',
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient reject error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function returnForCorrection(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            if (!$client->canBeApprovedBy(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot return this document at the current step.',
                ], 422);
            }

            $request->validate([
                'comment' => 'required|string|min:1',
            ]);

            $client->return($request->input('comment'), auth()->user());
            $client->load(['client_source', 'user', 'approvalStatus', 'approvals.user'])
                ->loadCount(['projects', 'documents']);

            return response()->json([
                'success' => true,
                'message' => 'Project client returned successfully.',
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectClient return error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to return client: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function discard(Request $request, int $id): JsonResponse
    {
        try {
            $client = ProjectClient::findOrFail($id);

            if (!$client->canBeApprovedBy(auth()->user()) || !$client->isRejected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This document cannot be discarded right now.',
                ], 422);
            }

            $client->discard($request->input('comment'), auth()->user());
            $client->load(['client_source', 'user', 'approvalStatus', 'approvals.user'])
                ->loadCount(['projects', 'documents']);

            return response()->json([
                'success' => true,
                'message' => 'Project client discarded successfully.',
                'data' => $this->formatClientDetail($client),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectClient discard error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to discard client: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatClientDetail(ProjectClient $client): array
    {
        $resource = (new ProjectClientResource($client))->toArray(request());
        $approvalFlow = $this->buildApprovalFlow($client);

        return array_merge($resource, [
            'requested_by' => trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')),
            'created_time' => $client->created_at?->format('Y-m-d H:i:s'),
            'approval_page_url' => url("/project_clients/{$client->id}/9"),
            'project_details' => [
                'First Name' => $client->first_name,
                'Last Name' => $client->last_name,
                'Email' => $client->email,
                'Phone Number' => $client->phone_number,
                'Date Created' => $client->created_at?->format('Y-m-d H:i:s'),
                'Status' => $client->approvalStatus?->status ?? $client->status ?? 'PENDING',
            ],
            'approval_flow' => $approvalFlow,
        ]);
    }

    private function buildApprovalFlow(ProjectClient $client): array
    {
        $nextStep = $client->nextApprovalStep();
        $canApprove = auth()->check() ? (bool) $client->canBeApprovedBy(auth()->user()) : false;
        $steps = collect($client->approvalStatus?->steps ?? [])->map(function ($step) use ($client) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id']);
            $approval = !empty($step['process_approval_id'])
                ? $client->approvals->firstWhere('id', $step['process_approval_id'])
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

        $isCompleted = $client->isApprovalCompleted();
        $isSubmitted = $client->isSubmitted();
        $isRejected = $client->isRejected();
        $isReturned = $client->isReturned();
        $isDiscarded = $client->isDiscarded();
        $nextRole = $nextStep?->role?->name;
        $nextAction = strtoupper((string) ($nextStep?->action ?? 'APPROVE'));

        if (!$isSubmitted) {
            $statusLabel = 'In Progress';
            $message = $client->canBeSubmittedBy(auth()->user())
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
            $message = $nextRole
                ? 'Waiting for approval from ' . $nextRole
                : 'Approval is currently in progress.';
        }

        return [
            'status_label' => $statusLabel,
            'is_submitted' => $isSubmitted,
            'is_completed' => $isCompleted,
            'is_rejected' => $isRejected,
            'is_returned' => $isReturned,
            'is_discarded' => $isDiscarded,
            'can_be_submitted' => auth()->check() ? (bool) $client->canBeSubmittedBy(auth()->user()) : false,
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
}
