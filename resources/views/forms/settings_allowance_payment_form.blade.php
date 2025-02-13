<?php
//$month = date('m');
//$allowance_subscription_all_staff = \App\Models\Staff::getAllStaffAllowance($month);

?>
    <div class="block-content">
        <form  method="post"  autocomplete="off">
            @csrf
            <div class="form-group">
                <label for="example-nf-amount">Monthly</label>
                <select name="month" id="month" class="form-control" required>
                    <option value="">Select Month</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
            </div>
            <div class="form-group">
                <label for="example-nf-cost">Amount</label>
                <select name="amount" id="amount" class="form-control" readonly required>
                    <option></option>

                </select>
            </div>
{{--            <div class="form-group">--}}
{{--                <label for="example-nf-amount">Amount</label>--}}
{{--                <input type="text" class="form-control amount" id="input-amount" name="amount" value="{{ $object->amount ?? $allowance_subscription_all_staff }}"  readonly>--}}
{{--            </div>--}}
            @if(\App\Classes\Utility::isAdmin())
                <div class="form-group">
                    <label for="example-nf-date" class="control-label required">Date</label>
                    <input type="text" class="form-control datepicker"  id="input-date" name="date"
                           value="{{ $object->date ?? date('Y-m-d') }}" required>
                </div>
            @else
                <div class="form-group">
                    <label for="example-nf-date" class="control-label required">Date</label>
                    <input type="text" class="form-control "  id="input-date" name="date"
                           value="{{ $object->date ?? date('Y-m-d') }}" readonly>
                </div>
            @endif
            <div class="form-group">
                @if($object->id ?? null)
                    <input type="hidden" name="id" value="{{$object->id }}">
                    <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
                @else
                    <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AllowancePayment">Submit</button>
                @endif
            </div>
        </form>
    </div>
    <script>
        $("input.amount").each((i,ele)=>{
            let clone=$(ele).clone(false)
            clone.attr("type","text")
            let ele1=$(ele)
            clone.val(Number(ele1.val()).toLocaleString("en"))
            $(ele).after(clone)
            $(ele).hide()
            clone.mouseenter(()=>{

                ele1.show()
                clone.hide()
            })
            setInterval(()=>{
                let newv=Number(ele1.val()).toLocaleString("en")
                if(clone.val()!=newv){
                    clone.val(newv)
                }
            },10)

            $(ele).mouseleave(()=>{
                $(clone).show()
                $(ele1).hide()
            })


        });
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });

        $("#month").change(function () {
            var month = $(this).val();
            var url = '/allowance_cost';
            $.ajax({
                url: url,
                type: 'post',
                data: {month: month, _token: csrf_token},
                dataType: 'json',
                success: function (response) {
                    var len = response.length;
                    $("#amount").empty();
                    for (var i = 0; i < len; i++) {
                        // var id = response[i]['id'];
                        var amount = response[i]['amount'];

                        $("#amount").append("<option value='" + amount + "'>" + amount + "</option>");

                    }
                }
            });
        });
    </script>
