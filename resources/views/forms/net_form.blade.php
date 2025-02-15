@php
    $payroll_id = $object->id;
    $staffs = \App\Models\Staff::onlyStaffs();
@endphp
<div class="block-content">
    <div class="hide_print">Payroll Number : <b>{{$object->payroll_number}}</b></div>
    <table class="table table-condensed">
        <tbody>
        <tr>
            <td width="25%" class="text-left">
                <img class="img img-responsive" src="{{ asset('media/logo/wajenzilogo.png') }}" height="100" width="100">
            </td>
            <td width="50%" class="text-center">
                <h2>NET SALARY</h2>

            </td>
            <td width="25%" class="text-right">NETS/CONT/{{$payroll_id}}</td>
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
            <th>Employee Gross Salary</th>
            <th>NET SALARY (Tsh.)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $total_basic_salary = 0;
            $total_gross_pay = 0;
            $total_net = 0;
        @endphp
        @foreach($staffs as $staff)
            @php
            $staff_id = $staff->id;
            $net = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;


            @endphp
            @if($net != 0)
                @php
                    $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                    $total_basic_salary += $basic_salary;
                    $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                    $total_gross_pay += $gross_pay;
                    $total_net += $net;
               @endphp
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$staff->name}}</td>
                    <td class="money text-right">{{number_format($basic_salary)}}</td>
                    <td class="money text-right">{{number_format($gross_pay)}}</td>
                    <td class="money text-right">{{number_format($net)}}</td>
                </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2" class="txt-center txt-strong">TOTAL</th>
            <th class="money text-right">{{number_format($total_basic_salary)}}</th>
            <th class="money text-right">{{number_format($total_gross_pay)}}</th>
            <th class="money text-right">{{number_format($total_net)}}</th>
        </tr>
        </tfoot>
    </table>
</div>


