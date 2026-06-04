<?php
$document_id = \App\Classes\Utility::getLastId('AdvanceSalary')+1;
?>
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Staff</label>
            <select name="staff_id" id="input-employee-id" class="form-control" required>

                <option value="">Select Staff</option>
                @foreach ($staffs as $staff)
                    <option value="{{ $staff['id'] }}" {{ ( $staff['id'] == $object->staff_id) ? 'selected' : '' }}> {{ $staff['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Amount</label>
            <input type="text" class="form-control amount" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="Amount" required>
        </div>
        @php $isNewAdvance = !($object->id ?? null); @endphp
        <div class="form-group">
            <label for="input-monthly-deduction" class="control-label {{ $isNewAdvance ? 'required' : '' }}">Monthly Deduction Amount</label>
            <input type="number" step="0.01" min="1" class="form-control" id="input-monthly-deduction" name="monthly_deduction"
                   value="{{ old('monthly_deduction', $object->monthly_deduction ?? '') }}"
                   placeholder="Amount to deduct each payroll" {{ $isNewAdvance ? 'required' : '' }}>
            <small class="text-muted">How much to recover from payroll each month until the advance is fully repaid.</small>
        </div>
        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label for="input-start-month" class="control-label {{ $isNewAdvance ? 'required' : '' }}">Start Month</label>
                    <select name="start_month" id="input-start-month" class="form-control" {{ $isNewAdvance ? 'required' : '' }}>
                        <option value="">Select Month</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ (old('start_month', $object->start_month ?? date('n')) == $m) ? 'selected' : '' }}>
                                {{ date('F', mktime(0,0,0,$m,1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label for="input-start-year" class="control-label {{ $isNewAdvance ? 'required' : '' }}">Start Year</label>
                    <select name="start_year" id="input-start-year" class="form-control" {{ $isNewAdvance ? 'required' : '' }}>
                        <option value="">Select Year</option>
                        @foreach(range(date('Y')-1, date('Y')+2) as $y)
                            <option value="{{ $y }}" {{ (old('start_year', $object->start_year ?? date('Y')) == $y) ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="date" class="form-control" id="input-date" name="date"
                   value="{{ old('date', $object->date ? \Carbon\Carbon::parse($object->date)->format('Y-m-d') : date('Y-m-d')) }}">
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="document_number" value="ADVS/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="6">
                <input type="hidden" name="link" value="settings/advance_salaries/{{$document_id}}/6">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AdvanceSalary">Submit</button>
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
</script>
