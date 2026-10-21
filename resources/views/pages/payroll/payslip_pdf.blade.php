<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $data['employee']['name'] }} - {{ $data['payroll']['month_name'] }} {{ $data['payroll']['year'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; margin: 24px; }
        .header { width: 100%; border-bottom: 2px solid #193340; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: top; }
        .company { font-size: 15px; font-weight: bold; color: #193340; margin: 0 0 4px; }
        .meta { color: #6b7280; font-size: 10px; line-height: 1.5; }
        .label { text-align: right; }
        .label small { color: #6b7280; }
        .label strong { display: block; font-size: 20px; color: #193340; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid td { width: 50%; padding: 5px 8px; border: 1px solid #e5e7eb; }
        .grid span { display: block; color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .grid strong { font-size: 11px; }
        .panels { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .panels > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        .panel-title { background: #193340; color: #fff; padding: 6px 8px; font-weight: bold; }
        .lines { width: 100%; border-collapse: collapse; }
        .lines td, .lines th { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .lines .money { text-align: right; }
        .lines tfoot th, .lines tfoot td { background: #f3f4f6; font-weight: bold; }
        .net { margin-top: 14px; width: 100%; background: #3BA154; color: #fff; border-collapse: collapse; }
        .net td { padding: 10px 12px; }
        .net .amount { font-size: 18px; font-weight: bold; }
        .net .acct { text-align: right; font-size: 10px; }
        .footer { margin-top: 22px; border-top: 1px solid #e5e7eb; padding-top: 8px; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($v) => number_format((float) $v, 2);
    $income = collect([['name' => 'BASIC SALARY', 'value' => $data['basic_salary']]])
        ->concat(collect($data['allowances'])->map(fn ($a) => ['name' => $a['name'], 'value' => $a['amount']]));
    $deductionLines = collect($data['deductions'])->map(fn ($d) => ['name' => $d['name'], 'value' => $d['amount']])
        ->prepend(['name' => $data['payee']['name'], 'value' => $data['payee']['amount']]);
    if ((float) $data['advance_salary'] > 0) {
        $deductionLines->push(['name' => 'ADVANCE SALARY', 'value' => $data['advance_salary']]);
    }
    if ((float) $data['loan_deduction'] > 0) {
        $deductionLines->push(['name' => 'LOAN DEDUCTION', 'value' => $data['loan_deduction']]);
    }
    $bank = $data['bank']['name'] ?? null;
    $account = $data['bank']['account_number'] ?? null;
@endphp
    <table class="header">
        <tr>
            <td>
                <p class="company">{{ settings('ORGANIZATION_NAME') }}</p>
                <div class="meta">
                    {{ settings('COMPANY_ADDRESS_LINE_1') }}<br>
                    {{ settings('COMPANY_ADDRESS_LINE_2') }}<br>
                    {{ settings('COMPANY_PHONE_NUMBER') }} &middot; {{ settings('COMPANY_EMAIL_ADDRESS') }}
                </div>
            </td>
            <td class="label">
                <small>Approved Payroll</small>
                <strong>Payslip</strong>
                <small>{{ $data['payroll']['month_name'] }} {{ $data['payroll']['year'] }}</small>
            </td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td><span>Payroll Number</span><strong>{{ $data['payroll']['payroll_number'] ?? '-' }}</strong></td>
            <td><span>Payroll Month</span><strong>{{ $data['payroll']['month_name'] }} {{ $data['payroll']['year'] }}</strong></td>
        </tr>
        <tr>
            <td><span>Employee Number</span><strong>HRM/LE/PO-{{ $data['employee']['employee_number'] ?? '-' }}</strong></td>
            <td><span>Employee Name</span><strong>{{ $data['employee']['name'] ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td><span>Designation</span><strong>{{ $data['employee']['designation'] ?? '-' }}</strong></td>
            <td><span>Bank / Account</span><strong>{{ $bank ?? '-' }} / {{ $account ?? '-' }}</strong></td>
        </tr>
    </table>

    <table class="panels">
        <tr>
            <td>
                <div class="panel-title">Employee Income</div>
                <table class="lines">
                    <tbody>
                    @foreach($income as $line)
                        <tr><td>{{ $line['name'] }}</td><td class="money">{{ $fmt($line['value']) }}</td></tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th>Gross Salary</th><td class="money">{{ $fmt($data['gross_salary']) }}</td></tr></tfoot>
                </table>
            </td>
            <td>
                <div class="panel-title">Deductions</div>
                <table class="lines">
                    <tbody>
                    @foreach($deductionLines as $line)
                        <tr><td>{{ $line['name'] }}</td><td class="money">{{ $fmt($line['value']) }}</td></tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th>Total Deductions</th><td class="money">{{ $fmt($data['total_deductions']) }}</td></tr></tfoot>
                </table>
            </td>
        </tr>
    </table>

    <table class="net">
        <tr>
            <td>Net Salary<br><span class="amount">{{ $fmt($data['net_salary']) }}</span></td>
            <td class="acct">Payment Account<br><b>{{ $bank ?? 'Bank not set' }} / {{ $account ?? 'Account not set' }}</b></td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated payslip and does not require a signature. &mdash; {{ settings('ORGANIZATION_NAME') }}
    </div>
</body>
</html>
