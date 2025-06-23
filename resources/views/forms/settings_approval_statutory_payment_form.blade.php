<div class="block-content">
    <form method="post" action="{{route('hr_settings_approvals')}}" enctype="multipart/form-data">
        @csrf
        <?php
        use App\Models\Approval;
        $document_id = $_POST['id'];
        $statutory_payments = \App\Models\StatutoryPayment::find($document_id)->first();
        $approvalStages = Approval::getApprovalStages($document_id);
        $nextApproval = Approval::getNextApproval($document_id);
        $approvalCompleted = Approval::isApprovalCompleted($document_id);
        $rejected = Approval::isRejected($document_id);
        ?>
        <table class="table table-bordered table-striped table-vcenter">
            <tbody>
                <tr>
                    <th width="40%">Statutory Payment</th>
                    <td>{{$statutory_payments->subCategory->name}}</td>
                </tr>
                <tr>
                    <th width="30%">Description</th>
                    <td>{{$statutory_payments->description}}</td>
                </tr>
                <tr>
                    <th width="30%">Amount</th>
                    <td>{{number_format($statutory_payments->amount)}}</td>
                </tr>
                <tr>
                    <th width="30%">Control Number</th>
                    <td>{{$statutory_payments->description}}</td>
                </tr>
                <tr>
                    <th width="30%">Issue Date</th>
                    <td>{{$statutory_payments->issue_date}}</td>
                </tr>
                <tr>
                    <th>Due Date</th>
                    <td>{{$statutory_payments->due_date}}</td>
                </tr>
                <tr>
                    <th width="30%">Uploaded File</th>
                    <td width="70%" class="bold"><a
                            href="{{ url($statutory_payments->file) }}" target="_blank">View</a>
                    </td>
                </tr>
                <tr>
                    <th width="30%">status</th>
                    @if($statutory_payments->status == 'PENDING')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-warning badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'APPROVED')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-primary badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'REJECTED')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-danger badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'PAID')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-primary badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'COMPLETED')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-success badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @else
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-secondary badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @endif
                </tr>
            </tbody>
        </table>
        <div class="row">
            <?php
            $get_user_group_id = \App\Models\AssignUserGroup::getAssignUserGroup(Auth::user()->id);
            foreach ($get_user_group_id as $index => $item) {
                $arr[] = $item->user_group_id;
            }
            ?>
            @if($nextApproval)
                @if($rejected)
                    <div class="col-md-9">
                        <span class='pull-right'>This Statutory Payment was rejected <i class='text-light'>Comment: {{$rejected->comments}}</i></span>
                    </div>
                    <div class="col-md-3">
                    </div>
                @else
                    @if(!in_array($nextApproval->user_group_id,$arr))
                    <div class="col-md-12">
                        <span class='pull-left'><i class='fa fa-clock'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Waiting {{$nextApproval->user_group_name}} for Approval</span>
                    </div>
                        @else
                    <div class="col-md-12">
                        <input type="hidden" name="status" id="status" value="APPROVED">
                        <input type="hidden" name="approval_document_types_id" id="approval_document_type_id" value="{{$nextApproval->document_id}}">
                        <input type="hidden" name="user_id" id="user_id" value="{{Auth::user()->id }}">
                        <input type="hidden" name="approval_level_id" id="approval_level_id" value="{{$nextApproval->order_id ?? null}}">
                        <input type="hidden" name="user_group_id" id="user_group_id" value="{{$nextApproval->user_group_id ?? null}}">
                        <input type="hidden" name="document_id" id="document_id" value="{{$document_id}}">
                        <input type="hidden" name="approval_date" id="approval_date" value="<?=date('Y-m-d H:i:s')?>">
                        <br/>
                        <div class="form-group row">
                            <label for="example-text-input" class="col-md-2 col-form-label">Comments</label>
                            <div class="col-md-10">
                                <textarea class="form-control" type="text" id="comments" name="comments" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="btn-group pull-right">
                                <button type="submit" class="btn btn-alt-primary btn-sm" name="approveItem" value="StatutoryPayment">Approve now</button>
                                <button type="submit" class="btn btn-alt-danger btn-sm" name="rejectItem" value="StatutoryPayment">Reject</button>
                            </div>
                        </div>
                    </div>
                    @endif
                @endif
            @elseif($approvalCompleted)
                <div class="col-md-9">
                    <span class='text-primary'><i class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> Statutory Payment Approved</span>
                </div>
                <div class="col-md-3">

                </div>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


