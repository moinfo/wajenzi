<div class="table-responsive">
    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
        <thead>
        <tr>
            <th class="text-center" style="width: 100px;">#</th>
            <th>Date</th>
            <th>EFD Name</th>
            <th>Turnover</th>
            <th>NET (A+B+C)</th>
            <th>Tax</th>
            <th>Turnover (EX + SR)</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $total_amount = 0;
        $total_net = 0;
        $total_tax = 0;
        $total_turn_over = 0;
        ?>
        @foreach($object as $sale)
            <?php
            $amount = $sale->amount;
            $total_amount += $amount;
            $net = $sale->net;
            $total_net += $net;
            $tax = $sale->tax;
            $total_tax += $tax;
            $turn_over = $sale->turn_over;
            $total_turn_over += $turn_over;
            ?>
            <tr id="sale-tr-{{$sale->id}}">
                <td class="text-center">
                    {{$loop->index + 1}}
                </td>
                <td class="font-w600">{{ $sale->date }}</td>
                <td class="font-w600">{{ $sale->efd->name }}</td>
                <td class="text-right">{{ number_format($sale->amount, 2) }}</td>
                <td class="text-right">{{ number_format($sale->net, 2) }}</td>
                <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                <td class="text-right">{{ number_format($sale->turn_over, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3"></td>
            <td class="text-right">{{ number_format($total_amount, 2) }}</td>
            <td class="text-right">{{ number_format($total_net, 2) }}</td>
            <td class="text-right">{{ number_format($total_tax, 2) }}</td>
            <td class="text-right">{{ number_format($total_turn_over, 2) }}</td>
        </tr>
        </tfoot>
    </table>
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
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>

