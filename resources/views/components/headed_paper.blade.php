<div class="class card-box">
    <div class="row" style="border-bottom: 3px solid gray">
        <div class="col-md-3 text-right">
            <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
        </div>
        <div class="col-md-6 text-center">
            <span class="text-center font-size-h3">{{settings('ORGANIZATION_NAME')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_1')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_2')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_PHONE_NUMBER')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span><br/>
        </div>
        <div class="col-md-3 text-right">
            <a href="{{route('reports')}}"   type="button" class="btn btn-sm btn-success"><i class="fa fa arrow-left"></i>Back</a>
        </div>
    </div>
</div>
