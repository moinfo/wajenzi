<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin/HR bulk supervisor assignment.
 *
 * Replaces typing supervisor_id one staff at a time. Lists every active staff
 * member with a "Supervisor" dropdown next to their row and saves the lot in
 * one POST.
 */
class SupervisorAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAllowed();

        $users = User::with('supervisor:id,name')
            ->where('status', 'ACTIVE')
            ->where('type', 'STAFF')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'supervisor_id', 'department_id']);

        $candidates = User::where('status', 'ACTIVE')->where('type', 'STAFF')
            ->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total'    => $users->count(),
            'assigned' => $users->whereNotNull('supervisor_id')->count(),
            'missing'  => $users->whereNull('supervisor_id')->count(),
        ];

        return view('pages.settings.settings_supervisor_assignments', [
            'users'      => $users,
            'candidates' => $candidates,
            'stats'      => $stats,
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureAllowed();

        $request->validate([
            'assignments'              => 'required|array',
            'assignments.*.supervisor_id' => 'nullable|exists:users,id',
        ]);

        $changed = 0;
        DB::transaction(function () use ($request, &$changed) {
            foreach ($request->input('assignments', []) as $userId => $row) {
                $newSupId = $row['supervisor_id'] ?? null;
                $newSupId = $newSupId === '' ? null : $newSupId;

                // Guard: user can't be their own supervisor
                if ($newSupId == $userId) {
                    continue;
                }

                $affected = User::where('id', $userId)->update(['supervisor_id' => $newSupId]);
                $changed += $affected;
            }
        });

        return back()->with('success', "Saved supervisor assignments for {$changed} staff.");
    }

    /**
     * Only admin / HR roles can manage supervisor assignments.
     */
    protected function ensureAllowed(): void
    {
        $user = auth()->user();
        if (!$user->hasAnyRole([
            'System Administrator', 'Managing Director', 'CEO',
            'Chief Executive Officer', 'HR Generalist', 'General Manager',
        ])) {
            abort(403, 'Only HR/Admin can manage supervisor assignments.');
        }
    }
}
