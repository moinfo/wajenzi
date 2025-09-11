<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEmail;
use App\Models\BillingClient;
use App\Models\BillingProduct;
use App\Models\BillingTaxRate;
use App\Models\BillingDocumentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceEmail;
use PDF;

class ProformaController extends Controller
{
    public function index(Request $request)
    {
        $proformas = BillingDocument::with(['client', 'creator'])
            ->where('document_type', 'proforma')
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

        $clients = BillingClient::active()->customers()->get();
        
        return view('billing.proformas.index', compact('proformas', 'clients'));
    }

    public function create(Request $request)
    {
        $clients = BillingClient::active()->customers()->get();
        $products = BillingProduct::with('taxRate')->where('is_active', true)->orderBy('name')->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        return view('billing.proformas.create', compact('clients', 'products', 'taxRates', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:billing_clients,id',
            'issue_date' => 'required|date',
            'valid_until_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            $proforma = new BillingDocument();
            $proforma->document_type = 'proforma';
            $proforma->document_number = $proforma->generateDocumentNumber('proforma');
            $proforma->client_id = $request->client_id;
            $proforma->project_id = $request->project_id;
            $proforma->status = $request->save_as_draft ? 'draft' : 'pending';
            $proforma->issue_date = $request->issue_date;
            $proforma->valid_until_date = $request->valid_until_date;
            $proforma->payment_terms = $request->payment_terms;
            $proforma->currency_code = $request->currency_code ?? 'TZS';
            $proforma->exchange_rate = $request->exchange_rate ?? 1;
            $proforma->discount_type = $request->discount_type;
            $proforma->discount_value = $request->discount_value;
            $proforma->shipping_amount = $request->shipping_amount ?? 0;
            $proforma->notes = $request->notes;
            $proforma->terms_conditions = $request->terms_conditions;
            $proforma->footer_text = $request->footer_text;
            $proforma->po_number = $request->po_number;
            $proforma->sales_person = $request->sales_person;
            $proforma->created_by = auth()->id();
            $proforma->save();
            
            foreach ($request->items as $index => $item) {
                $proforma->items()->create([
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
            
            $proforma->calculateTotals();
            
            DB::commit();
            
            return redirect()
                ->route('billing.proformas.show', $proforma)
                ->with('success', 'Proforma invoice created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating proforma invoice: ' . $e->getMessage());
        }
    }

    public function show(BillingDocument $proforma)
    {
        $proforma->load(['client', 'items', 'creator', 'emails.sender']);
        
        if ($proforma->status === 'sent' && !$proforma->viewed_at) {
            $proforma->update(['viewed_at' => now(), 'status' => 'viewed']);
        }
        
        return view('billing.proformas.show', compact('proforma'));
    }

    public function edit(BillingDocument $proforma)
    {
        if (!$proforma->is_editable) {
            return redirect()
                ->route('billing.proformas.show', $proforma)
                ->with('error', 'This proforma invoice cannot be edited.');
        }
        
        $proforma->load('items');
        $clients = BillingClient::active()->customers()->get();
        $products = BillingProduct::with('taxRate')->where('is_active', true)->orderBy('name')->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        return view('billing.proformas.edit', compact('proforma', 'clients', 'products', 'taxRates', 'settings'));
    }

    public function update(Request $request, BillingDocument $proforma)
    {
        if (!$proforma->is_editable) {
            return redirect()
                ->route('billing.proformas.show', $proforma)
                ->with('error', 'This proforma invoice cannot be edited.');
        }
        
        $request->validate([
            'client_id' => 'required|exists:billing_clients,id',
            'issue_date' => 'required|date',
            'valid_until_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
        ]);
        
        DB::beginTransaction();
        
        try {
            $proforma->update($request->only([
                'client_id', 'project_id', 'issue_date', 'valid_until_date',
                'payment_terms', 'currency_code', 'exchange_rate', 
                'discount_type', 'discount_value', 'shipping_amount',
                'notes', 'terms_conditions', 'footer_text', 'po_number', 'sales_person'
            ]));
            
            $proforma->items()->delete();
            
            foreach ($request->items as $index => $item) {
                $proforma->items()->create([
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
            
            $proforma->calculateTotals();
            
            DB::commit();
            
            return redirect()
                ->route('billing.proformas.show', $proforma)
                ->with('success', 'Proforma invoice updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating proforma invoice: ' . $e->getMessage());
        }
    }

    public function destroy(BillingDocument $proforma)
    {
        $proforma->update(['status' => 'cancelled']);
        
        return redirect()
            ->route('billing.proformas.index')
            ->with('success', 'Proforma invoice cancelled successfully.');
    }

    public function generatePDF(BillingDocument $proforma)
    {
        $proforma->load(['client', 'items']);
        
        $pdf = PDF::loadView('billing.proformas.pdf', compact('proforma'));
        
        return $pdf->download('proforma-' . $proforma->document_number . '.pdf');
    }

    public function sendEmail(Request $request, BillingDocument $proforma)
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
            $mail->send(new InvoiceEmail($proforma, $request->subject, $request->message));
            
            // Update document status
            $proforma->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);
            
            // Track the email
            $proforma->emails()->create([
                'document_type' => $proforma->document_type,
                'recipient_email' => $request->email,
                'cc_emails' => $request->cc,
                'subject' => $request->subject,
                'message' => $request->message,
                'has_attachment' => true,
                'attachment_filename' => $proforma->document_type . '-' . $proforma->document_number . '.pdf',
                'status' => 'sent',
                'sent_by' => auth()->id(),
                'sent_at' => now()
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Proforma invoice sent successfully to ' . $request->email);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Track the failed email attempt
            try {
                $proforma->emails()->create([
                    'document_type' => $proforma->document_type,
                    'recipient_email' => $request->email,
                    'cc_emails' => $request->cc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'has_attachment' => true,
                    'attachment_filename' => $proforma->document_type . '-' . $proforma->document_number . '.pdf',
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

    public function duplicate(BillingDocument $proforma)
    {
        $newProforma = $proforma->duplicate();
        $newProforma->document_type = 'proforma';
        $newProforma->document_number = $newProforma->generateDocumentNumber('proforma');
        $newProforma->save();
        
        return redirect()
            ->route('billing.proformas.edit', $newProforma)
            ->with('success', 'Proforma invoice duplicated successfully.');
    }

    public function convertToInvoice(BillingDocument $proforma)
    {
        try {
            $invoice = $proforma->convertToInvoice();
            
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('success', 'Proforma invoice converted to invoice successfully.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error converting proforma invoice: ' . $e->getMessage());
        }
    }
}