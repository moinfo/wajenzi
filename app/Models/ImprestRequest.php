<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprestRequest extends Model
{
    use HasFactory;

    protected $table = 'imprest_requests';

    public $fillable = ['document_number','description','amount','status','create_by_id','expenses_sub_category_id','file','date','project_id'];

    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public function expenseSubCategory(){
        return $this->belongsTo(ExpensesSubCategory::class);
    }

    public function ImprestFromBeginning(){
        return $this->where('status', 'approved')->sum('amount');
    }

    public function project(){
        return $this->belongsTo(Project::class);
    }
}
