<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Daily Reports Summary</title>
</head>
<body>
    <h2>Sales Daily Reports — Summary</h2>
    <p>
        Generated: {{ now()->format('Y-m-d H:i') }}
        @if(!empty($summary['filters']['start_date']) || !empty($summary['filters']['end_date']))
            | Period:
            {{ $summary['filters']['start_date'] ?: '—' }}
            to
            {{ $summary['filters']['end_date'] ?: '—' }}
        @endif
        @if(!empty($summary['filters']['status']))
            | Status: {{ $summary['filters']['status'] }}
        @endif
    </p>

    <h3>Totals</h3>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Reports</th>
            <th>Invoices Written</th>
            <th>Invoice Total</th>
            <th>Paid Total</th>
            <th>Unpaid Total</th>
            <th>Partial Total</th>
            <th>Payments Received</th>
            <th>Follow-ups</th>
            <th>Client Concerns</th>
        </tr>
        <tr>
            <td>{{ $summary['reports_count'] }}</td>
            <td>{{ $summary['invoices_count'] }}</td>
            <td>{{ number_format($summary['invoices_total'], 2) }}</td>
            <td>{{ number_format($summary['paid_total'], 2) }}</td>
            <td>{{ number_format($summary['unpaid_total'], 2) }}</td>
            <td>{{ number_format($summary['partial_total'], 2) }}</td>
            <td>{{ number_format($summary['payments_total'], 2) }}</td>
            <td>{{ $summary['followups_count'] }}</td>
            <td>{{ $summary['concerns_count'] }}</td>
        </tr>
    </table>

    <h3>Invoices Written — by Preparer</h3>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Prepared By</th>
                <th>Invoices Count</th>
                <th>Invoice Total</th>
                <th>Paid</th>
                <th>Unpaid</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary['by_user'] as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->user_name }}</td>
                    <td>{{ $row->invoice_count }}</td>
                    <td>{{ number_format($row->invoice_total, 2) }}</td>
                    <td>{{ number_format($row->paid_total, 2) }}</td>
                    <td>{{ number_format($row->unpaid_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No invoices in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
