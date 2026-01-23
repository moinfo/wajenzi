<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEmail;
use App\Models\BillingClient;
use App\Models\ProjectClient;
use App\Models\BillingProduct;
use App\Models\BillingTaxRate;
use App\Models\BillingDocumentSetting;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceEmail;
use PDF;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = BillingDocument::with(['client', 'creator'])
            ->where('document_type', 'quote')
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->client_id, function ($query, $clientId) {
                return $query->where('client_id', $clientId);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                return $query->where('issue_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                return $query->where('issue_date', '<=', $toDate);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $clients = ProjectClient::orderBy('first_name')->orderBy('last_name')->get();
        
        return view('billing.quotations.index', compact('quotations', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = ProjectClient::orderBy('first_name')->orderBy('last_name')->get();
        $products = BillingProduct::with('taxRate')->where('is_active', true)->orderBy('name')->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');

        // If creating from a lead
        $lead = null;
        if ($request->lead_id) {
            $lead = Lead::with('client')->find($request->lead_id);
        }

        return view('billing.quotations.create', compact('clients', 'products', 'taxRates', 'settings', 'lead'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:project_clients,id',
            'issue_date' => 'required|date',
            'valid_until_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            $quotation = new BillingDocument();
            $quotation->document_type = 'quote';
            $quotation->document_number = $quotation->generateDocumentNumber('quote');
            $quotation->client_id = $request->client_id;
            $quotation->project_id = $request->project_id;
            $quotation->lead_id = $request->lead_id;
            $quotation->status = $request->save_as_draft ? 'draft' : 'pending';
            $quotation->issue_date = $request->issue_date;
            $quotation->valid_until_date = $request->valid_until_date;
            $quotation->payment_terms = $request->payment_terms;
            $quotation->currency_code = $request->currency_code ?? 'TZS';
            $quotation->exchange_rate = $request->exchange_rate ?? 1;
            $quotation->discount_type = $request->discount_type;
            $quotation->discount_value = $request->discount_value;
            $quotation->shipping_amount = $request->shipping_amount ?? 0;
            $quotation->notes = $request->notes;
            $quotation->terms_conditions = $request->terms_conditions;
            $quotation->footer_text = $request->footer_text;
            $quotation->po_number = $request->po_number;
            $quotation->sales_person = $request->sales_person;
            $quotation->created_by = auth()->id();
            $quotation->save();
            
            foreach ($request->items as $index => $item) {
                $quotation->items()->create([
                    'item_type' => $item['item_type'] ?? 'custom',
                    'product_service_id' => $item['product_service_id'] ?? null,
                    'item_code' => $item['item_code'] ?? null,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_of_measure' => $item['unit_of_measure'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'sort_order' => $index + 1
                ]);
            }
            
            $quotation->calculateTotals();
            
            DB::commit();
            
            return redirect()
                ->route('billing.quotations.show', $quotation)
                ->with('success', 'Quotation created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating quotation: ' . $e->getMessage());
        }
    }

    public function show(BillingDocument $quotation)
    {
        $quotation->load(['client', 'items', 'creator', 'lead']);

        if ($quotation->status === 'sent' && !$quotation->viewed_at) {
            $quotation->update(['viewed_at' => now(), 'status' => 'viewed']);
        }

        return view('billing.quotations.show', compact('quotation'));
    }

    public function edit(BillingDocument $quotation)
    {
        if (!$quotation->is_editable) {
            return redirect()
                ->route('billing.quotations.show', $quotation)
                ->with('error', 'This quotation cannot be edited.');
        }
        
        $quotation->load('items');
        $clients = ProjectClient::orderBy('first_name')->orderBy('last_name')->get();
        $products = BillingProduct::with('taxRate')->where('is_active', true)->orderBy('name')->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        return view('billing.quotations.edit', compact('quotation', 'clients', 'products', 'taxRates', 'settings'));
    }

    public function update(Request $request, BillingDocument $quotation)
    {
        if (!$quotation->is_editable) {
            return redirect()
                ->route('billing.quotations.show', $quotation)
                ->with('error', 'This quotation cannot be edited.');
        }
        
        $request->validate([
            'client_id' => 'required|exists:project_clients,id',
            'issue_date' => 'required|date',
            'valid_until_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
        ]);
        
        DB::beginTransaction();
        
        try {
            $quotation->update($request->only([
                'client_id', 'project_id', 'issue_date', 'valid_until_date',
                'payment_terms', 'currency_code', 'exchange_rate', 
                'discount_type', 'discount_value', 'shipping_amount',
                'notes', 'terms_conditions', 'footer_text', 'po_number', 'sales_person'
            ]));
            
            $quotation->items()->delete();
            
            foreach ($request->items as $index => $item) {
                $quotation->items()->create([
                    'item_type' => $item['item_type'] ?? 'custom',
                    'product_service_id' => $item['product_service_id'] ?? null,
                    'item_code' => $item['item_code'] ?? null,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_of_measure' => $item['unit_of_measure'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'tax_rate_id' => $item['tax_rate_id'] ?? null,
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'sort_order' => $index + 1
                ]);
            }
            
            $quotation->calculateTotals();
            
            DB::commit();
            
            return redirect()
                ->route('billing.quotations.show', $quotation)
                ->with('success', 'Quotation updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating quotation: ' . $e->getMessage());
        }
    }

    public function destroy(BillingDocument $quotation)
    {
        $quotation->update(['status' => 'cancelled']);
        
        return redirect()
            ->route('billing.quotations.index')
            ->with('success', 'Quotation cancelled successfully.');
    }

    public function generatePDF(BillingDocument $quotation)
    {
        $quotation->load(['client', 'items']);
        
        $pdf = PDF::loadView('billing.quotations.pdf', compact('quotation'));
        
        return $pdf->download('quotation-' . $quotation->document_number . '.pdf');
    }

    public function sendEmail(Request $request, BillingDocument $quotation)
    {
        $request->validate([
            'email' => 'required|email',
            'cc' => 'nullable|string',
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            $mail = Mail::to($request->email);
            
            // Add CC emails if provided
            if ($request->cc) {
                $ccEmails = array_map('trim', explode(',', $request->cc));
                $mail->cc($ccEmails);
            }
            
            // Send the email
            $mail->send(new InvoiceEmail($quotation, $request->subject, $request->message));
            
            // Update document status
            $quotation->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);
            
            // Track the email
            $quotation->emails()->create([
                'document_type' => $quotation->document_type,
                'recipient_email' => $request->email,
                'cc_emails' => $request->cc,
                'subject' => $request->subject,
                'message' => $request->message,
                'has_attachment' => true,
                'attachment_filename' => $quotation->document_type . '-' . $quotation->document_number . '.pdf',
                'status' => 'sent',
                'sent_by' => auth()->id(),
                'sent_at' => now()
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Quotation sent successfully to ' . $request->email);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Track the failed email attempt
            try {
                $quotation->emails()->create([
                    'document_type' => $quotation->document_type,
                    'recipient_email' => $request->email,
                    'cc_emails' => $request->cc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'has_attachment' => true,
                    'attachment_filename' => $quotation->document_type . '-' . $quotation->document_number . '.pdf',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_by' => auth()->id(),
                    'sent_at' => now()
                ]);
            } catch (\Exception $trackingException) {
                // Log but don't fail on tracking error
            }
            
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function duplicate(BillingDocument $quotation)
    {
        $newQuotation = $quotation->duplicate();
        $newQuotation->document_type = 'quote';
        $newQuotation->document_number = $newQuotation->generateDocumentNumber('quote');
        $newQuotation->save();
        
        return redirect()
            ->route('billing.quotations.edit', $newQuotation)
            ->with('success', 'Quotation duplicated successfully.');
    }

    public function convertToProforma(BillingDocument $quotation)
    {
        try {
            $proforma = $quotation->convertToProforma();
            
            return redirect()
                ->route('billing.proformas.show', $proforma)
                ->with('success', 'Quotation converted to proforma successfully.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error converting quotation: ' . $e->getMessage());
        }
    }

    public function convertToInvoice(BillingDocument $quotation)
    {
        try {
            $invoice = $quotation->convertToInvoice();
            
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('success', 'Quotation converted to invoice successfully.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error converting quotation: ' . $e->getMessage());
        }
    }
}