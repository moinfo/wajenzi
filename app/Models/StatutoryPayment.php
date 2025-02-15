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
        return StatutoryInvoicePayment::
            select([DB::raw("SUM(statutory_invoice_payments.amount) as total_amount")])->
                join('invoices','invoices.id','=','statutory_invoice_payments.invoice_id')->
                join('products','products.id','=','invoices.product_id')->
            Where('products.sub_category_id',$sub_category_id)->Where('statutory_invoice_payments.status','APPROVED')->
            WhereBetween('statutory_invoice_payments.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalPaymentByCategory($category_id, $start_date, $end_date)
    {
        return StatutoryInvoicePayment::
            select([DB::raw("SUM(statutory_invoice_payments.amount) as total_amount")])->
                join('invoices','invoices.id','=','statutory_invoice_payments.invoice_id')->
                join('products','products.id','=','invoices.product_id')->
            join('sub_categories','sub_categories.id','=','products.sub_category_id')->
            join('categories','categories.id','=','sub_categories.category_id')->
            Where('sub_categories.category_id',$category_id)->Where('statutory_invoice_payments.status','APPROVED')->
            WhereBetween('statutory_invoice_payments.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalPaymentByCategoryByDate($start_date, $end_date)
    {
        return StatutoryInvoicePayment::
            select([DB::raw("SUM(statutory_invoice_payments.amount) as total_amount")])->
                join('invoices','invoices.id','=','statutory_invoice_payments.invoice_id')->
                join('products','products.id','=','invoices.product_id')->
                join('sub_categories','sub_categories.id','=','products.sub_category_id')->
                join('categories','categories.id','=','sub_categories.category_id')->
            Where('statutory_invoice_payments.status','APPROVED')->
            WhereBetween('statutory_invoice_payments.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalPayment($start_date, $end_date)
    {
        return StatutoryInvoicePayment::
            select([DB::raw("SUM(statutory_invoice_payments.amount) as total_amount")])->
            join('invoices','invoices.id','=','statutory_invoice_payments.invoice_id')->
            join('products','products.id','=','invoices.product_id')->Where('statutory_invoice_payments.status','APPROVED')->
            WhereBetween('statutory_invoice_payments.date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function countUnapproved()
    {
        return count(StatutoryInvoicePayment::where('status','!=','APPROVED')->where('status','!=','REJECTED')->get());
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
