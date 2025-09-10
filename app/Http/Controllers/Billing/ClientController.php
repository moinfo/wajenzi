<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingClient;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $clients = BillingClient::query()
            ->when($request->search, function ($query, $search) {
                return $query->where('company_name', 'like', "%{$search}%")
                           ->orWhere('contact_person', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->client_type, function ($query, $type) {
                return $query->where('client_type', $type);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    return $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    return $query->where('is_active', false);
                }
            })
            ->orderBy('company_name')
            ->paginate(20);

        return view('billing.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('billing.clients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_type' => 'required|in:customer,vendor',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_identification_number' => 'nullable|string|max:100',
            'billing_address_line1' => 'nullable|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'shipping_address_line1' => 'nullable|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:immediate,net_7,net_15,net_30,net_45,net_60,net_90,custom',
            'preferred_currency' => 'nullable|string|size:3',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);

        $client = BillingClient::create($request->all());

        return redirect()
            ->route('billing.clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    public function show(BillingClient $client)
    {
        $client->load(['documents' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }, 'payments' => function($query) {
            $query->orderBy('payment_date', 'desc')->limit(5);
        }]);
        
        $stats = [
            'total_documents' => $client->documents()->count(),
            'total_invoiced' => $client->documents()->where('document_type', 'invoice')->sum('total_amount'),
            'total_paid' => $client->payments()->where('status', 'completed')->sum('amount'),
            'outstanding_balance' => $client->documents()->where('document_type', 'invoice')->sum('balance_amount'),
        ];

        return view('billing.clients.show', compact('client', 'stats'));
    }

    public function edit(BillingClient $client)
    {
        return view('billing.clients.edit', compact('client'));
    }

    public function update(Request $request, BillingClient $client)
    {
        $request->validate([
            'client_type' => 'required|in:customer,vendor',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'tax_identification_number' => 'nullable|string|max:100',
            'billing_address_line1' => 'nullable|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'shipping_address_line1' => 'nullable|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|in:immediate,net_7,net_15,net_30,net_45,net_60,net_90,custom',
            'preferred_currency' => 'nullable|string|size:3',
            'website' => 'nullable|url|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($request->all());

        return redirect()
            ->route('billing.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(BillingClient $client)
    {
        if ($client->documents()->count() > 0) {
            return back()->with('error', 'Cannot delete client with existing documents. Deactivate instead.');
        }

        $client->delete();

        return redirect()
            ->route('billing.clients.index')
            ->with('success', 'Client deleted successfully.');
    }

    public function activate(BillingClient $client)
    {
        $client->update(['is_active' => true]);

        return back()->with('success', 'Client activated successfully.');
    }

    public function deactivate(BillingClient $client)
    {
        $client->update(['is_active' => false]);

        return back()->with('success', 'Client deactivated successfully.');
    }

    public function statement(Request $request, BillingClient $client)
    {
        $from_date = $request->from_date ?? now()->subMonths(3)->toDateString();
        $to_date = $request->to_date ?? now()->toDateString();

        $documents = $client->documents()
            ->whereBetween('issue_date', [$from_date, $to_date])
            ->orderBy('issue_date', 'desc')
            ->get();

        $payments = $client->payments()
            ->whereBetween('payment_date', [$from_date, $to_date])
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('billing.clients.statement', compact('client', 'documents', 'payments', 'from_date', 'to_date'));
    }
}