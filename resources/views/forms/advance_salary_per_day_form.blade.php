<div class="block-content">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-vcenter">
            <thead>
            <tr>
                <td>#</td>
                <td>Staff Name</td>
                <td>Amount</td>
            </tr>
            </thead>
            <tbody>
            <?php
            $sum = 0;
            ?>
            @foreach($object as $list)
                <?php
                $expenses = $list->amount;
                $sum += $expenses;
                ?>
                <tr>
                    <td>{{$loop->iteration}}</td>
                    <td>{{$list->staff->name}}</td>
                    <td class="text-right">{{number_format($list->amount)}}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr class="font-weight-bold">
                <td class="text-right" colspan="2">Total Advance Salary</td>
                <td class="text-right">{{number_format($sum)}}</td>
            </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
    $("input").on("change", function () {
        this.setAttribute(
            "data-date",
            moment(this.value, "YYYY-MM-DD")
                .format(this.getAttribute("data-date-format"))
        )
    }).trigger("change")
</script>


