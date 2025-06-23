@php
    $payroll_id = $object->id;
    $staffs = \App\Models\Staff::onlyStaffs();
@endphp
<div class="block-content">
    <div class="hide_print">Payroll Number : <b>{{$object->payroll_number}}</b></div>
    <table class="table table-condensed">
        <tbody>
        <tr>
            <td width="25%" class="text-left"><p></p></td>
            <td width="50%" class="text-center">
                <h2>SKILL & DEVELOPMENT LEVY</h2>
                <img alt=" " width="150px" src="{{ asset('logo/tra.png') }}">
            </td>
            <td width="25%" class="text-right">SDL/CONT/{{$payroll_id}}</td>
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
            <th>Employee Gross Salary</th>
            <th colspan="2">Deduction Amount (Tsh.)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $total_basic_salary = 0;
            $total_gross_pay = 0;
            $total_employer_deducted_amount_sdl = 0;
        @endphp
        @foreach($staffs as $staff)
            @php
            $staff_id = $staff->id;
            $employer_deducted_amount_sdl = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,5,'employer_deduction_amount') ?? 0;



            @endphp
            @if($employer_deducted_amount_sdl != 0)
                @php
                    $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                    $total_basic_salary += $basic_salary;
                    $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                    $total_gross_pay += $gross_pay;
                   $total_employer_deducted_amount_sdl += $employer_deducted_amount_sdl;
               @endphp
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$staff->name}}</td>
                    <td class="money text-right">{{number_format($gross_pay)}}</td>
                    <td class="money text-right" width="40">4%</td>
                    <td class="money text-right">{{number_format($employer_deducted_amount_sdl)}}</td>
                </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="text-center text-strong">TOTAL</th>
            <th class="money text-right">{{number_format($total_employer_deducted_amount_sdl)}}</th>
        </tr>
        </tfoot>
    </table>
</div>


