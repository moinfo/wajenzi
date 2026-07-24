<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sanctum-authenticated KPI reviewer-matrix API (PUBLIC-V1).
 *
 * 1:1 mirror of App\Http\Controllers\SupervisorAssignmentController (the
 * web/portal controller). It edits the `supervisor_id` column ON the `users`
 * table (User::supervisor() belongsTo) — this is the KPI-reviewer matrix, NOT
 * the construction SiteSupervisorAssignment feature.
 *
 * Both endpoints are gated by the SAME role set as the web controller's
 * ensureAllowed() — note the set INCLUDES 'General Manager', unlike the
 * template / all-reviews gates.
 */
class SupervisorAssignmentApiController extends Controller
{
    /**
     * GET performance/supervisor-assignments
     *
     * Active STAFF users with their current supervisor, the candidate list to
     * pick from, and assigned/missing stats.
     */
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAllowed()) {
            return $denied;
        }

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

        return response()->json([
            'success' => true,
            'data'    => [
                'users' => $users->map(function (User $u) {
                    return [
                        'id'            => $u->id,
                        'name'          => $u->name,
                        'email'         => $u->email,
                        'supervisor_id' => $u->supervisor_id,
                        'department_id' => $u->department_id,
                        'supervisor'    => $u->supervisor
                            ? ['id' => $u->supervisor->id, 'name' => $u->supervisor->name]
                            : null,
                    ];
                })->values(),
                'candidates' => $candidates->map(fn (User $c) => [
                    'id'   => $c->id,
                    'name' => $c->name,
                ])->values(),
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * PATCH performance/supervisor-assignments
     *
     * Bulk-save supervisor assignments. The `assignments` payload is a MAP keyed
     * by userId, each entry `{supervisor_id: nullable exists:users,id}` — mirrors
     * the web form's `assignments[<userId>][supervisor_id]` name shape. A user
     * cannot be their own supervisor (skipped). Returns changed_count.
     */
    public function update(Request $request): JsonResponse
    {
        if ($denied = $this->ensureAllowed()) {
            return $denied;
        }

        $request->validate([
            'assignments'                 => 'required|array',
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

        return response()->json([
            'success'       => true,
            'message'       => "Saved supervisor assignments for {$changed} staff.",
            'changed_count' => $changed,
        ]);
    }

    /**
     * Only admin / HR roles can manage supervisor assignments.
     *
     * Returns a 403 JsonResponse when refused, or null when allowed — mirrors
     * SupervisorAssignmentController::ensureAllowed() role set (includes
     * 'General Manager').
     */
    protected function ensureAllowed(): ?JsonResponse
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole([
            'System Administrator', 'Managing Director', 'CEO',
            'Chief Executive Officer', 'HR Generalist', 'General Manager',
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Only HR/Admin can manage supervisor assignments.',
            ], 403);
        }

        return null;
    }
}
