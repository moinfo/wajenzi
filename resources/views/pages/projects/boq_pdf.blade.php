@php
if (!function_exists('renderPdfSection')) {
    function renderPdfSection($section, $depth, &$counter) {
        $paddingLeft = 8 + ($depth * 12);
        $bgColor = $depth == 0 ? '#d6e9f8' : '#e8f0f8';
        $fontSize = $depth == 0 ? '11px' : '10px';

        // Section header with subtotal
        echo '<tr style="background-color: ' . $bgColor . '; font-weight: bold;">';
        echo '<td style="padding: 5px ' . $paddingLeft . 'px; font-size: ' . $fontSize . ';" colspan="5">' . e($section->name) . '</td>';
        echo '<td></td>';
        echo '<td class="text-right" style="padding: 5px; font-size: 10px;">' . number_format($section->subtotal, 2) . '</td>';
        echo '</tr>';

        // Items
        foreach ($section->items as $item) {
            $counter++;
            $labourBg = $item->item_type == 'labour' ? ' background-color: #fefce8;' : '';
            echo '<tr style="' . $labourBg . '">';
            echo '<td class="text-center" style="padding: 3px 4px; font-size: 9px; color: #999;">' . $counter . '</td>';
            echo '<td style="padding: 3px 5px 3px ' . ($paddingLeft + 10) . 'px; font-size: 10px;">' . e($item->description);
            if ($item->specification) echo ' <span style="color: #888; font-size: 9px;">(' . e($item->specification) . ')</span>';
            if ($item->item_type == 'labour') echo ' <em style="color: #b45309; font-size: 9px;">[Labour]</em>';
            echo '</td>';
            echo '<td class="text-center" style="padding: 3px 4px; font-size: 10px;">' . e($item->unit) . '</td>';
            echo '<td class="text-right" style="padding: 3px 5px; font-size: 10px;">' . number_format($item->quantity, 2) . '</td>';
            echo '<td class="text-right" style="padding: 3px 5px; font-size: 10px;">' . number_format($item->unit_price, 2) . '</td>';
            echo '<td class="text-right" style="padding: 3px 5px; font-size: 10px;">' . number_format($item->total_price, 2) . '</td>';
            echo '</tr>';
        }

        // Children
        foreach ($section->childrenRecursive as $child) {
            renderPdfSection($child, $depth + 1, $counter);
        }

        // Subtotal row (only if section has content)
        if ($section->items->count() > 0 || $section->childrenRecursive->count() > 0) {
            echo '<tr style="border-top: 1px solid #aaa;">';
            echo '<td colspan="5" style="text-align: right; padding: 3px 5px; font-weight: bold; font-size: 9px; color: #555;">Subtotal — ' . e($section->name) . ':</td>';
            echo '<td class="text-right" style="padding: 3px 5px; font-weight: bold; font-size: 10px;">' . number_format($section->subtotal, 2) . '</td>';
            echo '</tr>';
        }
    }
}
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>BOQ - {{ $boq->project->project_name ?? 'Project' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        .header-table {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #2c5f8a;
            padding-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c5f8a;
        }
        .company-details {
            font-size: 9px;
            color: #666;
            line-height: 1.4;
        }
        .boq-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0 8px 0;
            text-transform: uppercase;
            color: #2c5f8a;
            letter-spacing: 2px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 8px;
            font-size: 10px;
            border: 1px solid #eee;
        }
        .meta-table .label {
            font-weight: bold;
            background-color: #f5f5f5;
            width: 80px;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th {
            background-color: #2c5f8a;
            color: white;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 3px 4px;
            border-bottom: 1px solid #e5e5e5;
            font-size: 10px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .grand-total-row {
            background-color: #2c5f8a;
            color: white;
            font-weight: bold;
        }
        .grand-total-row td {
            padding: 8px 5px;
            border: none;
            font-size: 11px;
        }
        .summary-box {
            width: 250px;
            margin-left: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 4px 8px;
            font-size: 10px;
            border-bottom: 1px solid #eee;
        }
        .summary-box .total-row td {
            background-color: #2c5f8a;
            color: white;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 8px;
        }
        .footer-info {
            text-align: center;
            margin-top: 20px;
            font-size: 8px;
            color: #aaa;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    {{-- Header with logo left, company info right --}}
    <table class="header-table">
        <tr>
            <td style="width: 60px;">
                @if(file_exists(public_path('media/logo/wajenzilogo.png')))
                    <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 45px;">
                @endif
            </td>
            <td>
                <div class="company-name">{{ config('app.name') }}</div>
                <div class="company-details">
                    PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA |
                    P. O. Box 14492, Dar es Salaam |
                    +255 793 444 400 | billing@wajenziprofessional.co.tz |
                    TIN: 154-867-805
                </div>
            </td>
            <td style="text-align: right; width: 100px;">
                <div style="font-size: 9px; color: #888;">{{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="boq-title">Bill of Quantities</div>

    {{-- Meta info in a compact grid --}}
    <table class="meta-table">
        <tr>
            <td class="label">Project</td>
            <td>{{ $boq->project->project_name ?? 'N/A' }}</td>
            <td class="label">Version</td>
            <td>{{ $boq->version }}</td>
            <td class="label">Status</td>
            <td>{{ ucfirst($boq->status) }}</td>
        </tr>
        <tr>
            <td class="label">Type</td>
            <td>{{ ucfirst($boq->type) }}</td>
            <td class="label">Items</td>
            <td>{{ $boq->items->count() }}</td>
            <td class="label">Sections</td>
            <td>{{ $boq->rootSections->count() }}</td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">S/N</th>
                <th style="width: 38%;">Description</th>
                <th style="width: 8%;" class="text-center">Unit</th>
                <th style="width: 12%;" class="text-right">Qty</th>
                <th style="width: 17%;" class="text-right">Rate (TZS)</th>
                <th style="width: 21%;" class="text-right">Amount (TZS)</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 0; @endphp

            {{-- Sections --}}
            @foreach($boq->rootSections as $section)
                @php renderPdfSection($section, 0, $counter); @endphp
            @endforeach

            {{-- Unsectioned items --}}
            @if($boq->unsectionedItems->count() > 0)
                @if($boq->rootSections->count() > 0)
                    <tr style="background-color: #e9ecef; font-weight: bold;">
                        <td colspan="5" style="padding: 5px;">Other Items</td>
                        <td class="text-right" style="padding: 5px;">{{ number_format($boq->unsectionedItems->sum('total_price'), 2) }}</td>
                    </tr>
                @endif
                @foreach($boq->unsectionedItems as $item)
                    @php $counter++; @endphp
                    <tr @if($item->item_type == 'labour') style="background-color: #fefce8;" @endif>
                        <td class="text-center" style="font-size: 9px; color: #999;">{{ $counter }}</td>
                        <td style="padding: 3px 5px;">
                            {{ $item->description }}
                            @if($item->specification) <span style="color: #888; font-size: 9px;">({{ $item->specification }})</span> @endif
                            @if($item->item_type == 'labour') <em style="color: #b45309; font-size: 9px;">[Labour]</em> @endif
                        </td>
                        <td class="text-center">{{ $item->unit }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            @endif

            {{-- Grand Total --}}
            <tr class="grand-total-row">
                <td colspan="5" style="text-align: right; padding-right: 10px;">GRAND TOTAL (TZS):</td>
                <td class="text-right">{{ number_format($boq->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Summary box --}}
    <table class="summary-box">
        <tr>
            <td style="font-weight: bold; color: #555;">Total Materials</td>
            <td class="text-right">{{ number_format($boq->items()->where('item_type', 'material')->sum('total_price'), 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #555;">Total Labour</td>
            <td class="text-right">{{ number_format($boq->items()->where('item_type', 'labour')->sum('total_price'), 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>Grand Total</td>
            <td class="text-right">TZS {{ number_format($boq->total_amount, 2) }}</td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer-info">
        Generated on {{ now()->format('d/m/Y H:i') }} | {{ config('app.name') }} — Bill of Quantities | {{ $boq->project->project_name ?? '' }} v{{ $boq->version }}
    </div>
</body>
</html>