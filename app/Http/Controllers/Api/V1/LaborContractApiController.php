<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\Utility;
use App\Http\Controllers\Controller;
use App\Models\LaborContract;
use App\Models\LaborRequest;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaborContractApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $projectId = $request->input('project_id');
            $status = $request->input('status');
            $perPage = $request->input('per_page', 20);

            $query = LaborContract::with([
                'project',
                'artisan',
                'laborRequest',
                'supervisor',
                'paymentPhases'
            ])->orderBy('created_at', 'desc');

            if ($projectId) {
                $query->where('project_id', $projectId);
            }
            if ($status) {
                $query->where('status', $status);
            }

            $contracts = $query->paginate($perPage);

            $items = collect($contracts->items())->map(fn($c) => $this->formatContract($c));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $contracts->currentPage(),
                        'last_page' => $contracts->lastPage(),
                        'per_page' => $contracts->perPage(),
                        'total' => $contracts->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contracts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $projects = Project::orderBy('project_name')
                ->get(['id', 'project_name', 'document_number']);

            $availableRequests = LaborRequest::availableForContract()
                ->with(['project', 'artisan'])
                ->get()
                ->map(fn($r) => [
                    'id' => $r->id,
                    'request_number' => $r->request_number,
                    'project_name' => $r->project?->project_name,
                    'artisan_name' => $r->artisan?->name,
                    'final_amount' => round($r->final_amount, 2),
                ]);

            $supervisors = User::whereHas('roles', fn($q) => $q->where('name', 'Site Supervisor'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            $statuses = [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'on_hold', 'label' => 'On Hold'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'terminated', 'label' => 'Terminated'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'available_requests' => $availableRequests,
                    'supervisors' => $supervisors,
                    'statuses' => $statuses,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract reference data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $contract = LaborContract::with([
                'project',
                'artisan',
                'laborRequest',
                'supervisor',
                'paymentPhases',
                'workLogs',
                'inspections'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatContract($contract, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->merge([
                'total_amount' => Utility::strip_commas($request->input('total_amount')),
            ]);

            $validated = $request->validate([
                'labor_request_id' => 'required|exists:labor_requests,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'scope_of_work' => 'required|string',
                'supervisor_id' => 'nullable|exists:users,id',
                'total_amount' => 'required|numeric|min:0',
                'terms_conditions' => 'nullable|string',
                'phases' => 'nullable|array',
                'phases.*.phase_number' => 'required_with:phases|integer',
                'phases.*.phase_name' => 'required_with:phases|string',
                'phases.*.percentage' => 'required_with:phases|numeric|min:0|max:100',
                'phases.*.milestone_description' => 'nullable|string',
            ]);

            $laborRequest = LaborRequest::findOrFail($validated['labor_request_id']);

            if (!$laborRequest->canCreateContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not eligible for contract creation',
                ], 400);
            }

            $contract = DB::transaction(function () use ($validated, $laborRequest) {
                $contract = LaborContract::create([
                    'labor_request_id' => $validated['labor_request_id'],
                    'project_id' => $laborRequest->project_id,
                    'artisan_id' => $laborRequest->artisan_id,
                    'supervisor_id' => $validated['supervisor_id'] ?? null,
                    'contract_date' => now(),
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'scope_of_work' => $validated['scope_of_work'],
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                    'total_amount' => $validated['total_amount'],
                    'currency' => $laborRequest->currency,
                    'status' => 'draft',
                ]);

                if (!empty($validated['phases'])) {
                    foreach ($validated['phases'] as $phaseData) {
                        $contract->paymentPhases()->create([
                            'phase_number' => $phaseData['phase_number'],
                            'phase_name' => $phaseData['phase_name'],
                            'percentage' => $phaseData['percentage'],
                            'amount' => ($phaseData['percentage'] / 100) * $contract->total_amount,
                            'milestone_description' => $phaseData['milestone_description'] ?? null,
                            'status' => 'pending',
                        ]);
                    }
                } else {
                    $contract->createDefaultPaymentPhases();
                }

                $laborRequest->update(['status' => 'contracted']);

                return $contract;
            });

            $contract->load(['project', 'artisan', 'supervisor', 'paymentPhases']);

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully.',
                'data' => $this->formatContract($contract),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborContract store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $contract = LaborContract::findOrFail($id);

            if (!$contract->isDraft()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft contracts can be edited',
                ], 400);
            }

            $validated = $request->validate([
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after:start_date',
                'scope_of_work' => 'sometimes|required|string',
                'supervisor_id' => 'nullable|exists:users,id',
                'terms_conditions' => 'nullable|string',
                'phases' => 'nullable|array',
                'phases.*.phase_number' => 'required_with:phases|integer',
                'phases.*.phase_name' => 'required_with:phases|string',
                'phases.*.percentage' => 'required_with:phases|numeric|min:0|max:100',
                'phases.*.milestone_description' => 'nullable|string',
            ]);

            // total_amount is locked on edit (mirrors web); only scalar fields + phases change.
            $scalarFields = array_intersect_key($validated, array_flip([
                'start_date', 'end_date', 'scope_of_work', 'supervisor_id', 'terms_conditions',
            ]));

            DB::transaction(function () use ($contract, $scalarFields, $request) {
                if (!empty($scalarFields)) {
                    $contract->update($scalarFields);
                }

                if ($request->has('phases')) {
                    $contract->paymentPhases()->delete();
                    foreach ($request->input('phases') as $phaseData) {
                        $contract->paymentPhases()->create([
                            'phase_number' => $phaseData['phase_number'],
                            'phase_name' => $phaseData['phase_name'],
                            'percentage' => $phaseData['percentage'],
                            'amount' => ($phaseData['percentage'] / 100) * $contract->total_amount,
                            'milestone_description' => $phaseData['milestone_description'] ?? null,
                            'status' => 'pending',
                        ]);
                    }
                }
            });

            $contract->refresh();
            $contract->load(['project', 'artisan', 'supervisor', 'paymentPhases']);

            return response()->json([
                'success' => true,
                'message' => 'Contract updated successfully.',
                'data' => $this->formatContract($contract),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborContract update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function putOnHold(Request $request, int $id): JsonResponse
    {
        try {
            $contract = LaborContract::findOrFail($id);

            if (!$contract->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active contracts can be put on hold',
                ], 400);
            }

            $contract->update([
                'status' => 'on_hold',
                'notes' => $contract->notes . "\n\nPut on hold: " . ($request->input('reason') ?? 'No reason specified'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract put on hold.',
                'data' => $this->formatContract($contract),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract putOnHold error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to put contract on hold: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resume(int $id): JsonResponse
    {
        try {
            $contract = LaborContract::findOrFail($id);

            if (!$contract->isOnHold()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only contracts on hold can be resumed',
                ], 400);
            }

            $contract->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Contract resumed.',
                'data' => $this->formatContract($contract),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract resume error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function terminate(Request $request, int $id): JsonResponse
    {
        try {
            $contract = LaborContract::findOrFail($id);

            if ($contract->isCompleted() || $contract->isTerminated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This contract cannot be terminated',
                ], 400);
            }

            $validated = $request->validate([
                'termination_reason' => 'required|string|min:10',
            ]);

            DB::transaction(function () use ($contract, $validated) {
                $contract->update([
                    'status' => 'terminated',
                    'actual_end_date' => now(),
                    'notes' => $contract->notes . "\n\nTermination Reason: " . $validated['termination_reason'],
                ]);

                // Put all pending/due/approved payment phases on hold
                $contract->paymentPhases()
                    ->whereIn('status', ['pending', 'due', 'approved'])
                    ->update(['status' => 'held']);
            });

            $contract->load(['project', 'artisan', 'supervisor', 'paymentPhases']);

            return response()->json([
                'success' => true,
                'message' => 'Contract terminated.',
                'data' => $this->formatContract($contract),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborContract terminate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sign(Request $request, int $id): JsonResponse
    {
        try {
            $contract = LaborContract::with(['artisan', 'supervisor'])->findOrFail($id);

            if (!$contract->isDraft()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft contracts can be signed',
                ], 400);
            }

            $request->validate([
                'contract_file' => 'nullable|file|max:10240',
            ]);

            DB::transaction(function () use ($request, $contract) {
                // Use supervisor's / artisan's profile signature if available,
                // otherwise fall back to a signing timestamp.
                $contract->supervisor_signature = ($contract->supervisor && $contract->supervisor->profile)
                    ? $contract->supervisor->profile
                    : now()->toISOString();
                $contract->artisan_signature = ($contract->artisan && $contract->artisan->profile)
                    ? $contract->artisan->profile
                    : now()->toISOString();

                // Handle optional uploaded signed-contract file
                if ($request->hasFile('contract_file')) {
                    $file = $request->file('contract_file');
                    $fileName = time() . '_contract_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('uploads/labor_contracts', $fileName, 'public');
                    $contract->contract_file = '/storage/' . $filePath;
                }

                $contract->status = 'active';
                $contract->save();

                // Mark first payment phase as due (mobilization)
                $firstPhase = $contract->paymentPhases()->where('phase_number', 1)->first();
                if ($firstPhase) {
                    $firstPhase->markAsDue();
                }
            });

            $contract->load(['project', 'artisan', 'supervisor', 'paymentPhases']);

            return response()->json([
                'success' => true,
                'message' => 'Contract signed and activated.',
                'data' => $this->formatContract($contract),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('LaborContract sign error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sign contract: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generatePDF(int $id): JsonResponse
    {
        try {
            $contract = LaborContract::with([
                'project',
                'artisan',
                'laborRequest.constructionPhase',
                'supervisor',
                'paymentPhases',
            ])->findOrFail($id);

            $pdf = Pdf::loadView('labor.contracts.pdf', ['contract' => $contract]);
            $pdf->setPaper('A4');

            $filename = 'Labor_Contract_' . $contract->contract_number . '.pdf';

            return response()->json([
                'success' => true,
                'data' => [
                    'pdf_base64' => base64_encode($pdf->output()),
                    'filename' => $filename,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract generatePDF error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate contract PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total' => LaborContract::count(),
                'draft' => LaborContract::where('status', 'draft')->count(),
                'active' => LaborContract::where('status', 'active')->count(),
                'on_hold' => LaborContract::where('status', 'on_hold')->count(),
                'completed' => LaborContract::where('status', 'completed')->count(),
                'terminated' => LaborContract::where('status', 'terminated')->count(),
            ];

            $totalAmount = LaborContract::sum('total_amount');
            $totalPaid = LaborContract::sum('amount_paid');
            $totalBalance = LaborContract::sum('balance_amount');

            $recentContracts = LaborContract::with(['project', 'artisan'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($c) => $this->formatContract($c));

            $activeContracts = LaborContract::with(['project', 'artisan'])
                ->where('status', 'active')
                ->orderBy('end_date', 'asc')
                ->limit(10)
                ->get()
                ->map(fn($c) => $this->formatContract($c));

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'totals' => [
                        'total_amount' => round($totalAmount, 2),
                        'total_paid' => round($totalPaid, 2),
                        'total_balance' => round($totalBalance, 2),
                    ],
                    'recent_contracts' => $recentContracts,
                    'active_contracts' => $activeContracts,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborContract dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatContract($contract, bool $detailed = false)
    {
        $data = [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'status' => $contract->status,
            'status_badge_class' => $contract->status_badge_class ?? 'secondary',
            'contract_date' => $contract->contract_date?->format('Y-m-d'),
            'start_date' => $contract->start_date?->format('Y-m-d'),
            'end_date' => $contract->end_date?->format('Y-m-d'),
            'actual_end_date' => $contract->actual_end_date?->format('Y-m-d'),
            'scope_of_work' => $contract->scope_of_work,
            'terms_conditions' => $contract->terms_conditions,
            'total_amount' => round((float) $contract->total_amount, 2),
            'amount_paid' => round((float) $contract->amount_paid, 2),
            'balance_amount' => round((float) $contract->balance_amount, 2),
            'payment_progress' => round($contract->payment_progress, 2),
            'latest_progress' => round($contract->latest_progress, 2),
            'days_remaining' => $contract->days_remaining,
            'days_overdue' => $contract->days_overdue,
            'currency' => $contract->currency ?? 'TZS',
            'is_signed' => $contract->isSigned(),
            'notes' => $contract->notes,
            'can_edit' => $contract->isDraft(),
            'can_sign' => $contract->isDraft(),
            'can_put_on_hold' => $contract->isActive(),
            'can_resume' => $contract->isOnHold(),
            'can_terminate' => !$contract->isCompleted() && !$contract->isTerminated(),
            'created_at' => $contract->created_at?->toISOString(),
        ];

        if ($contract->relationLoaded('project') && $contract->project) {
            $data['project'] = [
                'id' => $contract->project->id,
                'project_name' => $contract->project->project_name,
            ];
        }

        if ($contract->relationLoaded('artisan') && $contract->artisan) {
            $data['artisan'] = [
                'id' => $contract->artisan->id,
                'name' => $contract->artisan->name,
                'phone' => $contract->artisan->phone,
                'trade_skill' => $contract->artisan->trade_skill,
            ];
        }

        if ($contract->relationLoaded('supervisor') && $contract->supervisor) {
            $data['supervisor'] = [
                'id' => $contract->supervisor->id,
                'name' => $contract->supervisor->name,
            ];
        }

        if ($contract->relationLoaded('laborRequest') && $contract->laborRequest) {
            $data['labor_request'] = [
                'id' => $contract->laborRequest->id,
                'request_number' => $contract->laborRequest->request_number,
            ];
        }

        if ($contract->relationLoaded('paymentPhases')) {
            $data['payment_phases'] = $contract->paymentPhases->map(fn($p) => [
                'id' => $p->id,
                'phase_number' => $p->phase_number,
                'phase_name' => $p->phase_name,
                'percentage' => round((float) $p->percentage, 2),
                'amount' => round((float) $p->amount, 2),
                'status' => $p->status,
                'milestone_description' => $p->milestone_description,
            ])->toArray();
        }

        if ($detailed) {
            if ($contract->relationLoaded('workLogs')) {
                $data['work_logs'] = $contract->workLogs->map(fn($w) => [
                    'id' => $w->id,
                    'log_date' => $w->log_date?->format('Y-m-d'),
                    'progress_percentage' => round((float) ($w->progress_percentage ?? 0), 2),
                    'work_done' => $w->work_done,
                ])->toArray();
            }

            if ($contract->relationLoaded('inspections')) {
                $data['inspections'] = $contract->inspections->map(fn($i) => [
                    'id' => $i->id,
                    'inspection_number' => $i->inspection_number,
                    'inspection_type' => $i->inspection_type,
                    'completion_percentage' => round((float) ($i->completion_percentage ?? 0), 2),
                    'status' => $i->status,
                    'result' => $i->result,
                ])->toArray();
            }
        }

        return $data;
    }
}
