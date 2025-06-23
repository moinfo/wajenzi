@php
    $payroll_id = $object->id;
    $staffs = \App\Models\Staff::onlyStaffs();
@endphp
<div class="block-content">
    <div class="hide_print">Payroll Number : <b>{{$object->payroll_number}}</b></div>
    <table class="table table-condensed">
        <tbody>
        <tr>
            <td width="25%" class="txt-left">Benjamin Mkapa Pension Towers,<p>Azikiwe Street</p><p>DAR ES SALAAM</p><p>P.O. Box 1322</p><p>TANZANIA</p><p>Email:
                    dg@nssf.or.tz</p></td>
            <td width="50%" class="text-center">
                <h2>NATIONAL SOCIAL SECURITY FUND</h2>
                <img alt=" " width="180px" src="{{ asset('logo/nssf.png') }}">
            </td>
            <td width="25%" class="text-right">
                <p>NSSF/CONT/{{$payroll_id}}</p>
                <p>Phone:(255) (22) 2163400-19</p><p>Fax:(255) (22) 2200037</p><p>Mobile:
                    (255) (75) 6140140</p><p>https://www.nssf.or.tz</p>
            </td>
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
            <th class="txt-center">S/No</th>
            <th class="txt-center">Membership Number</th>
            <th class="txt-center">Name in Full</th>
            <th class="txt-center">Monthly Salary (Tsh.)</th>
            <th colspan="2" class="txt-center">Member’s Contribution (Tsh.)</th>
            <th colspan="2" class="txt-center">Employer’s Contribution (Tsh.)</th>
            <th class="txt-center">Total Contribution (Tsh.)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $total_basic_salary = 0;
            $total_gross_pay = 0;
            $total_employee_deducted_amount_pension = 0;
            $total_employer_deducted_amount_pension = 0;
            $sum = 0;
        @endphp
        @foreach($staffs as $staff)
            @php
            $staff_id = $staff->id;
            $membership_number = \App\Models\Staff::getStaffMembershipNumber($staff_id,2);
            $employee_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employee_deduction_amount') ?? 0;

            @endphp
            @if($employee_deducted_amount_pension != 0)
                @php
                    $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                    $total_basic_salary += $basic_salary;
                    $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                    $total_gross_pay += $gross_pay;
                    $total_employee_deducted_amount_pension += $employee_deducted_amount_pension;
                    $employer_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employer_deduction_amount') ?? 0;
                    $total_employer_deducted_amount_pension += $employer_deducted_amount_pension;
                    $total_contribution = $employer_deducted_amount_pension + $employee_deducted_amount_pension;
                    $sum += $total_contribution;
               @endphp
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$membership_number}}</td>
                    <td>{{$staff->name}}</td>
                    <td class="money text-right">{{number_format($gross_pay)}}</td>
                    <td class="money text-right" width="40">10%</td>
                    <td class="money text-right">{{number_format($employee_deducted_amount_pension)}}</td>
                    <td class="money text-right" width="40">10%</td>
                    <td class="money text-right">{{number_format($employer_deducted_amount_pension)}}</td>
                    <td class="money text-right">{{number_format($total_contribution)}}</td>
                </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="3" class="text-center text-strong">TOTAL</th>
            <th class="money text-right">{{number_format($total_gross_pay)}}</th>
            <th class="money text-right" colspan="2">{{number_format($total_employee_deducted_amount_pension)}}</th>
            <th class="money text-right" colspan="2">{{number_format($total_employer_deducted_amount_pension)}}</th>
            <th class="money text-right">{{number_format($sum)}}</th>
        </tr>
        </tfoot>
    </table>
</div>


