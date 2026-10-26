<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Payslip {{ $slip['period_label'] ?? '' }}</title>
<style>
    @page { margin: 24px 26px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1b2a36; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    .top { height: 6px; background: #193340; }
    .box { border: 1px solid #dfe5ec; }
    .muted { color: #6b7a86; }
    .right { text-align: right; }
    .brand td { vertical-align: top; }
    .company { font-size: 14px; font-weight: bold; color: #193340; }
    .meta { font-size: 9px; color: #6b7a86; line-height: 1.5; }
    .badge { display: inline-block; border: 1px solid #9adfc2; background: #e6f8f0; color: #0e7a55; font-size: 8.5px; font-weight: bold; padding: 3px 9px; }
    .doc-title { font-size: 26px; font-weight: bold; letter-spacing: 1px; color: #111b2b; margin: 6px 0 0 0; }
    .grid td { border: 1px solid #dfe5ec; padding: 7px 9px; width: 25%; vertical-align: top; }
    .grid .l { font-size: 7.5px; color: #6b7a86; font-weight: bold; text-transform: uppercase; }
    .grid .v { font-size: 10px; font-weight: bold; margin-top: 2px; }
    .panel { border: 1px solid #dfe5ec; }
    .panel th { text-align: left; padding: 9px 11px; font-size: 9px; letter-spacing: 0.4px; text-transform: uppercase; }
    .panel td { padding: 8px 11px; border-top: 1px solid #e8edf2; font-size: 10px; }
    .panel .total td, .panel .total th { border-top: 1.5px solid #c9d3dd; background: #f1f5f9; font-weight: bold; padding: 9px 11px; font-size: 10px; text-transform: none; letter-spacing: 0; }
    .earn th { background: #e9f8f2; color: #0e7a55; }
    .ded th { background: #fff1e6; color: #b4501a; }
    .net { background: #193340; color: #ffffff; }
    .net td { padding: 14px 16px; vertical-align: middle; }
    .net .k { font-size: 8px; color: #cfe0e3; text-transform: uppercase; letter-spacing: 0.5px; }
    .net .big { font-size: 22px; font-weight: bold; }
    .foot { text-align: center; color: #6b7a86; font-size: 8.5px; border-top: 1px solid #dfe5ec; padding-top: 8px; }
    .sum td { border: 1px solid #dfe5ec; padding: 9px 11px; width: 33%; }
    .sum .k { font-size: 8px; color: #6b7a86; text-transform: uppercase; font-weight: bold; }
    .sum .v { font-size: 13px; font-weight: bold; margin-top: 2px; }
</style>
</head>
<body>
<div class="top"></div>

<table class="brand" style="margin-top: 14px;">
    <tr>
        <td style="width: 66px;">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" style="width: 54px; height: 54px;">
            @endif
        </td>
        <td>
            <div class="company">{{ $slip['company']['name'] ?? $slip['organization'] ?? '' }}</div>
            <div class="meta">
                @foreach(($slip['company']['lines'] ?? []) as $line)
                    {{ $line }}<br>
                @endforeach
            </div>
        </td>
        <td class="right" style="width: 150px;">
            <span class="badge">{{ strtoupper($slip['badge'] ?? '') }}</span>
            <div class="doc-title">{{ strtoupper($slip['document_title'] ?? 'PAYSLIP') }}</div>
            <div class="muted" style="font-size: 10px; font-weight: bold;">{{ $slip['document_period'] ?? '' }}</div>
        </td>
    </tr>
</table>

<table class="sum" style="margin-top: 14px;">
    <tr>
        @foreach(($slip['summary'] ?? []) as $item)
            <td>
                <div class="k">{{ $item['label'] }}</div>
                <div class="v">{{ $item['value'] }}</div>
            </td>
        @endforeach
    </tr>
</table>

<table class="grid" style="margin-top: 12px;">
    @foreach(array_chunk($slip['employee'] ?? [], 4) as $row)
        <tr>
            @foreach($row as $field)
                <td>
                    <div class="l">{{ $field['label'] }}</div>
                    <div class="v">{{ $field['value'] }}</div>
                </td>
            @endforeach
            @for($i = count($row); $i < 4; $i++)
                <td></td>
            @endfor
        </tr>
    @endforeach
</table>

<table style="margin-top: 14px;">
    <tr>
        <td style="width: 49%; vertical-align: top;">
            <table class="panel earn">
                <tr>
                    <th>{{ $slip['income']['title'] ?? 'Employee Income' }}</th>
                    <th class="right">{{ $slip['income']['amount'] ?? '' }}</th>
                </tr>
                @foreach(($slip['income']['rows'] ?? []) as $row)
                    <tr><td>{{ $row['name'] }}</td><td class="right">{{ $row['value'] }}</td></tr>
                @endforeach
                <tr class="total">
                    <td>{{ $slip['income']['total_label'] ?? 'Gross Salary' }}</td>
                    <td class="right">{{ $slip['income']['total'] ?? '' }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 2%;"></td>
        <td style="width: 49%; vertical-align: top;">
            <table class="panel ded">
                <tr>
                    <th>{{ $slip['deductions']['title'] ?? 'Deductions' }}</th>
                    <th class="right">{{ $slip['deductions']['amount'] ?? '' }}</th>
                </tr>
                @foreach(($slip['deductions']['rows'] ?? []) as $row)
                    <tr><td>{{ $row['name'] }}</td><td class="right">{{ $row['value'] }}</td></tr>
                @endforeach
                <tr class="total">
                    <td>{{ $slip['deductions']['total_label'] ?? 'Total Deductions' }}</td>
                    <td class="right">{{ $slip['deductions']['total'] ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="net" style="margin-top: 14px;">
    <tr>
        <td>
            <div class="k">{{ $slip['net']['label'] ?? 'Net Salary' }}</div>
            <div class="big">{{ $slip['net']['amount'] ?? '' }}</div>
        </td>
        <td class="right">
            <div class="k">{{ $slip['net']['payment_label'] ?? 'Payment Account' }}</div>
            <div style="font-size: 11px; font-weight: bold;">{{ $slip['net']['payment_account'] ?? '' }}</div>
        </td>
    </tr>
</table>

<div class="foot" style="margin-top: 18px;">
    {{ $slip['footer']['note'] ?? '' }}<br>
    <strong style="color: #193340;">{{ $slip['footer']['organization'] ?? '' }}</strong>
</div>
</body>
</html>
