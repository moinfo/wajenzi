<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ClientSource;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Project;
use App\Models\ProjectClient;
use App\Models\ProjectType;
use App\Models\SalesLeadFollowup;
use App\Models\ServiceInterested;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    /**
     * Get salespeople (users with Sales and Marketing role - role_id = 13)
     */
    private function getSalespeople()
    {
        return User::whereHas('roles', function($query) {
            $query->where('roles.id', 13); // Sales and Marketing role
        })->get();
    }

    public function index(Request $request)
    {
        // Handle CRUD operations
        if ($this->handleCrud($request, 'Lead')) {
            return back();
        }

        $query = Lead::with(['leadSource', 'serviceInterested', 'leadStatus', 'salesperson', 'createdBy', 'latestFollowup']);

        // Apply filters
        if ($request->lead_status_id) {
            $query->where('lead_status_id', $request->lead_status_id);
        }

        if ($request->lead_source_id) {
            $query->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->salesperson_id) {
            $query->where('salesperson_id', $request->salesperson_id);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('lead_number', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        $leads = $query->orderBy('lead_date', 'desc')->orderBy('id', 'desc')->paginate(20);

        $data = [
            'leads' => $leads,
            'leadSources' => LeadSource::all(),
            'leadStatuses' => LeadStatus::all(),
            'serviceInteresteds' => ServiceInterested::all(),
            'salespeople' => $this->getSalespeople(),
            'object' => new Lead()
        ];

        return view('pages.leads.index')->with($data);
    }

    public function create()
    {
        $data = [
            'object' => new Lead(),
            'clients' => ProjectClient::orderBy('first_name')->get(),
            'leadSources' => LeadSource::all(),
            'leadStatuses' => LeadStatus::all(),
            'serviceInteresteds' => ServiceInterested::all(),
            'salespeople' => $this->getSalespeople()
        ];

        return view('pages.leads.form')->with($data);
    }

    public function store(Request $request)
    {
        $request->validate([
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

        try {
            $clientId = $request->client_id;

            // If no existing client selected, create a new one
            if (!$clientId) {
                // Split name into first and last name
                $nameParts = explode(' ', trim($request->name), 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                $newClient = ProjectClient::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $request->email,
                    'phone_number' => $request->phone,
                    'address' => $request->address,
                    'client_source_id' => $request->lead_source_id,
                    'status' => 'CREATED',
                    'create_by_id' => Auth::id(),
                ]);

                $clientId = $newClient->id;
            }

            Lead::create([
                'client_id' => $clientId,
                'lead_date' => $request->lead_date ?? now(),
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'lead_source_id' => $request->lead_source_id,
                'service_interested_id' => $request->service_interested_id,
                'site_location' => $request->site_location,
                'city' => $request->city,
                'estimated_value' => $request->estimated_value,
                'lead_status_id' => $request->lead_status_id,
                'salesperson_id' => $request->salesperson_id,
                'notes' => $request->notes,
                'status' => 'active',
                'created_by' => Auth::id()
            ]);

            return redirect()->route('leads.index')->with('success', 'Lead created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create lead: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $lead = Lead::with([
            'leadSource',
            'serviceInterested',
            'leadStatus',
            'salesperson',
            'createdBy',
            'leadFollowups',
            'quotations',
            'proformas',
            'invoices'
        ])->findOrFail($id);

        $data = [
            'lead' => $lead,
            'leadSources' => LeadSource::all(),
            'leadStatuses' => LeadStatus::all(),
            'serviceInteresteds' => ServiceInterested::all(),
            'salespeople' => $this->getSalespeople()
        ];

        return view('pages.leads.show')->with($data);
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);

        $data = [
            'object' => $lead,
            'clients' => ProjectClient::orderBy('first_name')->get(),
            'leadSources' => LeadSource::all(),
            'leadStatuses' => LeadStatus::all(),
            'serviceInteresteds' => ServiceInterested::all(),
            'salespeople' => $this->getSalespeople()
        ];

        return view('pages.leads.form')->with($data);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
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

        try {
            $lead->update([
                'client_id' => $request->client_id,
                'lead_date' => $request->lead_date,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'lead_source_id' => $request->lead_source_id,
                'service_interested_id' => $request->service_interested_id,
                'site_location' => $request->site_location,
                'city' => $request->city,
                'estimated_value' => $request->estimated_value,
                'lead_status_id' => $request->lead_status_id,
                'salesperson_id' => $request->salesperson_id,
                'notes' => $request->notes
            ]);

            return redirect()->route('leads.index')->with('success', 'Lead updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update lead: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $lead = Lead::findOrFail($id);
            $lead->delete();

            return back()->with('success', 'Lead deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete lead: ' . $e->getMessage());
        }
    }

    /**
     * Store a new follow-up for a lead
     */
    public function storeFollowup(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
            'followup_date' => 'required|date',
            'details_discussion' => 'required|string',
            'outcome' => 'nullable|string',
            'next_step' => 'nullable|string',
        ]);

        try {
            SalesLeadFollowup::create([
                'lead_id' => $lead->id,
                'lead_name' => $lead->name,
                'client_id' => $lead->client_id,
                'details_discussion' => $request->details_discussion,
                'outcome' => $request->outcome,
                'next_step' => $request->next_step,
                'followup_date' => $request->followup_date,
            ]);

            return back()->with('success', 'Follow-up added successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add follow-up: ' . $e->getMessage());
        }
    }

    /**
     * Mark a follow-up as attended/completed
     */
    public function attendFollowup(Request $request, $leadId, $followupId)
    {
        $lead = Lead::findOrFail($leadId);
        $followup = SalesLeadFollowup::where('lead_id', $leadId)->findOrFail($followupId);

        $request->validate([
            'status' => 'required|in:completed,cancelled,rescheduled',
            'outcome' => 'required|string',
            'remarks' => 'nullable|string',
            'update_lead_status' => 'nullable|exists:lead_statuses,id',
            'schedule_next_followup' => 'nullable|boolean',
            'next_followup_date' => 'required_if:schedule_next_followup,1|nullable|date|after:today',
            'next_followup_action' => 'nullable|string',
        ]);

        try {
            // Update the follow-up
            $followup->update([
                'status' => $request->status,
                'outcome' => $request->outcome,
                'details_discussion' => $request->remarks ? $followup->details_discussion . "\n\n--- Attended ---\n" . $request->remarks : $followup->details_discussion,
                'attended_at' => now(),
                'attended_by' => Auth::id(),
            ]);

            // Update lead status if requested
            if ($request->update_lead_status) {
                $lead->update(['lead_status_id' => $request->update_lead_status]);
            }

            // Schedule next follow-up if requested
            if ($request->schedule_next_followup && $request->next_followup_date) {
                SalesLeadFollowup::create([
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->name,
                    'client_id' => $lead->client_id,
                    'details_discussion' => $request->next_followup_action ?? 'Follow-up scheduled after previous attendance',
                    'next_step' => $request->next_followup_action,
                    'followup_date' => $request->next_followup_date,
                    'status' => 'pending',
                ]);
            }

            $statusMessage = match($request->status) {
                'completed' => 'Follow-up marked as completed!',
                'cancelled' => 'Follow-up cancelled.',
                'rescheduled' => 'Follow-up rescheduled.',
                default => 'Follow-up updated!'
            };

            return back()->with('success', $statusMessage);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update follow-up: ' . $e->getMessage());
        }
    }

    /**
     * Link an existing project to the lead
     */
    public function linkProject(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $lead->update(['project_id' => $request->project_id]);

        return back()->with('success', 'Project linked successfully.');
    }

    /**
     * Unlink project from the lead
     */
    public function unlinkProject($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->update(['project_id' => null]);

        return back()->with('success', 'Project unlinked successfully.');
    }

    /**
     * Create a new project from the lead
     */
    public function createProject(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
            'project_name' => 'required|string|max:100',
            'project_type_id' => 'required|exists:project_types,id',
            'service_type_id' => 'nullable|exists:service_types,id',
            'start_date' => 'required|date',
            'expected_end_date' => 'required|date|after_or_equal:start_date',
            'contract_value' => 'nullable|numeric|min:0',
        ]);

        try {
            // Generate document number
            $lastProject = Project::orderBy('id', 'desc')->first();
            $nextId = $lastProject ? $lastProject->id + 1 : 1;
            $documentNumber = 'PCT/' . $nextId . '/' . date('Y');

            // Create the project
            $project = Project::create([
                'document_number' => $documentNumber,
                'project_name' => $request->project_name,
                'client_id' => $lead->client_id,
                'project_type_id' => $request->project_type_id,
                'service_type_id' => $request->service_type_id,
                'start_date' => $request->start_date,
                'expected_end_date' => $request->expected_end_date,
                'contract_value' => $request->contract_value ?? $lead->estimated_value,
                'salesperson_id' => $lead->salesperson_id,
                'status' => 'pending',
                'create_by_id' => Auth::id(),
            ]);

            // Link project to lead
            $lead->update(['project_id' => $project->id]);

            return back()->with('success', 'Project created and linked successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create project: ' . $e->getMessage());
        }
    }

    /**
     * Add a project cost from the lead page
     */
    public function addProjectCost(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        if (!$lead->project_id) {
            return back()->with('error', 'No project linked to this lead.');
        }

        $request->validate([
            'cost_category_id' => 'required|exists:cost_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            \App\Models\ProjectExpense::create([
                'project_id' => $lead->project_id,
                'cost_category_id' => $request->cost_category_id,
                'expense_date' => $request->expense_date,
                'amount' => $request->amount,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ]);

            return back()->with('success', 'Project cost added successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add cost: ' . $e->getMessage());
        }
    }

    /**
     * Get a project cost for editing (AJAX)
     */
    public function getProjectCost($id, $costId)
    {
        $lead = Lead::findOrFail($id);

        if (!$lead->project_id) {
            return response()->json(['error' => 'No project linked'], 400);
        }

        $cost = \App\Models\ProjectExpense::where('id', $costId)
            ->where('project_id', $lead->project_id)
            ->firstOrFail();

        return response()->json([
            'id' => $cost->id,
            'cost_category_id' => $cost->cost_category_id,
            'expense_date' => $cost->expense_date->format('Y-m-d'),
            'amount' => $cost->amount,
            'description' => $cost->description,
            'remarks' => $cost->remarks,
        ]);
    }

    /**
     * Update a project cost from the lead page
     */
    public function updateProjectCost(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        if (!$lead->project_id) {
            return back()->with('error', 'No project linked to this lead.');
        }

        $request->validate([
            'cost_id' => 'required|exists:project_expenses,id',
            'cost_category_id' => 'required|exists:cost_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $cost = \App\Models\ProjectExpense::where('id', $request->cost_id)
                ->where('project_id', $lead->project_id)
                ->firstOrFail();

            $cost->update([
                'cost_category_id' => $request->cost_category_id,
                'expense_date' => $request->expense_date,
                'amount' => $request->amount,
                'description' => $request->description,
                'remarks' => $request->remarks,
            ]);

            return back()->with('success', 'Project cost updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update cost: ' . $e->getMessage());
        }
    }

    /**
     * Delete a project cost from the lead page (AJAX)
     */
    public function deleteProjectCost($id, $costId)
    {
        $lead = Lead::findOrFail($id);

        if (!$lead->project_id) {
            return response()->json(['success' => false, 'message' => 'No project linked'], 400);
        }

        try {
            $cost = \App\Models\ProjectExpense::where('id', $costId)
                ->where('project_id', $lead->project_id)
                ->firstOrFail();

            $cost->delete();

            return response()->json(['success' => true, 'message' => 'Cost deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
