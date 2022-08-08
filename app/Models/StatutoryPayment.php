<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StatutoryPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'sub_category_id', 'description','status','issue_date','due_date','amount','control_number','file'
    ];

    public static function getTotalPaymentBySubCategory($sub_category_id, $start_date, $end_date)
    {
        return StatutoryPayment::select([DB::raw("SUM(amount) as total_amount")])->Where('sub_category_id',$sub_category_id)->Where('status','APPROVED')->WhereBetween('issue_date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalPayment($start_date, $end_date)
    {
        return StatutoryPayment::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('issue_date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public function subCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(Category::class, SubCategory::class, 'category_id', 'id', 'sub_category_id');
    }

    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
