<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class Sale extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'efd_id', 'amount', 'date', 'net', 'tax', 'turn_over', 'file', 'status', 'efd_number', 'create_by_id','document_number'];


    /**
     * Logic executed when the approval process is completed.
     *
     * This method handles the state transitions based on your application's status values:
     * 'CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {

        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }
    public function efd(){
        return $this->belongsTo(Efd::class);
    }

    public function user(){
        return $this->belongsTo(User::class, 'create_by_id');
    }

    public static function getLastEfdNumber($efd_id){
        return Sale::where('efd_id',$efd_id)->orderBy('id','DESC')->get()->first()['efd_number'] ?? 0;
    }

    public function getAll($start_date,$end_date,$efd_id = null,$status = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->select('sales.*','efds.name as efd')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date);

        if($status != null){
            $sales->where('status','=',$status);
        } if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->get();
    }

    public static function getTotalNet($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.net');
    }
    public static function getTotalTurnover($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.amount');
    }

    public static function getTotalSale($start_date,$end_date,$efd_id = null){
       return self::getTotalTurnover($start_date,$end_date,$efd_id = null) - self::getTotalExempt($start_date,$end_date,$efd_id = null);

    }

    public static function getTotalSaleVatExcl($start_date,$end_date,$efd_id = null){
        return self::getTotalSale($start_date,$end_date,$efd_id = null)*100/118;
    }
    public static function getTotalVatAmt($start_date,$end_date,$efd_id = null){
        return self::getTotalSaleVatExcl($start_date,$end_date,$efd_id = null)*0.18;
    }

    public static function getTotalTax($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.tax');
    }

    public static function getTotalExempt($start_date,$end_date,$efd_id = null){
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.turn_over');
    }
    public static function getTotalExemptFromStart($end_date,$efd_id = null){
        $start_date = '2020-01-01';
        $sales = DB::table('sales')
            ->join('efds', 'efds.id', '=', 'sales.efd_id')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($efd_id != null){
            $sales->where('efd_id','=',$efd_id);
        }
        return $sales = $sales->sum('sales.turn_over');
    }

    public static function getTotalRevenue($start_date, $end_date){
        return self::getTotalExempt($start_date, $end_date) + self::getTotalSaleVatExcl($start_date, $end_date);

    }

    public static function getCostOfSales($start_date, $end_date){
        $opening = Stock::getTotalOpeningStock($start_date, $end_date);
        $closing = Stock::getTotalClosingStock($start_date, $end_date);
        $purchases = Purchase::getTotalPurchasesByDate($start_date, $end_date);
        $adjustment = Purchase::getTotalAdjustmentExpenses($start_date, $end_date);
        $adjustment_Exempt = Purchase::getTotalExemptByDateAdjustable($start_date,$end_date);
        return $opening+$purchases-$adjustment-$adjustment_Exempt- $closing;
    }
}
