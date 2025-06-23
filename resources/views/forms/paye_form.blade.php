@php
    $payroll_id = $object->id;
    $staffs = \App\Models\Staff::onlyStaffs();
@endphp
<div class="block-content">
    <div class="hide_print">Payroll Number : <b>{{$object->payroll_number}}</b></div>
    <table class="table table-condensed">
        <tbody>
        <tr>
            <td width="25%" class="text-left"><p></p>COMMISSIONER FOR DOMESTIC REVENUE
                PWANI</td>
            <td width="50%" class="text-center">
                <h2>PAY AS YOU EARN</h2>
                <img alt=" " width="150px" src="{{ asset('logo/tra.png') }}">
            </td>
            <td width="25%" class="text-right">PAYE/CONT/{{$payroll_id}}</td>
        </tr>
        <tr>
            <td colspan="3" class="text-center">
                <h5 class="text-capitalize"><u>CONTRIBUTIONS FOR THE MONTH OF {{date('F',strtotime($object->year.'-'.$object->month.'-'.'01')).' - '.$object->year}}</u></h5>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p>Name of Employer &nbsp;&nbsp;&nbsp; <span class="text-strong">{{settings('ORGANIZATION_NAME')}}</span></p>
                <p>Employer’s Registration Number &nbsp;&nbsp;&nbsp; <span class="text-strong">113 - 882 - 384</span></p>
            </td>

        </tr>

        </tbody>
    </table>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>S/No</th>

            <th>Name in Full</th>
            <th>Employee Basic Salary</th>
            <th>Taxible Amount</th>
            <th>TAX (PAYE) (Tsh.)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $total_basic_salary = 0;
            $total_taxable = 0;
            $total_employee_deducted_amount_payee = 0;
        @endphp
        @foreach($staffs as $staff)
            @php
            $staff_id = $staff->id;
                $employee_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employee_deduction_amount') ?? 0;


            @endphp
            @if($employee_deducted_amount_payee != 0)
                @php
                    $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                    $total_basic_salary += $basic_salary;
                    $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;
                    $total_taxable += $taxable;
                    $total_employee_deducted_amount_payee += $employee_deducted_amount_payee;
               @endphp
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$staff->name}}</td>
                    <td class="money text-right">{{number_format($basic_salary)}}</td>
                    <td class="money text-right">{{number_format($taxable)}}</td>
                    <td class="money text-right">{{number_format($employee_deducted_amount_payee)}}</td>
                </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2" class="text-center text-strong">TOTAL</th>
            <th class="money text-right">{{number_format($total_basic_salary)}}</th>
            <th class="money text-right">{{number_format($total_taxable)}}</th>
            <th class="money text-right">{{number_format($total_employee_deducted_amount_payee)}}</th>
        </tr>
        </tfoot>
    </table>
</div>


