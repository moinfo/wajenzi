<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingPayment;
use App\Models\BillingDocument;
use App\Models\BillingClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = BillingPayment::with(['document', 'client', 'receiver'])
            ->when($request->client_id, function ($query, $clientId) {
                return $query->where('client_id', $clientId);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->payment_method, function ($query, $method) {
                return $query->where('payment_method', $method);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                return $query->where('payment_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                return $query->where('payment_date', '<=', $toDate);
            })
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $clients = BillingClient::active()->customers()->get();
        
        return view('billing.payments.index', compact('payments', 'clients'));
    }

    public function create(Request $request)
    {
        $document = null;
        if ($request->document_id) {
            $document = BillingDocument::with('client')->findOrFail($request->document_id);
        }
        
        $clients = BillingClient::active()->customers()->get();
        $outstandingDocuments = BillingDocument::where('document_type', 'invoice')
            ->where('balance_amount', '>', 0)
            ->with('client')
            ->orderBy('issue_date', 'desc')
            ->get();
        
        return view('billing.payments.create', compact('document', 'clients', 'outstandingDocuments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_id' => 'required|exists:billing_documents,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,credit_card,mobile_payment,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $document = BillingDocument::findOrFail($request->document_id);
        
        if ($request->amount > $document->balance_amount) {
            return back()
                ->withInput()
                ->with('error', 'Payment amount cannot exceed outstanding balance of ' . number_format($document->balance_amount, 2));
        }

        DB::beginTransaction();
        
        try {
            $payment = new BillingPayment();
            $payment->document_id = $document->id;
            $payment->client_id = $document->client_id;
            $payment->payment_number = $payment->generatePaymentNumber();
            $payment->payment_date = $request->payment_date;
            $payment->amount = $request->amount;
            $payment->payment_method = $request->payment_method;
            $payment->reference_number = $request->reference_number;
            $payment->notes = $request->notes;
            $payment->status = 'completed';
            $payment->received_by = auth()->id();
            $payment->save();

            $document->paid_amount += $request->amount;
            $document->balance_amount = $document->total_amount - $document->paid_amount;
            
            if ($document->balance_amount <= 0) {
                $document->status = 'paid';
                $document->paid_at = now();
            } elseif ($document->paid_amount > 0) {
                $document->status = 'partial_paid';
            }
            
            $document->save();

            DB::commit();

            return redirect()
                ->route('billing.payments.show', $payment)
                ->with('success', 'Payment recorded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }

    public function show(BillingPayment $payment)
    {
        $payment->load(['document.client', 'client', 'receiver']);
        
        return view('billing.payments.show', compact('payment'));
    }

    public function edit(BillingPayment $payment)
    {
        if ($payment->status === 'voided') {
            return redirect()
                ->route('billing.payments.show', $payment)
                ->with('error', 'Voided payments cannot be edited.');
        }
        
        $payment->load(['document', 'client']);
        $clients = BillingClient::active()->customers()->get();
        
        return view('billing.payments.edit', compact('payment', 'clients'));
    }

    public function update(Request $request, BillingPayment $payment)
    {
        if ($payment->status === 'voided') {
            return redirect()
                ->route('billing.payments.show', $payment)
                ->with('error', 'Voided payments cannot be edited.');
        }
        
        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,cheque,bank_transfer,credit_card,mobile_payment,other',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $document = $payment->document;
        $oldAmount = $payment->amount;
        $newAmount = $request->amount;
        
        $availableBalance = $document->balance_amount + $oldAmount;
        
        if ($newAmount > $availableBalance) {
            return back()
                ->withInput()
                ->with('error', 'Payment amount cannot exceed available balance of ' . number_format($availableBalance, 2));
        }

        DB::beginTransaction();
        
        try {
            $payment->update($request->only([
                'payment_date', 'amount', 'payment_method', 'reference_number', 'notes'
            ]));

            $document->paid_amount = $document->paid_amount - $oldAmount + $newAmount;
            $document->balance_amount = $document->total_amount - $document->paid_amount;
            
            if ($document->balance_amount <= 0) {
                $document->status = 'paid';
                $document->paid_at = now();
            } elseif ($document->paid_amount > 0) {
                $document->status = 'partial_paid';
            } else {
                $document->status = 'pending';
                $document->paid_at = null;
            }
            
            $document->save();

            DB::commit();

            return redirect()
                ->route('billing.payments.show', $payment)
                ->with('success', 'Payment updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error updating payment: ' . $e->getMessage());
        }
    }

    public function void(BillingPayment $payment)
    {
        if ($payment->status === 'voided') {
            return back()->with('error', 'Payment is already voided.');
        }

        DB::beginTransaction();
        
        try {
            $document = $payment->document;
            
            $payment->update(['status' => 'voided', 'voided_at' => now(), 'voided_by' => auth()->id()]);
            
            $document->paid_amount -= $payment->amount;
            $document->balance_amount = $document->total_amount - $document->paid_amount;
            
            if ($document->balance_amount > 0) {
                if ($document->paid_amount > 0) {
                    $document->status = 'partial_paid';
                } else {
                    $document->status = 'pending';
                }
                $document->paid_at = null;
            }
            
            $document->save();

            DB::commit();

            return back()->with('success', 'Payment voided successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error voiding payment: ' . $e->getMessage());
        }
    }

    public function receipt(BillingPayment $payment)
    {
        $payment->load(['document.client', 'client', 'receiver']);
        
        return view('billing.payments.receipt', compact('payment'));
    }

    public function receiptPDF(BillingPayment $payment)
    {
        $payment->load(['document.client', 'client', 'receiver']);
        
        $pdf = PDF::loadView('billing.payments.receipt-pdf', compact('payment'));
        
        return $pdf->download('receipt-' . $payment->payment_number . '.pdf');
    }

    public function getOutstandingDocuments(Request $request)
    {
        $clientId = $request->client_id;
        
        $documents = BillingDocument::where('client_id', $clientId)
            ->where('document_type', 'invoice')
            ->where('balance_amount', '>', 0)
            ->select('id', 'document_number', 'total_amount', 'balance_amount', 'issue_date')
            ->orderBy('issue_date', 'desc')
            ->get();
        
        return response()->json($documents);
    }
}