
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <?php
        use Illuminate\Support\Facades\DB;$group_permissions = [
            ['name'=>'MENU'],
            ['name'=>'SETTING'],
            ['name'=>'REPORT'],
            ['name'=>'CRUD']
        ];

        $user_id = (int)$_POST["user_id"];
        foreach ($permissions as $index => $permission) {
            $type = $permission['name'];
            $permissions = \App\Models\Permission::Where('permission_type',$type)->get();
        }
        //App\Models\SupplierReceiving::Where('date',$date)->Where('supplier_id',$id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();

        ?>
        <div class="row">
           <div class="col-sm-12">
               @foreach($group_permissions as $permission)
               <h4>{{$permission['name']}}</h4>
                   <?php
                   $type = $permission['name'];
                   $sub_permissions = \App\Models\Permission::Where('permission_type',$type)->join('users_permissions','users_permissions.permission_id','=','permissions.id','left')->get();
                   ?>
                   <ul>
                   @foreach($sub_permissions as $sub_permission)
                       <?php
                           $sub_permission_id = $sub_permission->id;
                           $staff_permission_id = \App\Models\UsersPermission::select([DB::raw("permission_id as permission_id")])->Where('user_id',$user_id)->Where('permission_id',$sub_permission_id)->get()->first()['permission_id'];
                           ?>
                       <li><input type="checkbox" name="permission_id[]" {{ ( $sub_permission->id == $staff_permission_id) ? 'checked' : '' }}><span>&nbsp;{{$sub_permission->name}}</span></li>
                   @endforeach
                   </ul>
               @endforeach

           </div>
        </div>
        <div class="form-group">
            @if($object->user_id ?? null)
                <input type="hidden" name="user_id" value="{{$object->user_id }}">
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
