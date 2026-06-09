<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use App\Models\Project;
use App\Models\Site;
use App\Models\SitePaylog;
use App\Models\SitePaymentRequest;
use App\Models\SitePaymentRequestFile;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SitePaylogController extends Controller
{
    public function __construct(private ApprovalService $approvalService)
    {
    }

    /**
     * Daily Payments: pick a site/day, see the payment REQUESTS logged for it,
     * and open the modal to create a new request.
     */
    public function index(Request $request)
    {
        $sites    = Site::active()->orderBy('name')->get();
        $channels = PaymentChannel::active()->ordered()->get();

        $siteId = $request->input('site_id');
        $date   = $request->input('date', date('Y-m-d'));

        $requests = collect();
        $site     = null;
        $totals   = ['material' => 0, 'labour' => 0, 'all' => 0];

        if ($siteId) {
            $site = Site::with('currentSupervisor')->find($siteId);

            $requests = SitePaymentRequest::with(['lines.channel', 'creator', 'project', 'approvalStatus'])
                ->forSite($siteId)
                ->forDate($date)
                ->orderByDesc('id')
                ->get();

            $lines = $requests->flatMap->lines;
            $totals['material'] = (float) $lines->where('category', 'material')->sum('amount');
            $totals['labour']   = (float) $lines->where('category', 'labour')->sum('amount');
            $totals['all']      = $totals['material'] + $totals['labour'];
        }

        return view('pages.procurement.site_paylog', [
            'sites'    => $sites,
            'channels' => $channels,
            'projects' => Project::orderBy('project_name')->get(),
            'site'     => $site,
            'siteId'   => $siteId,
            'date'     => $date,
            'requests' => $requests,
            'totals'   => $totals,
        ]);
    }

    /**
     * Create one payment request (header) with its payment lines + supporting
     * documents, then submit it into the approval chain (→ Procurement).
     */
    public function storeBulk(Request $request, $site_id)
    {
        $request->merge(['site_id' => $site_id]);

        $request->validate([
            'site_id'                       => 'required|exists:sites,id',
            'project_id'                    => 'nullable|exists:projects,id',
            'payment_date'                  => 'required|date',
            'payments'                      => 'required|array|min:1',
            'payments.*.payee_name'         => 'required|string|max:255',
            'payments.*.reason'             => 'required|string|max:255',
            'payments.*.category'           => 'required|in:material,labour',
            'payments.*.payment_channel_id' => 'nullable|exists:payment_channels,id',
            'payments.*.account_name'       => 'nullable|string|max:255',
            'payments.*.amount'             => 'required|numeric|min:0.01',
            'documents'                     => 'nullable|array',
            'documents.*'                   => 'file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx|max:4048',
        ]);

        $paymentRequest = DB::transaction(function () use ($request, $site_id) {
            $total = collect($request->payments)
                ->sum(fn ($row) => (float) str_replace(',', '', $row['amount']));

            $paymentRequest = SitePaymentRequest::create([
                'request_number' => SitePaymentRequest::generateRequestNumber(),
                'site_id'        => $site_id,
                'project_id'     => $request->project_id,
                'payment_date'   => $request->payment_date,
                'total_amount'   => $total,
                'status'         => SitePaymentRequest::STATUS_PENDING,
                'created_by'     => auth()->id(),
            ]);

            foreach ($request->payments as $row) {
                $paymentRequest->lines()->create([
                    'site_id'            => $site_id,
                    'project_id'         => $request->project_id,
                    'payment_date'       => $request->payment_date,
                    'category'           => $row['category'],
                    'payee_name'         => $row['payee_name'],
                    'reason'             => $row['reason'],
                    'payment_channel_id' => $row['payment_channel_id'] ?? null,
                    'account_name'       => $row['account_name'] ?? null,
                    'amount'             => str_replace(',', '', $row['amount']),
                    'status'             => 'SUBMITTED',
                    'created_by'         => auth()->id(),
                ]);
            }

            foreach ($request->file('documents', []) as $file) {
                $stored = $file->store('uploads/site_payment_requests', 'public');
                $paymentRequest->files()->create([
                    'file_path'     => '/storage/' . $stored,
                    'original_name' => $file->getClientOriginalName(),
                    'uploaded_by'   => auth()->id(),
                ]);
            }

            return $paymentRequest;
        });

        // Enter the approval flow (fires ProcessSubmittedEvent → notifies Procurement).
        $paymentRequest->submit();

        return redirect()
            ->route('site_paylog', ['site_id' => $site_id, 'date' => $request->payment_date])
            ->with('success', "Payment request {$paymentRequest->request_number} submitted for approval.");
    }

    /**
     * List of payment requests across sites with optional status filtering.
     */
    public function requests(Request $request)
    {
        $query = SitePaymentRequest::with(['site', 'creator', 'project', 'approvalStatus'])
            ->orderByDesc('id');

        if ($request->filled('site_id')) {
            $query->forSite($request->input('site_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('pages.procurement.site_payment_requests', [
            'requests' => $query->paginate(25)->withQueryString(),
            'sites'    => Site::active()->orderBy('name')->get(),
            'siteId'   => $request->input('site_id'),
            'status'   => $request->input('status'),
        ]);
    }

    /**
     * Approval page for a single request (Procurement verify / MD approve /
     * Finance record payment), reusing the shared approval layout.
     */
    public function showRequest($id)
    {
        $paymentRequest = SitePaymentRequest::with([
            'site', 'project', 'creator', 'payer',
            'lines.channel', 'files', 'approvalStatus',
        ])->findOrFail($id);

        // Best-effort: clear the "awaiting your approval" notification for this viewer.
        try {
            $this->approvalService->markNotificationAsRead($id, 0, 'site-paylog/requests');
        } catch (\Throwable $e) {
            // Non-fatal — the page should still render.
        }

        $details = [
            'Request Number'    => $paymentRequest->request_number,
            'Site'              => $paymentRequest->site?->name ?? '—',
            'Project'           => $paymentRequest->project?->project_name ?? 'N/A',
            'Payment Date'      => optional($paymentRequest->payment_date)->format('d M Y'),
            'Total Amount (TZS)' => number_format((float) $paymentRequest->total_amount),
            'Initiated By'      => $paymentRequest->creator?->name ?? '—',
        ];

        return view('approvals._approve_page', [
            'approval_data'             => $paymentRequest,
            'document_id'               => $id,
            'approval_document_type_id' => 0,
            'page_name'                 => 'Site Payment Request',
            'approval_data_name'        => $paymentRequest->creator?->name ?? $paymentRequest->request_number,
            'details'                   => $details,
            'model'                     => 'SitePaymentRequest',
            'route'                     => 'site-paylog/requests',
        ]);
    }

    /**
     * Finance/Accountant records the actual payment against an approved request.
     */
    public function recordPayment(Request $request, $id)
    {
        abort_unless(auth()->user()?->can('Process Site Payment'), 403,
            'You are not authorised to process site payments.');

        $paymentRequest = SitePaymentRequest::findOrFail($id);

        if (!$paymentRequest->isFullyApproved()) {
            return back()->with('error', 'This request must be fully approved before payment can be recorded.');
        }

        if ($paymentRequest->isPaid()) {
            return back()->with('error', 'This request has already been paid.');
        }

        $request->validate([
            'payment_reference' => 'required|string|max:255',
            'payment_date'      => 'required|date',
            'payment_note'      => 'nullable|string|max:1000',
            'payment_slip'      => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:4048',
        ]);

        $slipPath = $paymentRequest->payment_slip;
        if ($request->hasFile('payment_slip')) {
            $slipPath = '/storage/' . $request->file('payment_slip')->store('uploads/site_payment_slips', 'public');
        }

        $paymentRequest->update([
            'status'            => SitePaymentRequest::STATUS_PAID,
            'payment_reference' => $request->payment_reference,
            'payment_note'      => $request->payment_note,
            'payment_slip'      => $slipPath,
            'paid_date'         => $request->payment_date,
            'paid_by'           => auth()->id(),
        ]);

        // Close the loop: tell the initiator their payment has been issued.
        $creator = $paymentRequest->creator;
        if ($creator) {
            $creator->notify(new \App\Notifications\ApprovalNotification([
                'staff_id'         => $creator->id,
                'link'             => "site-paylog/requests/{$paymentRequest->id}",
                'title'            => 'Payment Issued',
                'body'             => "Payment for request {$paymentRequest->request_number} ("
                    . number_format((float) $paymentRequest->total_amount) . " TZS) has been processed by Finance"
                    . ($request->payment_reference ? " (ref: {$request->payment_reference})" : '') . '.',
                'document_id'      => (string) $paymentRequest->id,
                'document_type_id' => '0',
            ]));
        }

        return back()->with('success', "Payment recorded for {$paymentRequest->request_number}.");
    }

    /**
     * Delete a request that has not yet started approval (creator only).
     */
    public function destroyRequest($id)
    {
        $paymentRequest = SitePaymentRequest::findOrFail($id);

        if ($paymentRequest->created_by !== auth()->id()) {
            return back()->with('error', 'Only the creator can delete this request.');
        }

        if ($paymentRequest->isApprovalStarted() || $paymentRequest->isFullyApproved()) {
            return back()->with('error', 'This request is already in the approval process and cannot be deleted.');
        }

        $paymentRequest->forceDelete(); // cascades lines + files + approval rows
        $siteId = $paymentRequest->site_id;
        $date   = optional($paymentRequest->payment_date)->format('Y-m-d');

        return redirect()
            ->route('site_paylog', ['site_id' => $siteId, 'date' => $date])
            ->with('success', 'Payment request deleted.');
    }

    /**
     * One-day report for a site (material vs labour split). CSV when ?export=csv.
     */
    public function dailyReport(Request $request)
    {
        $siteId = $request->input('site_id');
        $date   = $request->input('date', date('Y-m-d'));

        $payments = collect();
        $site     = null;

        if ($siteId) {
            $site     = Site::find($siteId);
            $payments = SitePaylog::with(['channel', 'creator'])
                ->forSite($siteId)
                ->forDate($date)
                ->orderBy('category')
                ->orderBy('id')
                ->get();
        }

        if ($request->input('export') === 'csv' && $siteId) {
            return $this->streamDailyCsv($site, $date, $payments);
        }

        return view('pages.procurement.site_paylog_daily_report', [
            'sites'    => Site::active()->orderBy('name')->get(),
            'site'     => $site,
            'siteId'   => $siteId,
            'date'     => $date,
            'payments' => $payments,
            'totals'   => [
                'material' => (float) $payments->where('category', 'material')->sum('amount'),
                'labour'   => (float) $payments->where('category', 'labour')->sum('amount'),
                'all'      => (float) $payments->sum('amount'),
            ],
        ]);
    }

    /**
     * Monthly roll-up across a site (material vs labour totals).
     */
    public function monthlyReport(Request $request)
    {
        $siteId = $request->input('site_id');
        $month  = $request->input('month', date('Y-m')); // format: YYYY-MM
        [$year, $monthNum] = array_pad(explode('-', $month), 2, null);

        $site     = $siteId ? Site::find($siteId) : null;
        $payments = collect();

        if ($siteId && $year && $monthNum) {
            $payments = SitePaylog::with('channel')
                ->forSite($siteId)
                ->forMonth($year, $monthNum)
                ->orderBy('payment_date')
                ->get();
        }

        $summary = $payments
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => ucfirst($category),
                'count'    => $rows->count(),
                'total'    => (float) $rows->sum('amount'),
            ])
            ->values();

        return view('pages.procurement.site_paylog_monthly_report', [
            'sites'      => Site::active()->orderBy('name')->get(),
            'site'       => $site,
            'siteId'     => $siteId,
            'month'      => $month,
            'payments'   => $payments,
            'summary'    => $summary,
            'grandTotal' => (float) $payments->sum('amount'),
        ]);
    }

    /**
     * Editable payment-channel list (banks / mobile money / cash).
     */
    public function channels(Request $request)
    {
        if ($this->handleCrud($request, 'PaymentChannel')) {
            return back();
        }

        return view('pages.procurement.site_paylog_channels', [
            'channels' => PaymentChannel::ordered()->get(),
        ]);
    }

    private function streamDailyCsv($site, $date, $payments)
    {
        $filename = 'site-paylog-' . ($site->name ?? 'site') . '-' . $date . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['Category', 'Payee', 'Reason', 'Channel', 'Account Name', 'Amount (TZS)', 'Logged By']);
            foreach ($payments as $p) {
                fputcsv($out, [
                    ucfirst($p->category),
                    $p->payee_name,
                    $p->reason,
                    $p->channel->name ?? '',
                    $p->account_name,
                    number_format((float) $p->amount, 2, '.', ''),
                    $p->creator->name ?? '',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', 'TOTAL', number_format((float) $payments->sum('amount'), 2, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
