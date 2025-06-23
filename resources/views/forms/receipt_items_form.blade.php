<div class="table-responsive">
    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
        <thead>
        <tr>
            <th class="text-center" style="width: 100px;">#</th>
            <th>Item Name</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $total_amount = 0;
        ?>
        @foreach($object->items as $receipt_item)
            <?php
            $amount = $receipt_item['amount'];
            $total_amount += $amount;
            ?>
            <tr id="sale-tr-{{$receipt_item['id']}}">
                <td class="text-center">
                    {{$loop->iteration}}
                </td>
                <td class="font-w600">{{ $receipt_item['description'] }}</td>
                <td class="text-right">{{ number_format($receipt_item['amount']/(($receipt_item['qty'] == 0) ? 1 : $receipt_item['qty'])) }}</td>
                <td class="text-right">{{ number_format($receipt_item['qty']) }}</td>
                <td class="text-right">{{ number_format($receipt_item['amount']) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4"></td>
            <td class="text-right">{{ number_format($total_amount) }}</td>
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

