<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    use HasFactory;

    public static function getTotalSupplierBalance($end_date)
    {
        $receiving = SupplierReceiving::getAllSupplierReceivingAmount($end_date);
        $transaction = TransactionMovement::getAllSupplierTransactionAmount($end_date);
        return $receiving - $transaction;
    }

    public static function getTotalInventoryForSpecificDate($start_date, $end_date)
    {
        return SystemInventory::getTotalInventoryForSpecificDate($start_date, $end_date);
    }

    public static function getTotalCashForSpecificDate($start_date, $end_date)
    {
        return SystemCash::getTotalCashForSpecificDate($start_date, $end_date);
    }

    public static function getTotalCreditForSpecificDate($start_date, $end_date)
    {
        return SystemCredit::getSystemCreditForSpecificDate($start_date, $end_date);
    }

    public static function getTotalCapitalForSpecificDate($start_date, $end_date)
    {
        return SystemCapital::getTotalCapitalForSpecificDate($start_date, $end_date);
    }

    static function getOpening($date)
    {
        $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($date)));
        $total_collection_per_day = \App\Models\Collection::getCollectionAmount($yesterday);
        $total_transaction_per_day = \App\Models\TransactionMovement::getAllSupplierTransactionAmount($yesterday);
        return $total_collection_per_day - $total_transaction_per_day;
    }

    public static function getTotalTransactionMuhidini($start_date, $end_date)
    {

        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql2')->table('muhidini.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }

    public static function getTotalTransactionKassim($start_date, $end_date)
    {
        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql3')->table('kassim.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }

    public static function getTotalTransactionLeruma($start_date, $end_date)
    {
        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql4')->table('leruma.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }

    public static function getSupplierDailyDebit($start_date, $end_date)
    {

        return DB::connection('mysql5')->select("SELECT *,
(SELECT first_name FROM bonge.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.employee_id) as first_name,
(SELECT last_name FROM bonge.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.employee_id) as last_name,
(SELECT first_name FROM bonge.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.client_id) as first_name_client,
(SELECT last_name FROM bonge.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.client_id) as last_name_client
 FROM bonge.`ospos_debits_credits` where date BETWEEN '$start_date' AND '$end_date' AND `delete` = '0' AND payment_type = 'SUPPLIER' ");
    }


    public static function getSupplierBankDepositedMuhidini($start_date, $end_date)
    {
        return DB::connection('mysql2')->table('muhidini.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr ?? 0;
    }

    public static function getSupplierBankDepositedKassim($start_date, $end_date)
    {
        return DB::connection('mysql3')->table('kassim.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr ?? 0;
    }

    public static function getSupplierBankDepositedLeruma($start_date, $end_date)
    {
        return DB::connection('mysql4')->table('leruma.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr ?? 0;
    }
    public static function getSupplierBankDepositedWhiteStar($start_date, $end_date)
    {

        return DB::connection('mysql6')->table('whitestar.ospos_banking')
            ->select(DB::raw('SUM(amount) as amount'))
            ->where('delete', '0')
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->amount ?? 0;
    }

    public static function getCustomerBankDepositedWhiteStar($start_date, $end_date)
    {

        return DB::connection('mysql6')->table('whitestar.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as amount'))
            ->where('delete', 0)
            ->where('paid_payment_type', '=',2)
            ->where('client_id', '!=',283)
            ->where('payment_type', '=','CUSTOMER')
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->amount ?? 0;
    }

    public static function getSupplierDailyDebitWhitestar($start_date, $end_date)
    {

        return DB::connection('mysql6')->select("SELECT *,
(SELECT first_name FROM whitestar.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.employee_id) as first_name,
(SELECT last_name FROM whitestar.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.employee_id) as last_name,
(SELECT first_name FROM whitestar.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.client_id) as first_name_client,
(SELECT last_name FROM whitestar.`ospos_people` WHERE ospos_people.person_id = ospos_debits_credits.client_id) as last_name_client
 FROM whitestar.`ospos_debits_credits` where date BETWEEN '$start_date' AND '$end_date' AND `delete` = '0' AND payment_type = 'SUPPLIER' ");
    }
//    public static function getTotalTransactionMuhidini($start_date, $end_date)
//    {
//
//        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
//        return DB::connection('mysql2')->table('muhidini.ospos_debits_credits')
//            ->select(DB::raw('SUM(dr) as dr'))
//            ->where('payment_mode', '1')
//            ->where('payment_type', 'SUPPLIER')
//            ->where('delete', '0')
//            ->where('paid_payment_type', 2)
//            ->whereBetween('date', [$start_date, $end_date])
//            ->get()->first()->dr;
//    }
//    public static function getTotalTransactionKassim($start_date, $end_date)
//    {
//        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
//        return DB::connection('mysql3')->table('kassim.ospos_debits_credits')
//            ->select(DB::raw('SUM(dr) as dr'))
//            ->where('payment_mode', '1')
//            ->where('payment_type', 'SUPPLIER')
//            ->where('delete', '0')
//            ->where('paid_payment_type', 2)
//            ->whereBetween('date', [$start_date, $end_date])
//            ->get()->first()->dr;
//    }
//    public static function getTotalTransactionLeruma($start_date, $end_date)
//    {
//        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
//        return DB::connection('mysql4')->table('leruma.ospos_debits_credits')
//            ->select(DB::raw('SUM(dr) as dr'))
//            ->where('payment_mode', '1')
//            ->where('payment_type', 'SUPPLIER')
//            ->where('delete', '0')
//            ->where('paid_payment_type', 2)
//            ->whereBetween('date', [$start_date, $end_date])
//            ->get()->first()->dr;
//    }
    public static function getSupplierDailyDebitAllTime($start_date, $end_date)
    {
//        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql5')->table('bonge.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }

    public static function getSupplierDailyDebitAllTimeWhiteStar($start_date, $end_date)
    {
//        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
//        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql6')->table('whitestar.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }
//    public static function getTotalWithDraw($start_date, $end_date)
//    {
//        return BankWithdraw::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
//
//    }

//    public static function getTotalBankDepositForSpecificDate($start_date,$end_date){
//        return   BankDeposit::Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'];
//
//    }
    static function getTotalLoan($start_date, $end_date)
    {
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status', 'APPROVED')->WhereBetween('date', [$start_date, $end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalAdvanceSalary($start_date, $end_date)
    {
        return AdvanceSalary::select([DB::raw("SUM(amount) as total_amount")])->Where('status', 'APPROVED')->WhereBetween('date', [$start_date, $end_date])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalNetSalary($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(net) as total_amount")])
                ->Where('status', 'APPROVED')->whereDate('created_at', '>=', $start_date)
                ->whereDate('created_at', '<=', $end_date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalAllowance($start_date, $end_date)
    {
        return PayrollRecord::select([DB::raw("SUM(allowance) as total_amount")])
                ->Where('status', 'APPROVED')->whereDate('created_at', '>=', $start_date)
                ->whereDate('created_at', '<=', $end_date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalTransactionWhitestar($start_date, $end_date)
    {
        $start_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
        $end_date = date('Y-m-d', strtotime('-1 day', strtotime($end_date)));
        return DB::connection('mysql6')->table('whitestar.ospos_debits_credits')
            ->select(DB::raw('SUM(dr) as dr'))
            ->where('payment_mode', '1')
            ->where('payment_type', 'SUPPLIER')
            ->where('delete', '0')
            ->where('paid_payment_type', 2)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()->first()->dr;
    }

    public static function getTotalDaysSalesBonge($start_date, $end_date, $customer_id)
    {
        return DB::connection('mysql5')->table('bonge.ospos_sales_payments')
            ->select(DB::raw('SUM(ospos_sales_payments.payment_amount) as cash'))
            ->join('bonge.ospos_sales', 'ospos_sales.sale_id', '=', 'ospos_sales_payments.sale_id')
            ->where('ospos_sales.sale_status', 0)
            ->where('ospos_sales_payments.payment_amount', '!=', 0)
            ->where('ospos_sales.customer_id', '=', $customer_id)
            ->whereBetween(DB::raw('DATE(ospos_sales_payments.payment_time)'), [$start_date, $end_date])
            ->get()->first()->cash;
    }

    public static function getReceivingItems($receiving_id)
    {
        return DB::connection('mysql5')->table('bonge.ospos_receivings_items')
            ->select('ospos_items.name')
            ->join('bonge.ospos_items', 'ospos_items.item_id', '=', 'ospos_receivings_items.item_id')
            ->where('ospos_receivings_items.receiving_id', '=', $receiving_id)
            ->get()->toArray();
    }

    public static function getTotalBankingWhitestar($start_date, $end_date)
    {
    }


}
