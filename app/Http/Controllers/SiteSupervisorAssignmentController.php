<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteSupervisorAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class SiteSupervisorAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View Site Assignments')->only(['index', 'show', 'history']);
        $this->middleware('permission:Add Site Assignments')->only(['create', 'store']);
        $this->middleware('permission:Edit Site Assignments')->only(['edit', 'update']);
        $this->middleware('permission:Delete Site Assignments')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = SiteSupervisorAssignment::with(['site', 'supervisor', 'assignedBy'])
            ->where('is_active', true);

        // Apply filters
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('supervisor_id')) {
            $query->where('user_id', $request->supervisor_id);
        }

        $assignments = $query->orderBy('assigned_from', 'desc')->paginate(15);
        
        // Get sites without active supervisors
        $unassignedSites = Site::active()
            ->whereDoesntHave('currentSupervisorAssignment')
            ->get();

        $sites = Site::active()->get();
        $supervisors = $this->getSupervisors();

        return view('pages.sites.assignments.index', compact('assignments', 'unassignedSites', 'sites', 'supervisors'));
    }

    public function create()
    {
        // Get only sites without active supervisors
        $availableSites = Site::active()
            ->whereDoesntHave('currentSupervisorAssignment')
            ->get();

        $supervisors = $this->getSupervisors();

        return view('pages.sites.assignments.create', compact('availableSites', 'supervisors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'user_id' => 'required|exists:users,id',
            'assigned_from' => 'required|date_format:d/m/Y',
            'assigned_to' => 'nullable|date|after:assigned_from',
            'notes' => 'nullable|string'
        ]);

        // Check if site already has active assignment
        $existingAssignment = SiteSupervisorAssignment::where('site_id', $validated['site_id'])
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()
                ->with('error', 'This site already has an active supervisor. Please end the current assignment first.');
        }

        // Convert dates
        $assignedFrom = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['assigned_from'])->format('Y-m-d');
        $assignedTo = null;
        if ($request->filled('assigned_to')) {
            // assigned_to is already in Y-m-d format from date validation
            $assignedTo = $validated['assigned_to'];
        }

        SiteSupervisorAssignment::create([
            'site_id' => $validated['site_id'],
            'user_id' => $validated['user_id'],
            'assigned_from' => $assignedFrom,
            'assigned_to' => $assignedTo,
            'is_active' => true,
            'assigned_by' => Auth::id(),
            'notes' => $validated['notes']
        ]);

        return redirect()->route('site-supervisor-assignments.index')
            ->with('success', 'Supervisor assigned successfully.');
    }

    public function edit(SiteSupervisorAssignment $siteSupervisorAssignment)
    {
        if (!$siteSupervisorAssignment->is_active) {
            return redirect()->back()
                ->with('error', 'Cannot edit inactive assignments.');
        }

        $supervisors = $this->getSupervisors();

        return view('pages.sites.assignments.edit', [
            'assignment' => $siteSupervisorAssignment,
            'supervisors' => $supervisors
        ]);
    }

    public function update(Request $request, SiteSupervisorAssignment $siteSupervisorAssignment)
    {
        if (!$siteSupervisorAssignment->is_active) {
            return redirect()->back()
                ->with('error', 'Cannot update inactive assignments.');
        }

        $validated = $request->validate([
            'assigned_to' => 'nullable|date_format:d/m/Y|after:assigned_from',
            'notes' => 'nullable|string'
        ]);

        $data = ['notes' => $validated['notes']];
        
        if ($request->filled('assigned_to')) {
            $data['assigned_to'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['assigned_to'])->format('Y-m-d');
            $data['is_active'] = false; // End the assignment if end date is set
        }

        $siteSupervisorAssignment->update($data);

        return redirect()->route('site-supervisor-assignments.index')
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(SiteSupervisorAssignment $siteSupervisorAssignment)
    {
        if ($siteSupervisorAssignment->is_active) {
            // End the assignment instead of deleting
            $siteSupervisorAssignment->deactivate();
            return redirect()->route('site-supervisor-assignments.index')
                ->with('success', 'Assignment ended successfully.');
        }

        return redirect()->back()
            ->with('error', 'Cannot delete active assignments.');
    }

    public function history(Site $site)
    {
        $assignments = $site->supervisorAssignments()
            ->with(['supervisor', 'assignedBy'])
            ->orderBy('assigned_from', 'desc')
            ->paginate(15);

        return view('pages.sites.assignments.history', compact('site', 'assignments'));
    }

    private function getSupervisors()
    {
        // Get all active users
        return User::where('status', 'ACTIVE')->orderBy('name')->get();
    }
}