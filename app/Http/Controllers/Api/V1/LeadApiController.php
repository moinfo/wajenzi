<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\ProjectClient;
use App\Models\ServiceInterested;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with([
            'leadSource:id,name',
            'leadStatus:id,name',
            'serviceInterested:id,name',
            'salesperson:id,name',
            'project:id,project_name',
            'client:id,first_name,last_name',
            'latestFollowup',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lead_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('lead_status_id')) {
            $query->where('lead_status_id', $request->lead_status_id);
        }

        if ($request->filled('lead_source_id')) {
            $query->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->filled('salesperson_id')) {
            $query->where('salesperson_id', $request->salesperson_id);
        }

        $leads = $query->orderBy('lead_date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $leads->map(fn (Lead $lead) => $this->transformLead($lead)),
            'meta' => [
                'total' => $leads->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::with([
            'leadSource:id,name',
            'leadStatus:id,name',
            'serviceInterested:id,name',
            'salesperson:id,name',
            'project:id,project_name',
            'client:id,first_name,last_name',
            'latestFollowup',
        ])->find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformLead($lead),
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $salespeople = User::whereHas('roles', function ($query) {
            $query->where('roles.id', 13);
        })->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'lead_sources' => LeadSource::orderBy('name')->get(['id', 'name']),
                'lead_statuses' => LeadStatus::orderBy('name')->get(['id', 'name']),
                'service_interesteds' => ServiceInterested::orderBy('name')->get(['id', 'name']),
                'salespeople' => $salespeople,
                'clients' => ProjectClient::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:project_clients,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'lead_source_id' => 'required|exists:lead_sources,id',
            'service_interested_id' => 'required|exists:service_interesteds,id',
            'lead_status_id' => 'required|exists:lead_statuses,id',
            'salesperson_id' => 'required|exists:users,id',
            'lead_date' => 'nullable|date',
            'site_location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'active';

        $lead = Lead::create($validated);
        $lead->load([
            'leadSource:id,name',
            'leadStatus:id,name',
            'serviceInterested:id,name',
            'salesperson:id,name',
            'project:id,project_name',
            'client:id,first_name,last_name',
            'latestFollowup',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully',
            'data' => $this->transformLead($lead),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:project_clients,id',
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'lead_source_id' => 'sometimes|required|exists:lead_sources,id',
            'service_interested_id' => 'sometimes|required|exists:service_interesteds,id',
            'lead_status_id' => 'sometimes|required|exists:lead_statuses,id',
            'salesperson_id' => 'sometimes|required|exists:users,id',
            'lead_date' => 'nullable|date',
            'site_location' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'nullable|string|max:50',
        ]);

        $lead->update($validated);
        $lead->load([
            'leadSource:id,name',
            'leadStatus:id,name',
            'serviceInterested:id,name',
            'salesperson:id,name',
            'project:id,project_name',
            'client:id,first_name,last_name',
            'latestFollowup',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lead updated successfully',
            'data' => $this->transformLead($lead),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found',
            ], 404);
        }

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted successfully',
        ]);
    }

    private function transformLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'lead_number' => $lead->lead_number,
            'lead_date' => optional($lead->lead_date)->format('Y-m-d'),
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'site_location' => $lead->site_location,
            'city' => $lead->city,
            'estimated_value' => $lead->estimated_value,
            'client_id' => $lead->client_id,
            'lead_source_id' => $lead->lead_source_id,
            'service_interested_id' => $lead->service_interested_id,
            'lead_status_id' => $lead->lead_status_id,
            'salesperson_id' => $lead->salesperson_id,
            'status' => $lead->status,
            'lead_source_name' => $lead->leadSource?->name,
            'lead_status_name' => $lead->leadStatus?->name,
            'service_interested_name' => $lead->serviceInterested?->name,
            'salesperson_name' => $lead->salesperson?->name,
            'project_name' => $lead->project?->project_name,
            'client_name' => $lead->client
                ? trim(($lead->client->first_name ?? '') . ' ' . ($lead->client->last_name ?? ''))
                : null,
            'latest_followup_date' => optional($lead->latestFollowup?->followup_date)->format('Y-m-d'),
            'latest_followup_status' => $lead->latestFollowup?->status,
            'notes' => $lead->notes,
            'created_at' => $lead->created_at?->toIso8601String(),
            'updated_at' => $lead->updated_at?->toIso8601String(),
        ];
    }
}
