<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldMarketingService;
use App\Models\FieldMarketingSession;
use App\Models\FieldMarketingTarget;
use App\Models\FieldMarketingVisit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Field Marketing API (mobile).
 *
 * Mirrors {@see \App\Http\Controllers\FieldMarketingController} (portal).
 *
 * Resources:
 *   - sessions  (officer day-of-fieldwork in an area)
 *   - visits    (business contacts inside a session)
 *   - services  (configurable service tags)
 *   - targets   (monthly per-officer targets)
 */
class FieldMarketingApiController extends Controller
{
    // ────────────────────────── Index / stats ──────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            [$year, $mon] = $this->parseMonth($month);

            $sessions = $this->buildSessions($request, (int) $year, (int) $mon);
            $stats    = $this->buildStats((int) $year, (int) $mon);

            return response()->json([
                'success' => true,
                'data' => [
                    'month'    => $month,
                    'sessions' => $sessions,
                    'stats'    => $stats,
                    'is_field_officer' => $this->isFieldOfficer(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('FieldMarketingApi index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch field marketing data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = $this->parseMonth($month);

        return response()->json([
            'success' => true,
            'data' => $this->buildStats((int) $year, (int) $mon),
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $officers = $this->getOfficers()->map(fn ($u) => [
            'id'   => $u->id,
            'name' => $u->name,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'officers' => $officers,
                'services' => FieldMarketingService::active()
                    ->orderBy('sort_order')->orderBy('name')
                    ->get(['id', 'name', 'sort_order', 'status']),
                'statuses' => [
                    ['value' => 'interested',     'label' => 'Interested'],
                    ['value' => 'not_interested', 'label' => 'Not Interested'],
                    ['value' => 'follow_up',      'label' => 'Follow Up'],
                    ['value' => 'converted',      'label' => 'Converted'],
                ],
                'is_field_officer' => $this->isFieldOfficer(),
            ],
        ]);
    }

    // ────────────────────────── Sessions CRUD ──────────────────────────

    public function showSession(int $id): JsonResponse
    {
        $session = FieldMarketingSession::with([
            'officer:id,name',
            'visits.services:id,name',
            'visits.lead:id,lead_number,name',
        ])->find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSession($session, withVisits: true),
        ]);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'officer_id' => 'nullable|exists:users,id',
            'area'       => 'nullable|string|max:255',
            'date'       => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        $officerId = $this->isFieldOfficer() ? Auth::id() : ($validated['officer_id'] ?? Auth::id());

        $session = FieldMarketingSession::create([
            'officer_id' => $officerId,
            'area'       => $validated['area'] ?? null,
            'date'       => $validated['date'],
            'notes'      => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        $session->load(['officer:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Session created',
            'data' => $this->transformSession($session),
        ], 201);
    }

    public function updateSession(Request $request, int $id): JsonResponse
    {
        $session = FieldMarketingSession::find($id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        // Field officers may only edit their own sessions.
        if ($this->isFieldOfficer()) {
            abort_unless($session->officer_id === Auth::id(), 403, 'You can only edit your own session.');
        }

        $validated = $request->validate([
            'officer_id' => 'sometimes|required|exists:users,id',
            'area'       => 'nullable|string|max:255',
            'date'       => 'sometimes|required|date',
            'status'     => 'sometimes|required|in:open,closed',
            'notes'      => 'nullable|string',
        ]);

        // Field officers cannot reassign sessions to other officers.
        if ($this->isFieldOfficer()) {
            unset($validated['officer_id']);
        }

        $session->update($validated);
        $session->load(['officer:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Session updated',
            'data' => $this->transformSession($session),
        ]);
    }

    public function destroySession(int $id): JsonResponse
    {
        $session = FieldMarketingSession::find($id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }
        // Field officers may only delete their own sessions.
        if ($this->isFieldOfficer()) {
            abort_unless($session->officer_id === Auth::id(), 403, 'You can only delete your own session.');
        }
        $session->delete();
        return response()->json(['success' => true, 'message' => 'Session deleted']);
    }

    // ────────────────────────── Visits CRUD ──────────────────────────

    public function storeVisit(Request $request, int $sessionId): JsonResponse
    {
        $session = FieldMarketingSession::find($sessionId);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $validated = $request->validate([
            'business_name'      => 'required|string|max:255',
            'location'           => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:30',
            'status'             => 'required|in:interested,not_interested,follow_up,converted',
            'next_followup_date' => 'nullable|date',
            'notes'              => 'nullable|string',
            'service_ids'        => 'nullable|array',
            'service_ids.*'      => 'integer|exists:field_marketing_services,id',
        ]);

        $visit = FieldMarketingVisit::create([
            'session_id'         => $sessionId,
            'business_name'      => $validated['business_name'],
            'location'           => $validated['location'] ?? null,
            'phone'              => $validated['phone'] ?? null,
            'status'             => $validated['status'],
            'next_followup_date' => $validated['next_followup_date'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        if (!empty($validated['service_ids'])) {
            $visit->services()->sync($validated['service_ids']);
        }

        $visit->load(['services:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Visit added',
            'data' => $this->transformVisit($visit),
        ], 201);
    }

    public function updateVisit(Request $request, int $id): JsonResponse
    {
        $visit = FieldMarketingVisit::find($id);
        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'Visit not found'], 404);
        }

        $validated = $request->validate([
            'business_name'      => 'sometimes|required|string|max:255',
            'location'           => 'nullable|string|max:255',
            'phone'              => 'nullable|string|max:30',
            'status'             => 'sometimes|required|in:interested,not_interested,follow_up,converted',
            'next_followup_date' => 'nullable|date',
            'notes'              => 'nullable|string',
            'service_ids'        => 'nullable|array',
            'service_ids.*'      => 'integer|exists:field_marketing_services,id',
        ]);

        $visit->update(collect($validated)->except('service_ids')->toArray());
        if ($request->has('service_ids')) {
            $visit->services()->sync($validated['service_ids'] ?? []);
        }
        $visit->load(['services:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Visit updated',
            'data' => $this->transformVisit($visit),
        ]);
    }

    public function destroyVisit(int $id): JsonResponse
    {
        $visit = FieldMarketingVisit::find($id);
        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'Visit not found'], 404);
        }
        // Field officers may only delete their own visit records.
        if ($this->isFieldOfficer()) {
            abort_unless($visit->created_by === Auth::id(), 403, 'You can only delete visits you logged.');
        }
        $visit->delete();
        return response()->json(['success' => true, 'message' => 'Visit deleted']);
    }

    // ────────────────────────── Targets / Services ──────────────────────────

    public function storeTarget(Request $request): JsonResponse
    {
        // Only marketing managers may set targets — field officers shouldn't be
        // able to set their own quotas.
        abort_unless($this->isMarketingManager(), 403, 'Only marketing managers can set targets.');

        $validated = $request->validate([
            'officer_id'         => 'required|exists:users,id',
            'month'              => 'required|date_format:Y-m',
            'target_visits'      => 'required|integer|min:0',
            'target_conversions' => 'required|integer|min:0',
        ]);

        [$year, $mon] = explode('-', $validated['month']);

        $target = FieldMarketingTarget::updateOrCreate(
            ['officer_id' => $validated['officer_id'], 'year' => (int) $year, 'month' => (int) $mon],
            [
                'target_visits'      => $validated['target_visits'],
                'target_conversions' => $validated['target_conversions'],
                'created_by'         => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Target saved',
            'data' => $target,
        ]);
    }

    // ────────────────────────── Builders ──────────────────────────

    private function buildSessions(Request $request, int $year, int $mon): array
    {
        $query = FieldMarketingSession::with('officer:id,name')
            ->withCount([
                'visits',
                'visits as interested_count' => fn ($q) => $q->where('status', 'interested'),
                'visits as converted_count'  => fn ($q) => $q->where('status', 'converted'),
            ])
            ->whereYear('date', $year)
            ->whereMonth('date', $mon);

        if ($this->isFieldOfficer()) {
            $query->where('officer_id', Auth::id());
        } elseif ($request->filled('officer_id')) {
            $query->where('officer_id', $request->officer_id);
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('session_number', 'like', "%{$s}%")
                  ->orWhere('area', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        return $query->orderByDesc('date')->orderByDesc('id')->get()
            ->map(fn ($s) => $this->transformSession($s))->all();
    }

    private function buildStats(int $year, int $mon): array
    {
        $visits = FieldMarketingVisit::whereHas('session', fn ($q) =>
            $q->whereYear('date', $year)->whereMonth('date', $mon)
        );

        return [
            'total'          => (clone $visits)->count(),
            'converted'      => (clone $visits)->where('status', 'converted')->count(),
            'interested'     => (clone $visits)->where('status', 'interested')->count(),
            'follow_up'      => (clone $visits)->where('status', 'follow_up')->count(),
            'not_interested' => (clone $visits)->where('status', 'not_interested')->count(),
            'sessions'       => FieldMarketingSession::whereYear('date', $year)
                ->whereMonth('date', $mon)->count(),
        ];
    }

    // ────────────────────────── Transformers ──────────────────────────

    private function transformSession(FieldMarketingSession $session, bool $withVisits = false): array
    {
        $out = [
            'id'              => $session->id,
            'session_number'  => $session->session_number,
            'officer_id'      => $session->officer_id,
            'officer_name'    => $session->officer?->name,
            'area'            => $session->area,
            'date'            => optional($session->date)->format('Y-m-d'),
            'status'          => $session->status,
            'notes'           => $session->notes,
            'visits_count'    => (int) ($session->visits_count ?? 0),
            'interested_count' => (int) ($session->interested_count ?? 0),
            'converted_count' => (int) ($session->converted_count ?? 0),
            'created_at'      => optional($session->created_at)->toIso8601String(),
            'updated_at'      => optional($session->updated_at)->toIso8601String(),
        ];

        if ($withVisits) {
            $out['visits'] = $session->visits->map(fn ($v) => $this->transformVisit($v))->all();
        }

        return $out;
    }

    private function transformVisit(FieldMarketingVisit $visit): array
    {
        return [
            'id'                 => $visit->id,
            'session_id'         => $visit->session_id,
            'business_name'      => $visit->business_name,
            'location'           => $visit->location,
            'phone'              => $visit->phone,
            'status'             => $visit->status,
            'status_label'       => $visit->status_label,
            'next_followup_date' => optional($visit->next_followup_date)->format('Y-m-d'),
            'notes'              => $visit->notes,
            'lead_id'            => $visit->lead_id,
            'services'           => $visit->relationLoaded('services')
                ? $visit->services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all()
                : [],
            'created_at'         => optional($visit->created_at)->toIso8601String(),
        ];
    }

    // ────────────────────────── Helpers ──────────────────────────

    private function parseMonth(string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        return explode('-', $month);
    }

    private function getOfficers()
    {
        return User::whereHas('roles', fn ($q) =>
            $q->where('roles.id', 13)
              ->orWhere('roles.name', 'like', '%sales%')
              ->orWhere('roles.name', 'like', '%field%')
              ->orWhere('roles.name', 'like', '%market%')
        )->orderBy('name')->get();
    }

    private function isFieldOfficer(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        $managerRoleIds = [1, 2, 7, 9, 12, 14, 15, 16];
        $isManager = $user->roles()->whereIn('roles.id', $managerRoleIds)->exists();
        return !$isManager && $user->roles()->where('roles.id', 13)->exists();
    }

    /** Inverse of isFieldOfficer — true for everyone who isn't a pure field officer. */
    private function isMarketingManager(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        $managerRoleIds = [1, 2, 7, 9, 12, 14, 15, 16];
        return $user->roles()->whereIn('roles.id', $managerRoleIds)->exists();
    }
}
