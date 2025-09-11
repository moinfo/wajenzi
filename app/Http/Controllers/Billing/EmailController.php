<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\BillingDocumentEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceEmail;

class EmailController extends Controller
{
    /**
     * Display all sent emails
     */
    public function index(Request $request)
    {
        $emails = BillingDocumentEmail::with(['document.client', 'sender'])
            ->when($request->document_type, function ($query, $type) {
                return $query->where('document_type', $type);
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->recipient_email, function ($query, $email) {
                return $query->where('recipient_email', 'like', "%{$email}%");
            })
            ->when($request->from_date, function ($query, $fromDate) {
                return $query->where('sent_at', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                return $query->where('sent_at', '<=', $toDate);
            })
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('billing.emails.index', compact('emails'));
    }

    /**
     * Show email details
     */
    public function show(BillingDocumentEmail $email)
    {
        $email->load(['document.client', 'sender']);
        return view('billing.emails.show', compact('email'));
    }

    /**
     * Resend an email
     */
    public function resend(Request $request, BillingDocumentEmail $email)
    {
        $request->validate([
            'email' => 'required|email',
            'cc' => 'nullable|string',
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);

        // Load the document relationship
        $originalEmail = $email;
        $originalEmail->load('document');
        $document = $originalEmail->document;
        
        if (!$document) {
            return back()->with('error', 'Document not found for this email.');
        }

        DB::beginTransaction();
        
        try {
            $mail = Mail::to($request->email);
            
            // Add CC emails if provided
            if ($request->cc) {
                $ccEmails = array_filter(array_map('trim', explode(',', $request->cc)));
                if (!empty($ccEmails)) {
                    $mail->cc($ccEmails);
                }
            }
            
            // Send the email
            $mail->send(new InvoiceEmail($document, $request->subject, $request->message));
            
            // Track the new email
            $document->emails()->create([
                'document_type' => $document->document_type,
                'recipient_email' => $request->email,
                'cc_emails' => $request->cc,
                'subject' => $request->subject,
                'message' => $request->message,
                'has_attachment' => true,
                'attachment_filename' => $document->document_type . '-' . $document->document_number . '.pdf',
                'status' => 'sent',
                'sent_by' => auth()->id(),
                'sent_at' => now()
            ]);
            
            // Update document status if it wasn't already sent
            if (!in_array($document->status, ['sent', 'viewed'])) {
                $document->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);
            }
            
            DB::commit();
            
            return back()->with('success', 'Email resent successfully to ' . $request->email);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Track the failed email attempt
            try {
                $document->emails()->create([
                    'document_type' => $document->document_type,
                    'recipient_email' => $request->email,
                    'cc_emails' => $request->cc,
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'has_attachment' => true,
                    'attachment_filename' => $document->document_type . '-' . $document->document_number . '.pdf',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_by' => auth()->id(),
                    'sent_at' => now()
                ]);
            } catch (\Exception $trackingException) {
                // Log but don't fail on tracking error
            }
            
            return back()->with('error', 'Failed to resend email: ' . $e->getMessage());
        }
    }

    /**
     * Show resend form
     */
    public function showResendForm(BillingDocumentEmail $email)
    {
        $email->load(['document.client']);
        
        if (!$email->document) {
            return redirect()->route('billing.emails.index')
                ->with('error', 'Document not found for this email.');
        }
        
        return view('billing.emails.resend', compact('email'));
    }
}
