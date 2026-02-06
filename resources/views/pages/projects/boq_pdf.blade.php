@php
if (!function_exists('renderPdfSection')) {
    function renderPdfSection($section, $depth) {
        $rowClass = $depth == 0 ? 'section-row' : 'subsection-row';
        $paddingLeft = 10 + ($depth * 15);

        echo '<tr class="' . $rowClass . '">';
        echo '<td colspan="6" style="padding-left: ' . $paddingLeft . 'px;">' . e($section->name) . '</td>';
        echo '</tr>';

        foreach ($section->items as $item) {
            $itemPadding = $paddingLeft + 15;
            $labourClass = $item->item_type == 'labour' ? 'labour-row' : '';
            echo '<tr class="' . $labourClass . '">';
            echo '<td style="padding-left: ' . $itemPadding . 'px;">' . e($item->description);
            if ($item->item_type == 'labour') echo ' <em>(Labour)</em>';
            echo '</td>';
            echo '<td>' . e($item->specification ?? '') . '</td>';
            echo '<td class="text-center">' . e($item->unit) . '</td>';
            echo '<td class="text-right">' . number_format($item->quantity, 2) . '</td>';
            echo '<td class="text-right">' . number_format($item->unit_price, 2) . '</td>';
            echo '<td class="text-right">' . number_format($item->total_price, 2) . '</td>';
            echo '</tr>';
        }

        foreach ($section->childrenRecursive as $child) {
            renderPdfSection($child, $depth + 1);
        }

        echo '<tr class="subtotal-row">';
        echo '<td colspan="5" style="text-align: right; padding-left: ' . $paddingLeft . 'px;">Subtotal — ' . e($section->name) . ':</td>';
        echo '<td class="text-right">' . number_format($section->subtotal, 2) . '</td>';
        echo '</tr>';
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
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .company-details {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .boq-title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 5px 0;
            text-transform: uppercase;
        }
        .boq-meta {
            width: 100%;
            margin-bottom: 20px;
        }
        .boq-meta td {
            padding: 3px 10px;
            font-size: 12px;
        }
        .boq-meta .label {
            font-weight: bold;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .items-table th {
            background-color: #333;
            color: white;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        .items-table td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .section-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .section-row td {
            padding: 6px 5px;
            border-bottom: 1px solid #ccc;
        }
        .subsection-row {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .subsection-row td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        .subtotal-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 10px;
        }
        .subtotal-row td {
            padding: 5px;
            border-top: 1px solid #999;
        }
        .labour-row {
            background-color: #fff8e1;
        }
        .grand-total-row {
            background-color: #333;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        .grand-total-row td {
            padding: 10px 5px;
            border: none;
        }
        .footer-info {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if(file_exists(public_path('media/logo/wajenzilogo.png')))
            <img src="{{ public_path('media/logo/wajenzilogo.png') }}" alt="{{ config('app.name') }}" style="max-height: 50px; margin-bottom: 8px;">
        @endif
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="company-details">
            PSSSF COMMERCIAL COMPLEX, SAM NUJOMA ROAD, DSM-TANZANIA<br>
            P. O. Box 14492, Dar es Salaam Tanzania<br>
            Phone: +255 793 444 400 | Email: billing@wajenziprofessional.co.tz
        </div>
    </div>

    <!-- Title -->
    <div class="boq-title">Bill of Quantities</div>

    <!-- Meta Information -->
    <table class="boq-meta">
        <tr>
            <td class="label">Project:</td>
            <td>{{ $boq->project->project_name ?? 'N/A' }}</td>
            <td class="label">Version:</td>
            <td>{{ $boq->version }}</td>
        </tr>
        <tr>
            <td class="label">Type:</td>
            <td>{{ ucfirst($boq->type) }}</td>
            <td class="label">Date:</td>
            <td>{{ now()->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status:</td>
            <td>{{ ucfirst($boq->status) }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 35%;">Description</th>
                <th style="width: 15%;">Specification</th>
                <th style="width: 8%;" class="text-center">Unit</th>
                <th style="width: 10%;" class="text-right">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 17%;" class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            {{-- Render sections recursively --}}
            @foreach($boq->rootSections as $section)
                @php renderPdfSection($section, 0); @endphp
            @endforeach

            {{-- Unsectioned items --}}
            @if($boq->unsectionedItems->count() > 0)
                @if($boq->rootSections->count() > 0)
                    <tr class="section-row">
                        <td colspan="6">Other Items</td>
                    </tr>
                @endif
                @foreach($boq->unsectionedItems as $item)
                    <tr class="{{ $item->item_type == 'labour' ? 'labour-row' : '' }}">
                        <td style="padding-left: 15px;">
                            {{ $item->description }}
                            @if($item->item_type == 'labour') <em>(Labour)</em> @endif
                        </td>
                        <td>{{ $item->specification ?? '' }}</td>
                        <td class="text-center">{{ $item->unit }}</td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="5" style="text-align: right;">Subtotal — Other Items:</td>
                    <td class="text-right">{{ number_format($boq->unsectionedItems->sum('total_price'), 2) }}</td>
                </tr>
            @endif

            {{-- Grand Total --}}
            <tr class="grand-total-row">
                <td colspan="5" style="text-align: right;">GRAND TOTAL:</td>
                <td class="text-right">{{ number_format($boq->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer-info">
        Generated on {{ now()->format('d/m/Y H:i') }} | Bill of Quantities - {{ $boq->project->project_name ?? '' }}
    </div>
</body>
</html>
