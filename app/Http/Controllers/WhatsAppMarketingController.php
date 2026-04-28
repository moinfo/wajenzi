<?php

namespace App\Http\Controllers;

use App\Models\FieldMarketingService;
use App\Models\ProjectClient;
use App\Models\User;
use App\Models\WhatsAppAdCampaign;
use App\Models\WhatsAppContact;
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

        $services  = FieldMarketingService::active()->orderBy('sort_order')->get();
        $users     = User::where('status', 'ACTIVE')->orderBy('name')->get();
        // Only active campaigns appear in the contact form dropdown
        $campaigns = WhatsAppAdCampaign::where('status', 'active')->orderByDesc('start_date')->get();

        $data = compact('tab', 'totalContacts', 'converted', 'fromAds', 'conversionRate',
                        'campaignCount', 'services', 'users', 'campaigns');

        if ($tab === 'campaigns') {
            $data['campaignRows'] = WhatsAppAdCampaign::withCount([
                'contacts',
                'contacts as converted_count' => fn($q) => $q->whereNotNull('client_id'),
            ])->orderByDesc('start_date')->get();
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

        // Stage counts for filter pills
        $stageCounts = WhatsAppContact::selectRaw('stage, count(*) as cnt')
            ->groupBy('stage')->pluck('cnt', 'stage');

        return compact('contacts', 'stageCounts');
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
            'is_important'       => $request->boolean('is_important'),
            'created_by'         => Auth::id(),
        ]);

        $contact->services()->sync($request->service_ids ?? []);

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
            'is_important'       => $request->boolean('is_important'),
        ]);

        $contact->services()->sync($request->service_ids ?? []);

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
}
