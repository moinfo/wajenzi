<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View Sites')->only(['index', 'show']);
        $this->middleware('permission:Add Sites')->only(['create', 'store']);
        $this->middleware('permission:Edit Sites')->only(['edit', 'update']);
        $this->middleware('permission:Delete Sites')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Site::with(['createdBy', 'currentSupervisor']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sites = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('pages.sites.index', compact('sites'));
    }

    public function create()
    {
        return view('pages.sites.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sites,name',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE,COMPLETED',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Dates are already in Y-m-d format from HTML date inputs

        $validated['created_by'] = Auth::id();

        $site = Site::create($validated);

        return redirect()->route('sites.index')
            ->with('success', 'Site created successfully.');
    }

    public function show(Site $site)
    {
        $site->load([
            'createdBy',
            'supervisorAssignments.supervisor',
            'supervisorAssignments.assignedBy',
            'dailyReports.preparedBy',
            'currentSupervisor'
        ]);

        $recentReports = $site->dailyReports()
            ->with(['supervisor', 'preparedBy'])
            ->orderBy('report_date', 'desc')
            ->limit(10)
            ->get();

        return view('pages.sites.show', compact('site', 'recentReports'));
    }

    public function edit(Site $site)
    {
        return view('pages.sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sites,name,' . $site->id,
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE,COMPLETED',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'actual_end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $site->update($validated);

        return redirect()->route('sites.index')
            ->with('success', 'Site updated successfully.');
    }

    public function destroy(Site $site)
    {
        if (!$site->canDelete()) {
            return redirect()->back()
                ->with('error', 'Cannot delete site with existing reports.');
        }

        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted successfully.');
    }
}