<div class="block-content">
    <h4>Approvals</h4>
    <form method="post"  enctype="multipart/form-data">
        @csrf
        <?php
        use App\Models\Approval;
        $statutory_payments = \App\Models\StatutoryPayment::find($_POST['id'])->first();
        $approvalStages = Approval::getApprovalStages($_POST['id']);
        $nextApproval = Approval::getNextApproval($_POST['id']);
        $approvalCompleted = Approval::isApprovalCompleted($_POST['id']);
        $rejected = Approval::isRejected($_POST['id']);
//        dump($nextApproval);
//        dump($approvalStages);
//        dump($approvalCompleted);
//        dump($rejected);
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
                    @if($statutory_payments->status == 'pending')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-warning badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'approved')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-primary badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'rejected')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-danger badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'paid')
                        <td width="70%" class="bold text-capitalize"
                            style="font-size: 16px!important">
                            <div
                                class="badge badge-primary badge-pill">{{ $statutory_payments->status}}</div>
                        </td>
                    @elseif($statutory_payments->status == 'completed')
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
            <?php //dump($rejected);  ?>
            {{--                                    @foreach ($nextApprovals as $nextApproval)--}}

            @if($nextApproval)
                @if($rejected)
                    <div class="col-md-9">
                        <span class='pull-right'>This Statutory Payment was rejected <i class='text-light'>Comment: {{$rejected->comments}}</i></span>
                    </div>
                    <div class="col-md-3">
                        {{--                                                <div class="btn-group pull-right">--}}
                        {{--                                                    <button type='button' class='btn btn-success' id='btn-approve' data-toggle="modal" data-target=".approval"><i--}}
                        {{--                                                            class='fa fa-check' >&nbsp;</i>Approve now--}}
                        {{--                                                    </button>--}}
                        {{--                                                    <button type='button' data-toggle="modal" data-target=".reject" class='btn btn-danger' id='btn-approve'><i--}}
                        {{--                                                            class='fa fa-check'>&nbsp;</i>Reject--}}
                        {{--                                                    </button>--}}
                        {{--                                                </div>--}}
                    </div>
                @else
                    <div class="col-md-12">
                        <span class='pull-left'><i class='fa fa-clock'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Waiting {{$nextApproval->user_group_name}} for Approval</span>
                    </div>
                    <div class="col-md-12">
                        <div class="btn-group pull-right">
                            <button type='button' class='btn btn-success btn-sm' id='btn-approve' data-toggle="modal" data-target=".approval"><i
                                    class='fa fa-check' >&nbsp;</i>Approve now
                            </button>
                            <button type='button' data-toggle="modal" data-target=".reject" class='btn btn-danger btn-sm' id='btn-approve'><i
                                    class='fa fa-check'>&nbsp;</i>Reject
                            </button>
                        </div>
                    </div>
                @endif

            @elseif($approvalCompleted)
                <div class="col-md-9">
                    <span class='text-primary'><i class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> Statutory Payment Approved</span>
                </div>
                <div class="col-md-3">

                </div>

                {{--                                            @elseif($nextApproval->status == 'rejected')--}}
                {{--                                            <div class="col-md-9">--}}
                {{--                                                <div>You rejected this request!&nbsp;<i>You can still re-approve it as {{$next_approval->user_group_name}}</i>--}}

                {{--                                                </div>--}}
                {{--                                            </div>--}}
                {{--                                            <div class="col-md-3">--}}
                {{--                                                <button type='button' class='btn btn-danger' title='Discard this document and let the requester create a new one!' data-toggle='tooltip'>--}}
                {{--                                                    <i class='fa fa-times'>&nbsp;</i>Discard--}}
                {{--                                                </button>--}}
                {{--                                            </div>--}}
            @endif
            {{--                                    @endforeach--}}


        </div>
{{--        <div class="form-group">--}}
{{--            @if($object->id ?? null)--}}
{{--                <input type="hidden" name="id" value="{{$object->id }}">--}}
{{--                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update--}}
{{--                </button>--}}
{{--            @else--}}
{{--                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Expense">Submit</button>--}}
{{--            @endif--}}
{{--        </div>--}}
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


