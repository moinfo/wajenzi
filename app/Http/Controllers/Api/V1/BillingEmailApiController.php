<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceEmail;
use App\Models\BillingDocumentEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BillingEmailApiController extends Controller
{
    public function index(Request $request): JsonResponse
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
            ->orderByDesc('sent_at')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $emails->getCollection()->map(fn (BillingDocumentEmail $email) => $this->serializeEmail($email)),
            'meta' => [
                'current_page' => $emails->currentPage(),
                'last_page' => $emails->lastPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $email = BillingDocumentEmail::with(['document.client', 'sender'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serializeEmail($email),
        ]);
    }

    public function resend(Request $request, int $id): JsonResponse
    {
        $email = BillingDocumentEmail::with('document')->findOrFail($id);

        $validated = $request->validate([
            'email' => 'required|email',
            'cc' => 'nullable|string',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        $document = $email->document;
        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found for this email.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $mail = Mail::to($validated['email']);
            if (!empty($validated['cc'])) {
                $ccEmails = array_filter(array_map('trim', explode(',', $validated['cc'])));
                if (!empty($ccEmails)) {
                    $mail->cc($ccEmails);
                }
            }

            $resentViaFallback = false;
            $mailMessage = new InvoiceEmail($document, $validated['subject'], $validated['message']);
            try {
                $mail->send($mailMessage);
            } catch (\Throwable $mailException) {
                if (!app()->environment('local')) {
                    throw $mailException;
                }

                Mail::mailer('log')
                    ->to($validated['email'])
                    ->when(!empty($validated['cc']), function ($pendingMail) use ($validated) {
                        $ccEmails = array_filter(array_map('trim', explode(',', (string) $validated['cc'])));
                        if (!empty($ccEmails)) {
                            $pendingMail->cc($ccEmails);
                        }
                    })
                    ->send($mailMessage);

                $resentViaFallback = true;
            }

            $newEmail = $document->emails()->create([
                'document_type' => $document->document_type,
                'recipient_email' => $validated['email'],
                'cc_emails' => $validated['cc'] ?? null,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'has_attachment' => true,
                'attachment_filename' => $document->document_type . '-' . $document->document_number . '.pdf',
                'status' => 'sent',
                'sent_by' => $request->user()->id,
                'sent_at' => now(),
            ]);

            if (!in_array($document->status, ['sent', 'viewed'])) {
                $document->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $resentViaFallback
                    ? 'Email recorded successfully using the local log mailer.'
                    : 'Email resent successfully.',
                'data' => $this->serializeEmail($newEmail->load(['document.client', 'sender'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            try {
                $document->emails()->create([
                    'document_type' => $document->document_type,
                    'recipient_email' => $validated['email'],
                    'cc_emails' => $validated['cc'] ?? null,
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'has_attachment' => true,
                    'attachment_filename' => $document->document_type . '-' . $document->document_number . '.pdf',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_by' => $request->user()->id,
                    'sent_at' => now(),
                ]);
            } catch (\Exception $trackingException) {
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend email: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function serializeEmail(BillingDocumentEmail $email): array
    {
        return [
            'id' => $email->id,
            'document_id' => $email->document_id,
            'document_type' => $email->document_type,
            'recipient_email' => $email->recipient_email,
            'cc_emails' => $email->cc_emails,
            'subject' => $email->subject,
            'message' => $email->message,
            'has_attachment' => (bool) $email->has_attachment,
            'attachment_filename' => $email->attachment_filename,
            'status' => $email->status,
            'error_message' => $email->error_message,
            'sent_at' => $email->sent_at?->toISOString(),
            'document' => $email->document ? [
                'id' => $email->document->id,
                'document_number' => $email->document->document_number,
                'document_type' => $email->document->document_type,
                'status' => $email->document->status,
                'total_amount' => $email->document->total_amount,
                'currency_code' => $email->document->currency_code,
                'issue_date' => $email->document->issue_date?->toDateString(),
                'client' => $email->document->client ? [
                    'id' => $email->document->client->id,
                    'name' => trim(($email->document->client->first_name ?? '') . ' ' . ($email->document->client->last_name ?? '')),
                    'email' => $email->document->client->email,
                ] : null,
            ] : null,
            'sender' => $email->sender ? [
                'id' => $email->sender->id,
                'name' => $email->sender->name,
            ] : null,
        ];
    }
}
