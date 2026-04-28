<?php

namespace App\Http\Controllers;

use App\Models\FieldMarketingService;
use App\Models\FieldMarketingSession;
use App\Models\FieldMarketingTarget;
use App\Models\FieldMarketingVisit;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FieldMarketingController extends Controller
{
    // ─── Main dashboard (all tabs) ───────────────────────────────────────────

    public function index(Request $request)
    {
        $month  = $request->get('month', now()->format('Y-m'));
        $tab    = $request->get('tab', 'sessions');
        [$year, $mon] = explode('-', $month);

        $isFieldOfficer = $this->isFieldOfficer();
        $officers = $this->getOfficers();
        $data = compact('month', 'tab', 'year', 'mon', 'officers', 'isFieldOfficer');

        switch ($tab) {
            case 'targets':
                $data += $this->targetsData((int)$year, (int)$mon, $officers);
                break;
            case 'stats':
                $data += $this->statsData((int)$year, (int)$mon);
                break;
            case 'visits':
                $data += $this->allVisitsData($request, (int)$year, (int)$mon);
                break;
            case 'services':
                $data['services'] = FieldMarketingService::orderBy('sort_order')->orderBy('name')->get();
                break;
            default: // sessions
                $data += $this->sessionsData($request, (int)$year, (int)$mon);
                break;
        }

        return view('pages.field_marketing.index', $data);
    }

    private function sessionsData(Request $request, int $year, int $mon): array
    {
        $query = FieldMarketingSession::with('officer')
            ->withCount([
                'visits',
                'visits as interested_count' => fn($q) => $q->where('status', 'interested'),
                'visits as converted_count'  => fn($q) => $q->where('status', 'converted'),
            ])
            ->whereYear('date', $year)
            ->whereMonth('date', $mon);

        // Field officers only see their own sessions
        if ($this->isFieldOfficer()) {
            $query->where('officer_id', Auth::id());
        } elseif ($request->officer_id) {
            $query->where('officer_id', $request->officer_id);
        }

        return ['sessions' => $query->orderBy('date', 'desc')->get()];
    }

    private function targetsData(int $year, int $mon, $officers): array
    {
        $targets = FieldMarketingTarget::where('year', $year)->where('month', $mon)
            ->get()->keyBy('officer_id');

        $visitCounts = FieldMarketingSession::withCount('visits')
            ->whereYear('date', $year)->whereMonth('date', $mon)
            ->get()->groupBy('officer_id')
            ->map(fn($sessions) => $sessions->sum('visits_count'));

        $convertedCounts = FieldMarketingVisit::query()
            ->join('field_marketing_sessions', 'field_marketing_sessions.id', '=', 'field_marketing_visits.session_id')
            ->where('field_marketing_visits.status', 'converted')
            ->whereYear('field_marketing_sessions.date', $year)
            ->whereMonth('field_marketing_sessions.date', $mon)
            ->select('field_marketing_sessions.officer_id', DB::raw('count(*) as cnt'))
            ->groupBy('field_marketing_sessions.officer_id')
            ->pluck('cnt', 'officer_id');

        return compact('targets', 'visitCounts', 'convertedCounts');
    }

    private function statsData(int $year, int $mon): array
    {
        $visits = FieldMarketingVisit::whereHas('session', fn($q) =>
            $q->whereYear('date', $year)->whereMonth('date', $mon)
        );

        $total        = (clone $visits)->count();
        $converted    = (clone $visits)->where('status', 'converted')->count();
        $interested   = (clone $visits)->where('status', 'interested')->count();
        $notInterested = (clone $visits)->where('status', 'not_interested')->count();
        $followUp     = (clone $visits)->where('status', 'follow_up')->count();

        $byOfficer = FieldMarketingSession::with('officer')
            ->withCount([
                'visits',
                'visits as converted_count' => fn($q) => $q->where('status', 'converted'),
            ])
            ->whereYear('date', $year)->whereMonth('date', $mon)
            ->get()
            ->groupBy('officer_id')
            ->map(fn($sessions) => [
                'officer'   => $sessions->first()->officer,
                'visits'    => $sessions->sum('visits_count'),
                'converted' => $sessions->sum('converted_count'),
            ])->values();

        return compact('total', 'converted', 'interested', 'notInterested', 'followUp', 'byOfficer');
    }

    private function allVisitsData(Request $request, int $year, int $mon): array
    {
        $query = FieldMarketingVisit::with(['session.officer', 'services', 'lead'])
            ->whereHas('session', fn($q) => $q->whereYear('date', $year)->whereMonth('date', $mon));

        if ($this->isFieldOfficer()) {
            $query->whereHas('session', fn($q) => $q->where('officer_id', Auth::id()));
        } elseif ($request->officer_id) {
            $query->whereHas('session', fn($q) => $q->where('officer_id', $request->officer_id));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from_date) {
            $query->whereHas('session', fn($q) => $q->where('date', '>=', $request->from_date));
        }
        if ($request->to_date) {
            $query->whereHas('session', fn($q) => $q->where('date', '<=', $request->to_date));
        }

        $allVisits     = $query->orderByDesc('id')->get();
        $visitCount    = $allVisits->count();

        return compact('allVisits', 'visitCount');
    }

    // ─── Sessions ────────────────────────────────────────────────────────────

    public function storeSession(Request $request)
    {
        $request->validate([
            'officer_id' => 'nullable|exists:users,id',
            'area'       => 'nullable|string|max:255',
            'date'       => 'required|date',
            'notes'      => 'nullable|string',
        ]);

        // Field officers are always assigned to themselves
        $officerId = $this->isFieldOfficer() ? Auth::id() : $request->officer_id;

        FieldMarketingSession::create([
            'officer_id' => $officerId,
            'area'       => $request->area,
            'date'       => $request->date,
            'notes'      => $request->notes,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('field_marketing.index', ['tab' => 'sessions', 'month' => substr($request->date, 0, 7)])
            ->with('success', 'Session created.');
    }

    public function showSession($id)
    {
        $session        = FieldMarketingSession::with(['officer', 'visits.services', 'visits.lead'])->findOrFail($id);
        $services       = FieldMarketingService::active()->orderBy('sort_order')->get();
        $officers       = $this->getOfficers();
        $isFieldOfficer = $this->isFieldOfficer();

        return view('pages.field_marketing.session_show', compact('session', 'services', 'officers', 'isFieldOfficer'));
    }

    public function updateSession(Request $request, $id)
    {
        $session = FieldMarketingSession::findOrFail($id);
        $request->validate([
            'officer_id' => 'required|exists:users,id',
            'area'       => 'nullable|string|max:255',
            'date'       => 'required|date',
            'status'     => 'required|in:open,closed',
            'notes'      => 'nullable|string',
        ]);

        $session->update($request->only(['officer_id', 'area', 'date', 'status', 'notes']));

        return back()->with('success', 'Session updated.');
    }

    public function destroySession($id)
    {
        FieldMarketingSession::findOrFail($id)->delete();
        return back()->with('success', 'Session deleted.');
    }

    // ─── Visits ──────────────────────────────────────────────────────────────

    public function storeVisit(Request $request, $sessionId)
    {
        FieldMarketingSession::findOrFail($sessionId);

        $request->validate([
            'business_name'    => 'required|string|max:255',
            'location'         => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:30',
            'status'           => 'required|in:interested,not_interested,follow_up,converted',
            'next_followup_date' => 'nullable|date',
            'notes'            => 'nullable|string',
            'service_ids'      => 'nullable|array',
            'service_ids.*'    => 'exists:field_marketing_services,id',
        ]);

        $visit = FieldMarketingVisit::create([
            'session_id'       => $sessionId,
            'business_name'    => $request->business_name,
            'location'         => $request->location,
            'phone'            => $request->phone,
            'status'           => $request->status,
            'next_followup_date' => $request->next_followup_date,
            'notes'            => $request->notes,
            'created_by'       => Auth::id(),
        ]);

        if ($request->service_ids) {
            $visit->services()->sync($request->service_ids);
        }

        return back()->with('success', 'Visit added.');
    }

    public function updateVisit(Request $request, $id)
    {
        $visit = FieldMarketingVisit::findOrFail($id);

        $request->validate([
            'business_name'    => 'required|string|max:255',
            'location'         => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:30',
            'status'           => 'required|in:interested,not_interested,follow_up,converted',
            'next_followup_date' => 'nullable|date',
            'notes'            => 'nullable|string',
            'service_ids'      => 'nullable|array',
            'service_ids.*'    => 'exists:field_marketing_services,id',
        ]);

        $visit->update($request->only([
            'business_name', 'location', 'phone', 'status', 'next_followup_date', 'notes',
        ]));
        $visit->services()->sync($request->service_ids ?? []);

        return back()->with('success', 'Visit updated.');
    }

    public function destroyVisit($id)
    {
        FieldMarketingVisit::findOrFail($id)->delete();
        return back()->with('success', 'Visit deleted.');
    }

    // ─── Targets ─────────────────────────────────────────────────────────────

    public function storeTarget(Request $request)
    {
        $request->validate([
            'officer_id'          => 'required|exists:users,id',
            'month'               => 'required|date_format:Y-m',
            'target_visits'       => 'required|integer|min:0',
            'target_conversions'  => 'required|integer|min:0',
        ]);

        [$year, $mon] = explode('-', $request->month);

        FieldMarketingTarget::updateOrCreate(
            ['officer_id' => $request->officer_id, 'year' => $year, 'month' => $mon],
            ['target_visits' => $request->target_visits, 'target_conversions' => $request->target_conversions, 'created_by' => Auth::id()]
        );

        return redirect()->route('field_marketing.index', ['tab' => 'targets', 'month' => $request->month])
            ->with('success', 'Target saved.');
    }

    // ─── Services ────────────────────────────────────────────────────────────

    public function storeService(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:field_marketing_services,name']);
        $maxOrder = FieldMarketingService::max('sort_order') ?? 0;
        FieldMarketingService::create(['name' => strtoupper(trim($request->name)), 'sort_order' => $maxOrder + 1]);

        return redirect()->route('field_marketing.index', ['tab' => 'services', 'month' => $request->month])
            ->with('success', 'Service added.');
    }

    public function updateService(Request $request, $id)
    {
        $service = FieldMarketingService::findOrFail($id);
        $request->validate(['name' => 'required|string|max:100|unique:field_marketing_services,name,' . $id]);
        $service->update(['name' => strtoupper(trim($request->name))]);

        return redirect()->route('field_marketing.index', ['tab' => 'services', 'month' => $request->month])
            ->with('success', 'Service updated.');
    }

    public function destroyService($id)
    {
        FieldMarketingService::findOrFail($id)->delete();
        return back()->with('success', 'Service deleted.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getOfficers()
    {
        return User::whereHas('roles', fn($q) =>
            $q->where('roles.id', 13)
              ->orWhere('roles.name', 'like', '%sales%')
              ->orWhere('roles.name', 'like', '%field%')
              ->orWhere('roles.name', 'like', '%market%')
        )->orderBy('name')->get();
    }

    // Returns true if the logged-in user is a field officer (Sales & Marketing)
    // and does NOT hold any admin/manager role.
    private function isFieldOfficer(): bool
    {
        $user = Auth::user();
        $managerRoleIds = [1, 2, 7, 9, 12, 14, 15, 16]; // Admin, MD, BizDev, etc.
        $isManager = $user->roles()->whereIn('roles.id', $managerRoleIds)->exists();
        return !$isManager && $user->roles()->where('roles.id', 13)->exists();
    }
}
