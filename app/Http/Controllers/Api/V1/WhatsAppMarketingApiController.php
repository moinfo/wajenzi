<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldMarketingService;
use App\Models\ProjectClient;
use App\Models\User;
use App\Models\WhatsAppAdCampaign;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppContactCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Marketing API (mobile).
 *
 * Mirrors {@see \App\Http\Controllers\WhatsAppMarketingController} (portal).
 *
 * Resources:
 *   - contacts   (people contacted via WhatsApp; tracks pipeline stage)
 *   - campaigns  (WhatsApp Ad campaigns)
 *   - calls      (per-contact call log with outcomes)
 *
 * The "stage" pipeline is the most important field; clients change it to
 * progress contacts from lead → paid → order_complete.
 */
class WhatsAppMarketingApiController extends Controller
{
    // ────────────────────────── Index / stats ──────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $contacts = $this->buildContacts($request);
            $stats    = $this->buildStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'contacts'    => $contacts,
                    'stats'       => $stats,
                    'stage_counts' => $this->stageCounts(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('WhatsAppMarketingApi index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch WhatsApp marketing data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        $users = User::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'stages' => collect(WhatsAppContact::STAGES)->map(fn ($v, $k) => [
                    'value' => $k,
                    'label' => $v['label'],
                ])->values(),
                'labels' => collect(WhatsAppContact::LABELS)->map(fn ($v, $k) => [
                    'value' => $k,
                    'label' => $v['label'],
                    'color' => $v['hex'],
                ])->values(),
                'sources' => collect(WhatsAppContact::SOURCES)->map(fn ($v, $k) => [
                    'value' => $k,
                    'label' => $v,
                ])->values(),
                'campaigns' => WhatsAppAdCampaign::orderByDesc('start_date')
                    ->get(['id', 'name', 'start_date', 'end_date', 'status']),
                'services' => FieldMarketingService::active()
                    ->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
                'users'   => $users,
                'clients' => ProjectClient::orderBy('first_name')
                    ->limit(500)->get(['id', 'first_name', 'last_name']),
                'call_outcomes' => collect(WhatsAppContactCall::OUTCOMES)->map(fn ($v, $k) => [
                    'value' => $k,
                    'label' => $v['label'] ?? ucfirst($k),
                    'color' => $v['color'] ?? 'secondary',
                ])->values(),
            ],
        ]);
    }

    // ────────────────────────── Contacts CRUD ──────────────────────────

    public function showContact(int $id): JsonResponse
    {
        $contact = WhatsAppContact::with([
            'campaign:id,name',
            'client:id,first_name,last_name',
            'assignedTo:id,name',
            'services:id,name',
        ])->find($id);

        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformContact($contact),
        ]);
    }

    public function storeContact(Request $request): JsonResponse
    {
        $validated = $request->validate($this->contactRules());

        $contact = WhatsAppContact::create([
            'name'               => $validated['name'],
            'phone'              => $validated['phone'],
            'stage'              => $validated['stage'],
            'source'             => $validated['source'],
            'campaign_id'        => $validated['campaign_id'] ?? null,
            'client_id'          => $validated['client_id'] ?? null,
            'next_followup_date' => $validated['next_followup_date'] ?? null,
            'assigned_to'        => $validated['assigned_to'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'deal_value'         => $request->input('deal_value'),
            'is_important'       => (bool) ($validated['is_important'] ?? false),
            'created_by'         => Auth::id(),
        ]);

        if (!empty($validated['service_ids'])) {
            $contact->services()->sync($validated['service_ids']);
        }
        if (!empty($validated['label_ids'])) {
            $contact->syncLabels($validated['label_ids']);
        }

        $contact->load(['campaign:id,name', 'client:id,first_name,last_name', 'assignedTo:id,name', 'services:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Contact created',
            'data' => $this->transformContact($contact),
        ], 201);
    }

    public function updateContact(Request $request, int $id): JsonResponse
    {
        $contact = WhatsAppContact::find($id);
        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }

        $rules = collect($this->contactRules())->map(function ($rule) {
            // make all rules "sometimes"
            return is_string($rule) ? 'sometimes|'.$rule : $rule;
        })->all();
        $validated = $request->validate($rules);

        $contact->update(collect($validated)->except(['service_ids', 'label_ids'])->toArray());

        if ($request->has('service_ids')) {
            $contact->services()->sync($validated['service_ids'] ?? []);
        }
        if ($request->has('label_ids')) {
            $contact->syncLabels($validated['label_ids'] ?? []);
        }

        $contact->load(['campaign:id,name', 'client:id,first_name,last_name', 'assignedTo:id,name', 'services:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Contact updated',
            'data' => $this->transformContact($contact),
        ]);
    }

    public function destroyContact(int $id): JsonResponse
    {
        $contact = WhatsAppContact::find($id);
        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }
        $contact->delete();
        return response()->json(['success' => true, 'message' => 'Contact deleted']);
    }

    public function updateContactStage(Request $request, int $id): JsonResponse
    {
        $contact = WhatsAppContact::find($id);
        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }

        $validated = $request->validate([
            'stage' => 'required|in:'.implode(',', array_keys(WhatsAppContact::STAGES)),
        ]);

        $contact->update(['stage' => $validated['stage']]);

        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $contact->id,
                'stage'        => $contact->stage,
                'stage_label'  => $contact->stage_label,
                'stage_counts' => $this->stageCounts(),
            ],
        ]);
    }

    // ────────────────────────── Campaigns CRUD ──────────────────────────

    public function indexCampaigns(): JsonResponse
    {
        $campaigns = WhatsAppAdCampaign::withCount([
            'contacts',
            'contacts as converted_count' => fn ($q) => $q->whereNotNull('client_id'),
        ])->orderByDesc('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => $campaigns->map(fn ($c) => $this->transformCampaign($c))->all(),
        ]);
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
        ]);

        $campaign = WhatsAppAdCampaign::create($validated + ['created_by' => Auth::id()]);

        return response()->json([
            'success' => true,
            'message' => 'Campaign created',
            'data' => $this->transformCampaign($campaign),
        ], 201);
    }

    public function updateCampaign(Request $request, int $id): JsonResponse
    {
        $campaign = WhatsAppAdCampaign::find($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $validated = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string',
            'status'     => 'sometimes|in:active,closed',
        ]);

        $campaign->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Campaign updated',
            'data' => $this->transformCampaign($campaign),
        ]);
    }

    public function destroyCampaign(int $id): JsonResponse
    {
        $campaign = WhatsAppAdCampaign::find($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }
        $campaign->delete();
        return response()->json(['success' => true, 'message' => 'Campaign deleted']);
    }

    public function closeCampaign(int $id): JsonResponse
    {
        $campaign = WhatsAppAdCampaign::find($id);
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }
        $campaign->update(['status' => 'closed']);
        return response()->json(['success' => true, 'message' => 'Campaign closed']);
    }

    // ────────────────────────── Calls ──────────────────────────

    public function indexCalls(int $contactId): JsonResponse
    {
        if (!WhatsAppContact::where('id', $contactId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }

        $calls = WhatsAppContactCall::where('contact_id', $contactId)
            ->with('createdBy:id,name')
            ->orderByDesc('call_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($call) => $this->transformCall($call))
            ->all();

        return response()->json(['success' => true, 'data' => $calls]);
    }

    public function storeCall(Request $request, int $contactId): JsonResponse
    {
        $contact = WhatsAppContact::find($contactId);
        if (!$contact) {
            return response()->json(['success' => false, 'message' => 'Contact not found'], 404);
        }

        $validated = $request->validate([
            'call_date'          => 'required|date',
            'outcome'            => 'required|in:'.implode(',', array_keys(WhatsAppContactCall::OUTCOMES)),
            'next_followup_date' => 'nullable|date',
            'notes'              => 'nullable|string',
        ]);

        $call = WhatsAppContactCall::create([
            'contact_id'         => $contactId,
            'call_date'          => $validated['call_date'],
            'outcome'            => $validated['outcome'],
            'next_followup_date' => $validated['next_followup_date'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        if (!empty($validated['next_followup_date'])) {
            $contact->update(['next_followup_date' => $validated['next_followup_date']]);
        }

        $call->load('createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Call logged',
            'data' => $this->transformCall($call),
        ], 201);
    }

    // ────────────────────────── Builders ──────────────────────────

    private function buildContacts(Request $request): array
    {
        $query = WhatsAppContact::with([
            'campaign:id,name',
            'client:id,first_name,last_name',
            'services:id,name',
            'assignedTo:id,name',
        ]);

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }
        if ($request->filled('stage')) {
            $query->where('stage', $request->input('stage'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', (int) $request->input('campaign_id'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', (int) $request->input('assigned_to'));
        }

        $contacts = $query->orderByDesc('is_important')->orderByDesc('id')->get();
        $contactIds = $contacts->pluck('id')->all();
        $labelsMap = DB::table('whatsapp_contact_labels')
            ->whereIn('contact_id', $contactIds)
            ->get()->groupBy('contact_id')
            ->map(fn ($rows) => $rows->pluck('label')->all());

        return $contacts->map(function ($c) use ($labelsMap) {
            $out = $this->transformContact($c);
            $out['labels'] = $labelsMap->get($c->id, []);
            return $out;
        })->all();
    }

    private function buildStats(): array
    {
        $total     = WhatsAppContact::count();
        $converted = WhatsAppContact::whereNotNull('client_id')->count();
        $fromAds   = WhatsAppContact::where('source', 'whatsapp_ad')->count();
        $rate      = $total > 0 ? round(($converted / $total) * 100) : 0;
        $active    = WhatsAppAdCampaign::where('status', '!=', 'closed')->count();
        $totalRevenue = (float) (WhatsAppContact::sum('deal_value') ?? 0);

        return [
            'total_contacts'   => $total,
            'converted'        => $converted,
            'from_ads'         => $fromAds,
            'conversion_rate'  => $rate,
            'active_campaigns' => $active,
            'total_revenue'    => $totalRevenue,
        ];
    }

    private function stageCounts(): array
    {
        $counts = WhatsAppContact::selectRaw('stage, count(*) as cnt')
            ->groupBy('stage')->pluck('cnt', 'stage')->toArray();

        $out = [];
        foreach (array_keys(WhatsAppContact::STAGES) as $stage) {
            $out[$stage] = (int) ($counts[$stage] ?? 0);
        }
        return $out;
    }

    // ────────────────────────── Transformers ──────────────────────────

    private function transformContact(WhatsAppContact $contact): array
    {
        $clientName = $contact->client
            ? trim(($contact->client->first_name ?? '').' '.($contact->client->last_name ?? ''))
            : null;

        return [
            'id'                 => $contact->id,
            'name'               => $contact->name,
            'phone'              => $contact->phone,
            'stage'              => $contact->stage,
            'stage_label'        => $contact->stage_label,
            'source'             => $contact->source,
            'source_label'       => $contact->source_label,
            'campaign_id'        => $contact->campaign_id,
            'campaign_name'      => $contact->campaign?->name,
            'client_id'          => $contact->client_id,
            'client_name'        => $clientName,
            'next_followup_date' => optional($contact->next_followup_date)->format('Y-m-d'),
            'assigned_to'        => $contact->assigned_to,
            'assigned_to_name'   => $contact->assignedTo?->name,
            'notes'              => $contact->notes,
            'deal_value'         => $contact->deal_value !== null ? (float) $contact->deal_value : null,
            'is_important'       => (bool) $contact->is_important,
            'services'           => $contact->relationLoaded('services')
                ? $contact->services->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->all()
                : [],
            'created_at'         => optional($contact->created_at)->toIso8601String(),
            'updated_at'         => optional($contact->updated_at)->toIso8601String(),
        ];
    }

    private function transformCampaign(WhatsAppAdCampaign $c): array
    {
        return [
            'id'              => $c->id,
            'name'            => $c->name,
            'start_date'      => optional($c->start_date)->format('Y-m-d'),
            'end_date'        => optional($c->end_date)->format('Y-m-d'),
            'budget'          => $c->budget !== null ? (float) $c->budget : null,
            'status'          => $c->status ?? 'active',
            'notes'           => $c->notes,
            'contacts_count'  => (int) ($c->contacts_count ?? 0),
            'converted_count' => (int) ($c->converted_count ?? 0),
            'created_at'      => optional($c->created_at)->toIso8601String(),
        ];
    }

    private function transformCall(WhatsAppContactCall $call): array
    {
        return [
            'id'                 => $call->id,
            'contact_id'         => $call->contact_id,
            'call_date'          => optional($call->call_date)->format('Y-m-d'),
            'outcome'            => $call->outcome,
            'outcome_label'      => WhatsAppContactCall::OUTCOMES[$call->outcome]['label'] ?? ucfirst($call->outcome),
            'outcome_color'      => WhatsAppContactCall::OUTCOMES[$call->outcome]['color'] ?? 'secondary',
            'next_followup_date' => optional($call->next_followup_date)->format('Y-m-d'),
            'notes'              => $call->notes,
            'logged_by'          => $call->createdBy?->name,
            'created_at'         => optional($call->created_at)->toIso8601String(),
        ];
    }

    // ────────────────────────── Helpers ──────────────────────────

    private function contactRules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'phone'              => 'required|string|max:30',
            'stage'              => 'required|in:'.implode(',', array_keys(WhatsAppContact::STAGES)),
            'source'             => 'required|in:'.implode(',', array_keys(WhatsAppContact::SOURCES)),
            'campaign_id'        => 'nullable|exists:whatsapp_ad_campaigns,id',
            'client_id'          => 'nullable|exists:project_clients,id',
            'next_followup_date' => 'nullable|date',
            'assigned_to'        => 'nullable|exists:users,id',
            'notes'              => 'nullable|string',
            'deal_value'         => 'nullable|numeric|min:0',
            'service_ids'        => 'nullable|array',
            'service_ids.*'      => 'integer|exists:field_marketing_services,id',
            'is_important'       => 'nullable|boolean',
            'label_ids'          => 'nullable|array',
        ];
    }
}
