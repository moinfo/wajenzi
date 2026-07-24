<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Daily Reports Summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
        th { background: #f2f4f7; font-weight: bold; }
        td.num, th.num { text-align: right; }
        .kpi { background: #f9fafb; }
        .section-title { font-size: 13px; margin: 0 0 6px; }
    </style>
</head>
<body>
    <h1>Sales Daily Reports — Summary</h1>
    <div class="meta">
        Generated: {{ now()->format('Y-m-d H:i') }}
        @if(!empty($summary['filters']['start_date']) || !empty($summary['filters']['end_date']))
            &nbsp;|&nbsp; Period:
            {{ $summary['filters']['start_date'] ?: '—' }}
            to
            {{ $summary['filters']['end_date'] ?: '—' }}
        @endif
        @if(!empty($summary['filters']['status']))
            &nbsp;|&nbsp; Status: {{ $summary['filters']['status'] }}
        @endif
    </div>

    <h3 class="section-title">Totals</h3>
    <table>
        <tr class="kpi">
            <th>Reports</th>
            <th>Invoices Written</th>
            <th class="num">Invoice Total</th>
            <th class="num">Paid</th>
            <th class="num">Unpaid</th>
            <th class="num">Partial</th>
            <th class="num">Payments</th>
            <th>Follow-ups</th>
            <th>Concerns</th>
        </tr>
        <tr>
            <td>{{ $summary['reports_count'] }}</td>
            <td>{{ $summary['invoices_count'] }}</td>
            <td class="num">{{ number_format($summary['invoices_total'], 2) }}</td>
            <td class="num">{{ number_format($summary['paid_total'], 2) }}</td>
            <td class="num">{{ number_format($summary['unpaid_total'], 2) }}</td>
            <td class="num">{{ number_format($summary['partial_total'], 2) }}</td>
            <td class="num">{{ number_format($summary['payments_total'], 2) }}</td>
            <td>{{ $summary['followups_count'] }}</td>
            <td>{{ $summary['concerns_count'] }}</td>
        </tr>
    </table>

    <h3 class="section-title">Invoices Written — by Preparer</h3>
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Prepared By</th>
                <th class="num">Invoices</th>
                <th class="num">Invoice Total</th>
                <th class="num">Paid</th>
                <th class="num">Unpaid</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['by_user'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->user_name }}</td>
                    <td class="num">{{ $row->invoice_count }}</td>
                    <td class="num">{{ number_format($row->invoice_total, 2) }}</td>
                    <td class="num">{{ number_format($row->paid_total, 2) }}</td>
                    <td class="num">{{ number_format($row->unpaid_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">No invoices in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
