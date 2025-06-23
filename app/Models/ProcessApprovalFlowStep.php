<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessApprovalFlowStep extends Model
{
    use HasFactory;

    protected $fillable = ['process_approval_flow_id','role_id','order','permission','action','active'];

    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }
    public function process_approval_flow(){
        return $this->belongsTo(ProcessApprovalFlow::class,'process_approval_flow_id');
    }

    public function approvable(){
        return $this->morphTo();
    }

    public function next(){
        return $this->hasOne(ProcessApprovalFlowStep::class,'id','next_id');
    }

    public function previous(){
        return $this->hasOne(ProcessApprovalFlowStep::class,'id','previous_id');
    }

    public function getNextStep(){
        return $this->next;
    }

    public function getPreviousStep(){
        return $this->previous;
    }

    public function getApprovable(){
        return $this->approvable;
    }

    public function getFlow(){
        return $this->process_approval_flow;
    }

    public function getFlowName(){
        return $this->process_approval_flow->name;
    }

    public function getFlowId(){
        return $this->process_approval_flow->id;
    }

    public function getFlowType(){
        return $this->process_approval_flow->approvable_type;
    }

    public function getFlowTypeId(){
        return $this->process_approval_flow->approvable_id;
    }

    public function getStepName(){
        return $this->name;
    }
}
