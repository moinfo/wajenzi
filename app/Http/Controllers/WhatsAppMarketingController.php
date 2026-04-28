<?php

namespace App\Http\Controllers;

use App\Models\FieldMarketingService;
use App\Models\ProjectClient;
use App\Models\User;
use App\Models\WhatsAppAdCampaign;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppContactCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsAppMarketingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'contacts');

        // Stat tiles (global)
        $totalContacts   = WhatsAppContact::count();
        $converted       = WhatsAppContact::whereNotNull('client_id')->count();
        $fromAds         = WhatsAppContact::where('source', 'whatsapp_ad')->count();
        $conversionRate  = $totalContacts > 0 ? round(($converted / $totalContacts) * 100) : 0;
        $campaignCount   = WhatsAppAdCampaign::where('status', 'active')->count();

        $services     = FieldMarketingService::active()->orderBy('sort_order')->get();
        $users        = User::where('status', 'ACTIVE')->orderBy('name')->get();
        $campaigns    = WhatsAppAdCampaign::where('status', 'active')->orderByDesc('start_date')->get();
        $allCampaigns = WhatsAppAdCampaign::orderByDesc('start_date')->get();

        $data = compact('tab', 'totalContacts', 'converted', 'fromAds', 'conversionRate',
                        'campaignCount', 'services', 'users', 'campaigns', 'allCampaigns');

        if ($tab === 'campaigns') {
            $data['campaignRows'] = WhatsAppAdCampaign::withCount([
                'contacts',
                'contacts as converted_count' => fn($q) => $q->whereNotNull('client_id'),
            ])->orderByDesc('start_date')->get();
        } elseif ($tab === 'reports') {
            $this->authorize('View WhatsApp Reports');
            $data += $this->reportsData();
        } else {
            $data += $this->contactsData($request);
        }

        return view('pages.whatsapp_marketing.index', $data);
    }

    private function contactsData(Request $request): array
    {
        $query = WhatsAppContact::with(['campaign', 'client', 'services', 'assignedTo']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $contacts = $query->orderByDesc('is_important')->orderByDesc('id')->get();

        // Batch-load labels for all contacts (avoids N+1)
        $contactIds = $contacts->pluck('id')->all();
        $labelsMap = \Illuminate\Support\Facades\DB::table('whatsapp_contact_labels')
            ->whereIn('contact_id', $contactIds)
            ->get()->groupBy('contact_id')
            ->map(fn($rows) => $rows->pluck('label')->all());
        $contacts->each(fn($c) => $c->setRelation('_labels', $labelsMap->get($c->id, [])));

        // Stage counts for filter pills
        $stageCounts = WhatsAppContact::selectRaw('stage, count(*) as cnt')
            ->groupBy('stage')->pluck('cnt', 'stage');

        return compact('contacts', 'stageCounts');
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    private function reportsData(): array
    {
        $totalContacts  = WhatsAppContact::count();
        $converted      = WhatsAppContact::whereNotNull('client_id')->count();
        $totalRevenue   = WhatsAppContact::sum('deal_value') ?? 0;
        $totalAdSpend   = WhatsAppAdCampaign::sum('budget') ?? 0;
        $netProfit      = $totalRevenue - $totalAdSpend;
        $roi            = $totalAdSpend > 0 ? round(($netProfit / $totalAdSpend) * 100, 1) : null;
        $costPerLead    = $totalContacts > 0 && $totalAdSpend > 0 ? round($totalAdSpend / $totalContacts, 2) : null;
        $costPerConv    = $converted > 0 && $totalAdSpend > 0 ? round($totalAdSpend / $converted, 2) : null;
        $convRate       = $totalContacts > 0 ? round(($converted / $totalContacts) * 100, 1) : 0;

        // Per-campaign breakdown
        $reportCampaigns = WhatsAppAdCampaign::withCount([
            'contacts',
            'contacts as converted_count' => fn($q) => $q->whereNotNull('client_id'),
        ])->withSum('contacts as revenue', 'deal_value')
          ->orderByDesc('start_date')->get();

        // Stage funnel
        $stageFunnel = WhatsAppContact::selectRaw('stage, count(*) as cnt')
            ->groupBy('stage')->pluck('cnt', 'stage');

        // Source breakdown
        $sourceBreakdown = WhatsAppContact::selectRaw(
            'source, count(*) as total, sum(client_id is not null) as converted_count, sum(deal_value) as revenue'
        )->groupBy('source')->get();

        // Monthly trend — last 6 months
        $monthlyTrend = WhatsAppContact::selectRaw(
            "DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total, sum(client_id is not null) as converted_count"
        )->where('created_at', '>=', now()->subMonths(6))
         ->groupBy('month')->orderBy('month')->get();

        // Top services by demand
        $servicesDemand = \Illuminate\Support\Facades\DB::table('whatsapp_contact_services')
            ->join('field_marketing_services', 'field_marketing_services.id', '=', 'whatsapp_contact_services.field_marketing_service_id')
            ->selectRaw('field_marketing_services.name, count(*) as cnt')
            ->groupBy('field_marketing_services.name')
            ->orderByDesc('cnt')->limit(8)->get();

        // Call outcomes summary
        $callOutcomes = \Illuminate\Support\Facades\DB::table('whatsapp_contact_calls')
            ->selectRaw('outcome, count(*) as cnt')
            ->groupBy('outcome')->pluck('cnt', 'outcome');
        $totalCalls = \Illuminate\Support\Facades\DB::table('whatsapp_contact_calls')->count();

        return compact(
            'totalContacts', 'converted', 'totalRevenue', 'totalAdSpend',
            'netProfit', 'roi', 'costPerLead', 'costPerConv', 'convRate',
            'reportCampaigns', 'stageFunnel', 'sourceBreakdown',
            'monthlyTrend', 'servicesDemand', 'callOutcomes', 'totalCalls'
        );
    }

    // ── Contacts ──────────────────────────────────────────────────────────────

    public function storeContact(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'phone'              => 'required|string|max:30',
            'stage'              => 'required|in:' . implode(',', array_keys(WhatsAppContact::STAGES)),
            'source'             => 'required|in:' . implode(',', array_keys(WhatsAppContact::SOURCES)),
            'campaign_id'        => 'nullable|exists:whatsapp_ad_campaigns,id',
            'client_id'          => 'nullable|exists:project_clients,id',
            'next_followup_date' => 'nullable|date',
            'assigned_to'        => 'nullable|exists:users,id',
            'notes'              => 'nullable|string',
            'service_ids'        => 'nullable|array',
            'service_ids.*'      => 'exists:field_marketing_services,id',
            'is_important'       => 'nullable|boolean',
            'label_ids'          => 'nullable|array',
        ]);

        $contact = WhatsAppContact::create([
            'name'               => $request->name,
            'phone'              => $request->phone,
            'stage'              => $request->stage,
            'source'             => $request->source,
            'campaign_id'        => $request->campaign_id,
            'client_id'          => $request->client_id,
            'next_followup_date' => $request->next_followup_date,
            'assigned_to'        => $request->assigned_to,
            'notes'              => $request->notes,
            'deal_value'         => $request->deal_value,
            'is_important'       => $request->boolean('is_important'),
            'created_by'         => Auth::id(),
        ]);

        $contact->services()->sync($request->service_ids ?? []);
        $contact->syncLabels($request->label_ids ?? []);

        return back()->with('success', 'Contact added.');
    }

    public function updateContact(Request $request, $id)
    {
        $contact = WhatsAppContact::findOrFail($id);

        $request->validate([
            'name'               => 'required|string|max:255',
            'phone'              => 'required|string|max:30',
            'stage'              => 'required|in:' . implode(',', array_keys(WhatsAppContact::STAGES)),
            'source'             => 'required|in:' . implode(',', array_keys(WhatsAppContact::SOURCES)),
            'campaign_id'        => 'nullable|exists:whatsapp_ad_campaigns,id',
            'client_id'          => 'nullable|exists:project_clients,id',
            'next_followup_date' => 'nullable|date',
            'assigned_to'        => 'nullable|exists:users,id',
            'notes'              => 'nullable|string',
            'service_ids'        => 'nullable|array',
            'service_ids.*'      => 'exists:field_marketing_services,id',
            'is_important'       => 'nullable|boolean',
        ]);

        $contact->update([
            'name'               => $request->name,
            'phone'              => $request->phone,
            'stage'              => $request->stage,
            'source'             => $request->source,
            'campaign_id'        => $request->campaign_id,
            'client_id'          => $request->client_id,
            'next_followup_date' => $request->next_followup_date,
            'assigned_to'        => $request->assigned_to,
            'notes'              => $request->notes,
            'deal_value'         => $request->deal_value,
            'is_important'       => $request->boolean('is_important'),
        ]);

        $contact->services()->sync($request->service_ids ?? []);
        $contact->syncLabels($request->label_ids ?? []);

        return back()->with('success', 'Contact updated.');
    }

    public function destroyContact($id)
    {
        WhatsAppContact::findOrFail($id)->delete();
        return back()->with('success', 'Contact deleted.');
    }

    // ── Campaigns ─────────────────────────────────────────────────────────────

    public function storeCampaign(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        WhatsAppAdCampaign::create([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'budget'     => $request->budget,
            'notes'      => $request->notes,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('whatsapp_marketing.index', ['tab' => 'campaigns'])
            ->with('success', 'Campaign created.');
    }

    public function updateCampaign(Request $request, $id)
    {
        $campaign = WhatsAppAdCampaign::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        $campaign->update($request->only(['name', 'start_date', 'end_date', 'budget', 'notes']));

        return back()->with('success', 'Campaign updated.');
    }

    public function destroyCampaign($id)
    {
        WhatsAppAdCampaign::findOrFail($id)->delete();
        return back()->with('success', 'Campaign deleted.');
    }

    public function closeCampaign($id)
    {
        WhatsAppAdCampaign::findOrFail($id)->update(['status' => 'closed']);
        return back()->with('success', 'Campaign closed.');
    }

    public function updateContactLabels(Request $request, $id)
    {
        $contact = WhatsAppContact::findOrFail($id);
        $request->validate(['label_ids' => 'nullable|array']);

        $labels = $request->label_ids ?? [];
        $contact->syncLabels($labels);

        // Stage labels in priority order (highest wins when multiple selected)
        $stagePriority = ['order_complete', 'paid', 'pending_payment', 'new_order', 'new_customer', 'follow_up', 'lead'];
        foreach ($stagePriority as $stage) {
            if (in_array($stage, $labels)) {
                $contact->update(['stage' => $stage]);
                break;
            }
        }

        $contact->refresh();

        $stageCounts = WhatsAppContact::selectRaw('stage, count(*) as cnt')
            ->groupBy('stage')->pluck('cnt', 'stage');

        return response()->json([
            'success'      => true,
            'stage'        => $contact->stage,
            'stage_label'  => $contact->stage_label,
            'stage_badge'  => $contact->stage_badge_class,
            'stage_counts' => $stageCounts,
            'total_all'    => WhatsAppContact::count(),
        ]);
    }

    // ── Call / Follow-up Log ──────────────────────────────────────────────────

    public function getContactCalls($id)
    {
        $contact = WhatsAppContact::findOrFail($id);

        $calls = WhatsAppContactCall::where('contact_id', $id)
            ->with('createdBy')
            ->orderByDesc('call_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn($call) => [
                'id'                 => $call->id,
                'call_date'          => $call->call_date->format('d M Y'),
                'outcome'            => $call->outcome,
                'outcome_label'      => WhatsAppContactCall::OUTCOMES[$call->outcome]['label'] ?? ucfirst($call->outcome),
                'outcome_color'      => WhatsAppContactCall::OUTCOMES[$call->outcome]['color'] ?? 'secondary',
                'next_followup_date' => $call->next_followup_date?->format('d M Y'),
                'notes'              => $call->notes,
                'logged_by'          => $call->createdBy?->name,
            ]);

        return response()->json([
            'contact' => [
                'id'          => $contact->id,
                'name'        => $contact->name,
                'phone'       => $contact->phone,
                'stage_label' => $contact->stage_label,
                'stage_badge' => $contact->stage_badge_class,
            ],
            'calls' => $calls,
        ]);
    }

    public function storeContactCall(Request $request, $id)
    {
        $this->authorize('Log WhatsApp Call');
        $contact = WhatsAppContact::findOrFail($id);

        $request->validate([
            'call_date'          => 'required|date',
            'outcome'            => 'required|in:' . implode(',', array_keys(WhatsAppContactCall::OUTCOMES)),
            'next_followup_date' => 'nullable|date',
            'notes'              => 'nullable|string',
        ]);

        $call = WhatsAppContactCall::create([
            'contact_id'         => $id,
            'call_date'          => $request->call_date,
            'outcome'            => $request->outcome,
            'next_followup_date' => $request->next_followup_date,
            'notes'              => $request->notes,
            'created_by'         => Auth::id(),
        ]);

        if ($request->filled('next_followup_date')) {
            $contact->update(['next_followup_date' => $request->next_followup_date]);
        }

        return response()->json([
            'success'     => true,
            'id'          => $call->id,
            'call_date'   => $call->call_date->format('d M Y'),
            'outcome'     => $call->outcome,
            'outcome_label' => WhatsAppContactCall::OUTCOMES[$call->outcome]['label'] ?? ucfirst($call->outcome),
            'outcome_color' => WhatsAppContactCall::OUTCOMES[$call->outcome]['color'] ?? 'secondary',
            'next_followup_date' => $call->next_followup_date?->format('d M Y'),
            'notes'       => $call->notes,
            'logged_by'   => Auth::user()?->name,
        ]);
    }
}
