<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\ClientSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        // Handle CRUD operations
        if ($this->handleCrud($request, 'Lead')) {
            return back();
        }

        $query = Lead::with(['clientSource', 'createdBy']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->client_source_id) {
            $query->where('client_source_id', $request->client_source_id);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(15);
        $clientSources = ClientSource::all();

        $data = [
            'leads' => $leads,
            'clientSources' => $clientSources,
            'object' => new Lead()
        ];

        return view('pages.leads.index')->with($data);
    }

    public function create()
    {
        $data = [
            'object' => new Lead(),
            'clientSources' => ClientSource::all()
        ];

        return view('pages.leads.form')->with($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'client_source_id' => 'nullable|exists:client_sources,id',
            'status' => 'required|in:active,converted,inactive'
        ]);

        try {
            Lead::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'client_source_id' => $request->client_source_id,
                'status' => $request->status,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('leads.index')->with('success', 'Lead created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create lead: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $lead = Lead::with(['clientSource', 'createdBy', 'leadFollowups'])->findOrFail($id);

        return view('pages.leads.show', compact('lead'));
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        
        $data = [
            'object' => $lead,
            'clientSources' => ClientSource::all()
        ];

        return view('pages.leads.form')->with($data);
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'client_source_id' => 'nullable|exists:client_sources,id',
            'status' => 'required|in:active,converted,inactive'
        ]);

        try {
            $lead->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'client_source_id' => $request->client_source_id,
                'status' => $request->status
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
}
