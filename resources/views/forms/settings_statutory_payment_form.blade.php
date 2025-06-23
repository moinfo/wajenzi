<?php
$document_id = \App\Classes\Utility::getLastId('StatutoryPayment')+1;
?>
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        @if(\App\Classes\Utility::isAdmin())
            <div class="form-group">
                <label for="example-nf-date" class="control-label required">Issues Date</label>
                <input type="text" class="form-control datepicker"  id="input-issue-date" name="issue_date"
                       value="{{ $object->issue_date ?? date('Y-m-d') }}" required>
            </div>
        @else
            <div class="form-group">
                <label for="example-nf-date" class="control-label required">Issues Date</label>
                <input type="text" class="form-control datepicker"  id="input-issue-date" name="issue_date"
                       value="{{ $object->issue_date ?? date('Y-m-d') }}" readonly>
            </div>
        @endif
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Sub Category</label>
            <select name="sub_category_id" id="input-sub-category-id" class="form-control" required>
                <option value="">Select Sub Category</option>
                @foreach ($sub_categories as $sub_category)
                    <option value="{{ $sub_category->id }}" {{ ( $sub_category->id == $object->sub_category_id) ? 'selected' : '' }}> {{ $sub_category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-cost">Billing Cycle</label>
            <select name="billing_cycle" id="billing_cycle" class="form-control" required>
                <option></option>

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-cost">Amount</label>
            <select name="amount" id="amount" class="form-control" required>
                <option></option>

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Asset</label>
            <select name="asset_id" id="asset_id" class="form-control" required>
                <option value="">Select Asset</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}" {{ ( $asset->id == $object->asset_id) ? 'selected' : '' }}> {{ $asset->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-cost">Asset Property</label>
            <select name="asset_property_id" id="asset_property_id" class="form-control" required>
                <option></option>

            </select>
        </div>
        <div class="form-group" >
            <label for="example-nf-description" class="control-label required">Description</label>
            <input type="text" class="form-control" id="input-description" name="description"
                   value="{{ $object->description ?? '' }}" placeholder="Description" required>
        </div>
{{--        <div class="form-group">--}}
{{--            <label for="example-nf-amount" class="control-label required">Amount</label>--}}
{{--            <input type="number" step=".01" class="form-control amount" id="input-amount" name="amount"--}}
{{--                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>--}}
{{--        </div>--}}
        <div class="form-group">
            <label for="example-nf-amount">Control Number</label>
            <input type="number" class="form-control" id="input-control_number" name="control_number"
                   value="{{ $object->control_number ?? '' }}" placeholder="Control Number">
        </div>
        @if(\App\Classes\Utility::isAdmin())
            <div class="form-group">
                <label for="example-nf-date" class="control-label required">Due Date</label>
                <input type="text" class="form-control "  id="input-due_date" name="due_date"
                       value="{{ $object->due_date ?? date('Y-m-d') }}" required>
            </div>
        @else
            <div class="form-group">
                <label for="example-nf-date" class="control-label required">Due Date</label>
                <input type="text" class="form-control datepicker"  id="input-due_date" name="due_date"
                       value="{{ $object->due_date ?? date('Y-m-d') }}" readonly>
            </div>
        @endif
        <div class="form-group">
            <label class="control-label" for="chooseFile">Choose file</label>
            <input type="file" name="file" class="form-control" id="chooseFile">
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <input type="hidden" name="document_number" value="STPT/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="link" value="settings/statutory_payments/{{$document_id}}/1">
                <input type="hidden" name="document_type_id" value="1">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="StatutoryPayment">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $("#input-sub-category-id").change(function () {
        var sub_category_id = $(this).val();
        var startdate = $('#input-issue-date').val();

// alert(month_add);
        var url = '/sub_category_list';
        $.ajax({
            url: url,
            type: 'post',
            data: {sub_category_id: sub_category_id, _token: csrf_token},
            dataType: 'json',
            success: function (response) {

                var len = response.length;
                $("#billing_cycle").empty();
                $("#amount").empty();
                for (var i = 0; i < len; i++) {
                    var id = response[i]['id'];
                    var price = response[i]['price'];
                    var billing_cycle = response[i]['billing_cycle'];
                    var billing_cycle_name = response[i]['billing_cycle_name'];
                    var month_add = response[i]['billing_cycle'];
                    var newDate = moment(startdate, "YYYY-MM-DD").add(month_add, 'months').format('YYYY-MM-DD');
                    // alert(month_add);

                    $("#amount").append("<option value='" + price + "'>" + price + "</option>");
                    $("#billing_cycle").append("<option value='" + billing_cycle + "'>" + billing_cycle_name + "</option>");
                    $('#input-due_date').val(newDate);
                }
            }
        });
    });



        // $('#total').val($('#number_of_months').val() * $( "#price option:selected" ).text());




    $("#asset_id").change(function () {
        var asset_id = $(this).val();
        var url = '/list_asset_properties';
        $.ajax({
            url: url,
            type: 'post',
            data: {asset_id: asset_id, _token: csrf_token},
            dataType: 'json',
            success: function (response) {
                var len = response.length;
                $("#asset_property_id").empty();
                for (var i = 0; i < len; i++) {
                    var id = response[i]['id'];
                    var name = response[i]['name'];

                    $("#asset_property_id").append("<option value='" + id + "'>" + name + "</option>");

                }
            }
        });
    });
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
    $("input").on("change", function () {
        this.setAttribute(
            "data-date",
            moment(this.value, "YYYY-MM-DD")
                .format(this.getAttribute("data-date-format"))
        )
    }).trigger("change")
</script>
<script>
    // window.Echo.private('details.' + window.Laravel.user)
    //     .listen('Approved', (e) => {
    //         console.log(e);
    //     });

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


