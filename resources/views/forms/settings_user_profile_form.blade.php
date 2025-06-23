
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="row">
            <input type="hidden" id="profile_image" name="profile_image" value="yes">

            <div class="form-group">
                <label class="control-label" for="chooseFile">Choose Profile</label>
                <input type="file" name="profile" class="form-control" id="chooseFile">
            </div>
{{--            @if(\App\Classes\Utility::isAdmin())--}}
{{--            <div class="col-sm-4">--}}
{{--                <div class="form-group">--}}
{{--                    <label for="example-nf-password">Password</label>--}}
{{--                    <input type="text" class="form-control" id="input-user-password" name="password" value="123456">--}}
{{--                </div>--}}
{{--            </div>--}}
{{--                @endif--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="User">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
