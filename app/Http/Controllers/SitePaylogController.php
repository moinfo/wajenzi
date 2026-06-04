<?php

namespace App\Http\Controllers;

use App\Models\PaymentChannel;
use App\Models\Project;
use App\Models\Site;
use App\Models\SitePaylog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SitePaylogController extends Controller
{
    /**
     * Step 1 + Step 5: select a site, see the payments logged for the chosen day.
     * Also handles inline edit/delete of a single payment via handleCrud.
     */
    public function index(Request $request)
    {
        // Single-row update / delete from the table (multi-row create -> storeBulk)
        if ($this->handleCrud($request, 'SitePaylog')) {
            return back();
        }

        $sites    = Site::active()->orderBy('name')->get();
        $channels = PaymentChannel::active()->ordered()->get();

        $siteId = $request->input('site_id');
        $date   = $request->input('date', date('Y-m-d'));

        $payments = collect();
        $site     = null;
        $totals   = ['material' => 0, 'labour' => 0, 'all' => 0];

        if ($siteId) {
            $site = Site::with('currentSupervisor')->find($siteId);

            $payments = SitePaylog::with(['channel', 'creator', 'project'])
                ->forSite($siteId)
                ->forDate($date)
                ->orderByDesc('id')
                ->get();

            $totals['material'] = (float) $payments->where('category', 'material')->sum('amount');
            $totals['labour']   = (float) $payments->where('category', 'labour')->sum('amount');
            $totals['all']      = $totals['material'] + $totals['labour'];
        }

        return view('pages.procurement.site_paylog', [
            'sites'    => $sites,
            'channels' => $channels,
            'projects' => Project::orderBy('project_name')->get(),
            'site'     => $site,
            'siteId'   => $siteId,
            'date'     => $date,
            'payments' => $payments,
            'totals'   => $totals,
        ]);
    }

    /**
     * Step 3 + Step 4: save several payments for one site/day in a single submit.
     */
    public function storeBulk(Request $request, $site_id)
    {
        $request->merge(['site_id' => $site_id]);

        $request->validate([
            'site_id'                  => 'required|exists:sites,id',
            'project_id'               => 'nullable|exists:projects,id',
            'payment_date'             => 'required|date',
            'payments'                 => 'required|array|min:1',
            'payments.*.payee_name'    => 'required|string|max:255',
            'payments.*.reason'        => 'required|string|max:255',
            'payments.*.category'      => 'required|in:material,labour',
            'payments.*.payment_channel_id' => 'nullable|exists:payment_channels,id',
            'payments.*.account_name'  => 'nullable|string|max:255',
            'payments.*.amount'        => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $site_id) {
            foreach ($request->payments as $row) {
                SitePaylog::create([
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
        });

        return redirect()
            ->route('site_paylog', ['site_id' => $site_id, 'date' => $request->payment_date])
            ->with('success', count($request->payments) . ' payment(s) logged.');
    }

    /**
     * Step 6: one-day report for a site (material vs labour split). CSV export
     * when ?export=csv is present.
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
     * Step 7: monthly roll-up across a site (the data feeding the monthly report).
     *
     * ── CONTRIBUTION POINT ───────────────────────────────────────────────────
     * The grouping below is where YOUR domain judgment matters. Right now it
     * produces one summary row per (category) so the report shows material-vs-
     * labour monthly totals. You flagged interest in a richer breakdown. Decide
     * how finance wants the monthly report sliced and build $summary accordingly:
     *   - by category only            (current default)
     *   - by category × channel       (e.g. how much went out via CRDB vs Cash)
     *   - by day                      (a running daily ledger for the month)
     * Return an array/collection of rows the blade can loop over. Keep it ~5-10
     * lines; the view (site_paylog_monthly_report.blade.php) already renders
     * whatever keys you put on each row.
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

        // TODO(you): shape $summary per the contribution note above.
        $summary = $payments
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => ucfirst($category),
                'count'    => $rows->count(),
                'total'    => (float) $rows->sum('amount'),
            ])
            ->values();

        return view('pages.procurement.site_paylog_monthly_report', [
            'sites'    => Site::active()->orderBy('name')->get(),
            'site'     => $site,
            'siteId'   => $siteId,
            'month'    => $month,
            'payments' => $payments,
            'summary'  => $summary,
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
