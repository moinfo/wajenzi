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

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $invoices = BillingDocument::with(['client', 'creator'])
            ->where('document_type', 'invoice')
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
        
        return view('billing.invoices.index', compact('invoices', 'clients'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request)
    {
        $clients = BillingClient::active()->customers()->get();
        $products = BillingProduct::where('is_active', true)->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        // If converting from quote or proforma
        $parentDocument = null;
        if ($request->from_document) {
            $parentDocument = BillingDocument::with('items')->find($request->from_document);
        }
        
        return view('billing.invoices.create', compact('clients', 'products', 'taxRates', 'settings', 'parentDocument'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:billing_clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'payment_terms' => 'required|in:immediate,net_7,net_15,net_30,net_45,net_60,net_90,custom',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            // Create invoice
            $invoice = new BillingDocument();
            $invoice->document_type = 'invoice';
            $invoice->document_number = $invoice->generateDocumentNumber('invoice');
            $invoice->client_id = $request->client_id;
            $invoice->project_id = $request->project_id;
            $invoice->parent_document_id = $request->parent_document_id;
            $invoice->status = $request->save_as_draft ? 'draft' : 'pending';
            $invoice->issue_date = $request->issue_date;
            $invoice->due_date = $request->due_date;
            $invoice->payment_terms = $request->payment_terms;
            $invoice->custom_payment_days = $request->custom_payment_days;
            $invoice->currency_code = $request->currency_code ?? 'TZS';
            $invoice->exchange_rate = $request->exchange_rate ?? 1;
            $invoice->discount_type = $request->discount_type;
            $invoice->discount_value = $request->discount_value;
            $invoice->shipping_amount = $request->shipping_amount ?? 0;
            $invoice->notes = $request->notes;
            $invoice->terms_conditions = $request->terms_conditions;
            $invoice->footer_text = $request->footer_text;
            $invoice->po_number = $request->po_number;
            $invoice->sales_person = $request->sales_person;
            $invoice->created_by = auth()->id();
            $invoice->save();
            
            // Add items
            foreach ($request->items as $index => $item) {
                $invoice->items()->create([
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
            
            // Calculate totals
            $invoice->calculateTotals();
            
            // Update parent document status if converting
            if ($request->parent_document_id) {
                BillingDocument::where('id', $request->parent_document_id)
                    ->update(['status' => 'accepted']);
            }
            
            DB::commit();
            
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified invoice.
     */
    public function show(BillingDocument $invoice)
    {
        $invoice->load(['client', 'items', 'payments', 'creator', 'approver']);
        
        // Mark as viewed if sent
        if ($invoice->status === 'sent' && !$invoice->viewed_at) {
            $invoice->update(['viewed_at' => now(), 'status' => 'viewed']);
        }
        
        return view('billing.invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the invoice.
     */
    public function edit(BillingDocument $invoice)
    {
        if (!$invoice->is_editable) {
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('error', 'This invoice cannot be edited.');
        }
        
        $invoice->load('items');
        $clients = BillingClient::active()->customers()->get();
        $products = BillingProduct::where('is_active', true)->get();
        $taxRates = BillingTaxRate::where('is_active', true)->get();
        $settings = BillingDocumentSetting::pluck('setting_value', 'setting_key');
        
        return view('billing.invoices.edit', compact('invoice', 'clients', 'products', 'taxRates', 'settings'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, BillingDocument $invoice)
    {
        if (!$invoice->is_editable) {
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('error', 'This invoice cannot be edited.');
        }
        
        $request->validate([
            'client_id' => 'required|exists:billing_clients,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update invoice
            $invoice->update($request->only([
                'client_id', 'project_id', 'issue_date', 'due_date',
                'payment_terms', 'custom_payment_days', 'currency_code',
                'exchange_rate', 'discount_type', 'discount_value',
                'shipping_amount', 'notes', 'terms_conditions',
                'footer_text', 'po_number', 'sales_person'
            ]));
            
            // Delete existing items
            $invoice->items()->delete();
            
            // Add new items
            foreach ($request->items as $index => $item) {
                $invoice->items()->create([
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
            
            // Recalculate totals
            $invoice->calculateTotals();
            
            DB::commit();
            
            return redirect()
                ->route('billing.invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(BillingDocument $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Cannot delete invoice with payments.');
        }
        
        $invoice->update(['status' => 'cancelled']);
        
        return redirect()
            ->route('billing.invoices.index')
            ->with('success', 'Invoice cancelled successfully.');
    }

    /**
     * Generate PDF for the invoice.
     */
    public function generatePDF(BillingDocument $invoice)
    {
        $invoice->load(['client', 'items', 'payments']);
        
        $pdf = PDF::loadView('billing.invoices.pdf', compact('invoice'));
        
        return $pdf->download('invoice-' . $invoice->document_number . '.pdf');
    }

    /**
     * Send invoice via email.
     */
    public function sendEmail(Request $request, BillingDocument $invoice)
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
            $mail->send(new InvoiceEmail($invoice, $request->subject, $request->message));
            
            // Update document status
            $invoice->update([
                'status' => 'sent',
                'sent_at' => now()
            ]);
            
            // Track the email
            $invoice->emails()->create([
                'document_type' => $invoice->document_type,
                'recipient_email' => $request->email,
                'cc_emails' => $request->cc,
                'subject' => $request->subject,
                'message' => $request->message,
                'has_attachment' => true,
                'attachment_filename' => $invoice->document_type . '-' . $invoice->document_number . '.pdf',
                'status' => 'sent',
                'sent_by' => auth()->id(),
                'sent_at' => now()
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Invoice sent successfully to ' . $request->email);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Track the failed email attempt
            try {
                $invoice->emails()->create([
                    'document_type' => $invoice->document_type,
                    'recipient_email' => $request->email,
                    'cc_emails' => $request->cc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'has_attachment' => true,
                    'attachment_filename' => $invoice->document_type . '-' . $invoice->document_number . '.pdf',
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

    /**
     * Record payment for the invoice.
     */
    public function recordPayment(Request $request, BillingDocument $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,credit_card,mobile_money,online,other',
            'reference_number' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            $payment = $invoice->payments()->create([
                'client_id' => $invoice->client_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'bank_name' => $request->bank_name,
                'cheque_number' => $request->cheque_number,
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
                'status' => 'completed',
                'received_by' => auth()->id()
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Payment recorded successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate an invoice.
     */
    public function duplicate(BillingDocument $invoice)
    {
        $newInvoice = $invoice->duplicate();
        
        return redirect()
            ->route('billing.invoices.edit', $newInvoice)
            ->with('success', 'Invoice duplicated successfully.');
    }

    /**
     * Void an invoice.
     */
    public function void(BillingDocument $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot void a paid invoice.');
        }
        
        $invoice->update(['status' => 'void']);
        
        return back()->with('success', 'Invoice voided successfully.');
    }
}