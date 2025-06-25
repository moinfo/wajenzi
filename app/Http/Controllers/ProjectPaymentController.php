<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvoice;
use App\Models\ProjectPayment;
use Illuminate\Http\Request;


class ProjectPaymentController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectPayment')) {
            return back();
        }

        $payments = ProjectPayment::with(['invoice'])
            ->when($request->start_date, function($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->start_date);
            })
            ->when($request->end_date, function($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->end_date);
            })
            ->when($request->status, function($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->get();

        $total_amount = $payments->sum('amount');

        $data = [
            'payments' => $payments,
            'total_amount' => $total_amount
        ];
        return view('pages.projects.project_payments')->with($data);
    }

    public function processPayment(Request $request) {
        $invoice = ProjectInvoice::findOrFail($request->invoice_id);

        // Check if payment amount is valid
        $remainingAmount = $invoice->amount - $invoice->payments->sum('amount');
        if($request->amount > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds remaining invoice amount'
            ], 400);
        }

        $payment = ProjectPayment::create([
            'invoice_id' => $request->invoice_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'status' => 'pending'
        ]);

        // Update invoice status if fully paid
        if(($invoice->payments->sum('amount') + $request->amount) >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    public function confirmPayment($id) {
        $payment = ProjectPayment::findOrFail($id);

        $payment->update([
            'status' => 'completed',
            'confirmed_at' => now()
        ]);

        // Send notification
        $payment->invoice->project->manager->notify(new PaymentConfirmed($payment));

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    public function generateReceipt($id) {
        $payment = ProjectPayment::with(['invoice.project'])->findOrFail($id);

        // Receipt generation logic here

        return response()->download($receiptPath);
    }
}
