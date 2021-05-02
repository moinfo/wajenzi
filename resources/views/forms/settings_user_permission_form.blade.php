
<div class="block-content">
    <form  method="post"  autocomplete="off" action="{{route('hr_settings_users')}}">
        @csrf
        <?php
        use Illuminate\Support\Facades\DB;
        $group_permissions = [
            ['name'=>'MENU'],
            ['name'=>'SETTING'],
            ['name'=>'REPORT'],
            ['name'=>'CRUD']
        ];

        $user_id = (int)$_POST["user_id"];
        ?>
        <div class="row">
           <div class="col-sm-12">
               @foreach($group_permissions as $permission)
               <h6>{{$permission['name']}}</h6>
                   <?php
                   $type = $permission['name'];
                   $sub_permissions = \App\Models\Permission::Where('permission_type',$type)->get();
                   ?>
                   <ul class="list-unstyled">
                   @foreach($sub_permissions as $sub_permission)
                       <?php
                           $sub_permission_id = $sub_permission->id;
                           $staff_permission_id = \App\Models\UsersPermission::select([DB::raw("permission_id as permission_id")])->Where('user_id',$user_id)->Where('permission_id',$sub_permission_id)->get()->first()['permission_id'];
                           ?>
                       <li><input type="checkbox" name="permission_id[]" value="{{$sub_permission->id}}" {{ ( $sub_permission->id == $staff_permission_id) ? 'checked' : '' }}><span>&nbsp;{{$sub_permission->name}}</span></li>
                   @endforeach
                   </ul>
               @endforeach

           </div>
        </div>
        <div class="form-group">
            <input type="hidden" name="user_id" id="user_id" value="{{$user_id}}">
            <button type="submit" class="btn btn-alt-primary col" name="submit" value="User">Submit</button>
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
