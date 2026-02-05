<?php

namespace App\Http\Controllers;

use App\Models\LaborContract;
use App\Models\LaborWorkLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LaborWorkLogController extends Controller
{
    /**
     * Display listing of work logs
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'LaborWorkLog')) {
            return back();
        }

        $startDate = $request->input('start_date') ?? date('Y-m-01');
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $contractId = $request->input('contract_id');
        $projectId = $request->input('project_id');

        $query = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])
            ->whereBetween('log_date', [$startDate, $endDate])
            ->orderBy('log_date', 'desc');

        if ($contractId) {
            $query->where('labor_contract_id', $contractId);
        }
        if ($projectId) {
            $query->whereHas('contract', fn($q) => $q->where('project_id', $projectId));
        }

        $logs = $query->get();

        // Get active contracts for dropdown
        $activeContracts = LaborContract::with(['project', 'artisan'])
            ->whereIn('status', ['active', 'on_hold'])
            ->orderBy('contract_number')
            ->get();

        $projects = Project::orderBy('project_name')->get();

        return view('labor.logs.index')->with([
            'logs' => $logs,
            'activeContracts' => $activeContracts,
            'projects' => $projects,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_contract' => $contractId,
            'selected_project' => $projectId
        ]);
    }

    /**
     * Show form to create work log for a contract
     */
    public function create($contractId)
    {
        $contract = LaborContract::with(['project', 'artisan'])->findOrFail($contractId);

        if (!$contract->isActive() && !$contract->isOnHold()) {
            return back()->with('error', 'Work logs can only be added to active or on-hold contracts');
        }

        // Get the latest log for context
        $lastLog = $contract->workLogs()->orderBy('log_date', 'desc')->first();

        return view('labor.logs.create')->with([
            'contract' => $contract,
            'lastLog' => $lastLog
        ]);
    }

    /**
     * Store new work log
     */
    public function store(Request $request)
    {
        $request->validate([
            'labor_contract_id' => 'required|exists:labor_contracts,id',
            'log_date' => 'required|date',
            'work_done' => 'required|string|min:10',
            'workers_present' => 'required|integer|min:1'
        ]);

        $contract = LaborContract::findOrFail($request->labor_contract_id);

        if (!$contract->isActive() && !$contract->isOnHold()) {
            return back()->with('error', 'Work logs can only be added to active or on-hold contracts');
        }

        try {
            $materialsUsedRaw = $request->input('materials_used');
            $materialsUsed = null;
            if ($materialsUsedRaw) {
                if (is_string($materialsUsedRaw)) {
                    $materialsUsed = json_decode($materialsUsedRaw, true);
                } else {
                    $materialsUsed = $materialsUsedRaw;
                }
            }

            // Handle photo uploads
            $photos = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = time() . '_' . $photo->getClientOriginalName();
                    $filePath = $photo->storeAs('uploads/labor_logs', $fileName, 'public');
                    $photos[] = '/storage/' . $filePath;
                }
            }

            LaborWorkLog::create([
                'labor_contract_id' => $request->labor_contract_id,
                'log_date' => $request->log_date,
                'work_done' => $request->work_done,
                'workers_present' => $request->workers_present,
                'hours_worked' => $request->hours_worked,
                'progress_percentage' => $request->progress_percentage,
                'challenges' => $request->challenges,
                'materials_used' => $materialsUsed,
                'photos' => $photos ?: null,
                'weather_conditions' => $request->weather_conditions,
                'notes' => $request->notes
            ]);

            $this->notify('Work log added', 'Success', 'success');
            return redirect()->route('labor.contracts.show', $contract->id);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create work log: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show work log details
     */
    public function show($id)
    {
        $log = LaborWorkLog::with(['contract.project', 'contract.artisan', 'logger'])->findOrFail($id);

        return view('labor.logs.show')->with([
            'log' => $log
        ]);
    }

    /**
     * Show form to edit work log
     */
    public function edit($id)
    {
        $log = LaborWorkLog::with(['contract'])->findOrFail($id);

        // Can only edit logs from the last 3 days
        if ($log->log_date->diffInDays(now()) > 3) {
            return back()->with('error', 'Work logs older than 3 days cannot be edited');
        }

        return view('labor.logs.edit')->with([
            'log' => $log
        ]);
    }

    /**
     * Update work log
     */
    public function update(Request $request, $id)
    {
        $log = LaborWorkLog::findOrFail($id);

        if ($log->log_date->diffInDays(now()) > 3) {
            return back()->with('error', 'Work logs older than 3 days cannot be edited');
        }

        $request->validate([
            'work_done' => 'required|string|min:10',
            'workers_present' => 'required|integer|min:1'
        ]);

        try {
            $materialsUsedRaw = $request->input('materials_used');
            $materialsUsed = null;
            if ($materialsUsedRaw) {
                if (is_string($materialsUsedRaw)) {
                    $materialsUsed = json_decode($materialsUsedRaw, true);
                } else {
                    $materialsUsed = $materialsUsedRaw;
                }
            }

            // Handle new photo uploads
            $photos = $log->photos ?? [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = time() . '_' . $photo->getClientOriginalName();
                    $filePath = $photo->storeAs('uploads/labor_logs', $fileName, 'public');
                    $photos[] = '/storage/' . $filePath;
                }
            }

            $log->update([
                'work_done' => $request->work_done,
                'workers_present' => $request->workers_present,
                'hours_worked' => $request->hours_worked,
                'progress_percentage' => $request->progress_percentage,
                'challenges' => $request->challenges,
                'materials_used' => $materialsUsed,
                'photos' => $photos ?: null,
                'weather_conditions' => $request->weather_conditions,
                'notes' => $request->notes
            ]);

            $this->notify('Work log updated', 'Success', 'success');
            return redirect()->route('labor.contracts.show', $log->labor_contract_id);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update work log: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete work log
     */
    public function destroy($id)
    {
        $log = LaborWorkLog::findOrFail($id);

        if ($log->log_date->diffInDays(now()) > 3) {
            return back()->with('error', 'Work logs older than 3 days cannot be deleted');
        }

        $contractId = $log->labor_contract_id;

        // Delete associated photos
        if ($log->photos) {
            foreach ($log->photos as $photo) {
                $path = str_replace('/storage/', '', $photo);
                Storage::disk('public')->delete($path);
            }
        }

        $log->delete();

        $this->notify('Work log deleted', 'Success', 'success');
        return redirect()->route('labor.contracts.show', $contractId);
    }

    /**
     * View work logs for a specific contract (timeline view)
     */
    public function contractLogs($contractId)
    {
        $contract = LaborContract::with(['project', 'artisan'])->findOrFail($contractId);
        $logs = $contract->workLogs()->with('logger')->orderBy('log_date', 'desc')->paginate(20);

        return view('labor.logs.contract_timeline')->with([
            'contract' => $contract,
            'logs' => $logs
        ]);
    }
}
