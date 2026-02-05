<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Purchase extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public static function getTotalAdjustmentExpenses($start_date, $end_date)
    {
        return AdjustmentExpense::where('date','>=',$start_date)->where('date','<=',$end_date)->sum('amount');
    }

    public function item() {
        return $this->belongsTo(Item::class);

    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function materialRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectMaterialRequest::class, 'material_request_id');
    }

    public function quotationComparison(): BelongsTo
    {
        return $this->belongsTo(QuotationComparison::class, 'quotation_comparison_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receivings(): HasMany
    {
        return $this->hasMany(SupplierReceiving::class);
    }
    public $fillable = [
        'id', 'supplier_id', 'is_expense', 'item_id', 'tax_invoice', 'invoice_date',
        'create_by_id', 'total_amount', 'amount_vat_exc', 'vat_amount', 'purchase_type',
        'file', 'date', 'status', 'document_number',
        // Procurement workflow fields
        'project_id', 'material_request_id', 'quotation_comparison_id',
        'expected_delivery_date', 'delivery_address', 'payment_terms', 'notes'
    ];

    public function getAll($start_date, $end_date, $supplier_id = null, $purchase_type = null){
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->get();
    }


    public function user(){
        return $this->belongsTo(User::class, 'create_by_id');
    }

    // Method required by the ApprovableModel interface
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();

        // Update BOQ item quantities for purchase items
        foreach ($this->purchaseItems as $item) {
            $item->updateBoqItemQuantities();
        }

        return true;
    }

    /**
     * Create purchase from approved quotation comparison
     *
     * @param QuotationComparison $comparison
     * @param int|null $userId Optional user ID (defaults to auth user)
     * @return self|null
     */
    public static function createFromComparison(QuotationComparison $comparison, ?int $userId = null): ?self
    {
        if (!$comparison->isApproved() || !$comparison->selectedQuotation) {
            return null;
        }

        $quotation = $comparison->selectedQuotation;
        $request = $comparison->materialRequest;

        // Get next ID (workaround for tables without proper auto_increment)
        $nextId = (self::max('id') ?? 0) + 1;

        // Use DB::table to bypass Eloquent events that trigger approval system
        DB::table('purchases')->insert([
            'id' => $nextId,
            'project_id' => $request->project_id,
            'material_request_id' => $request->id,
            'quotation_comparison_id' => $comparison->id,
            'supplier_id' => $quotation->supplier_id,
            'total_amount' => $quotation->grand_total,
            'amount_vat_exc' => $quotation->total_amount,
            'vat_amount' => $quotation->vat_amount,
            'invoice_date' => now(),
            'date' => now(),
            'payment_terms' => $quotation->payment_terms,
            'expected_delivery_date' => now()->addDays($quotation->delivery_time_days ?? 7),
            'status' => 'pending',
            'create_by_id' => $userId ?? auth()->id() ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $purchase = self::find($nextId);

        // Create purchase item linked to BOQ
        if ($request->boq_item_id) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'boq_item_id' => $request->boq_item_id,
                'description' => $request->boqItem?->description ?? 'Material from request',
                'unit' => $request->unit ?? $request->boqItem?->unit ?? 'pcs',
                'quantity' => $quotation->quantity,
                'unit_price' => $quotation->unit_price,
                'total_price' => $quotation->total_amount
            ]);
        }

        return $purchase;
    }

    public function isProjectPurchase(): bool
    {
        return $this->project_id !== null;
    }

    public function isProcurementLinked(): bool
    {
        return $this->material_request_id !== null;
    }

    public static function getTotalPurchases($end_date, $supplier_id = null, $purchase_type = null){
        $start_date = '2020-01-01';
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.total_amount');
    }
    public static function getTotalPurchasesBySupplier($start_date,$end_date, $supplier_id = null, $purchase_type = null){
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.total_amount');
    }

    public static function getTotalPurchasesWithVAT($end_date, $supplier_id = null, $purchase_type = null, $start_date = null){
        $start_date = $start_date ?? '2020-01-01';
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases->sum('purchases.vat_amount');
    }
    public static function getTotalAutoPurchasesVAT($end_date,$start_date = null)
    {
        $start_date =  $start_date ?? '2020-01-01';
        return DB::table('receipts')->where('receipt_total_tax','!=',0)->whereBetween('date',[$start_date,$end_date])->sum('receipt_total_tax');
    }

    public static function getTotalPurchasesWithVATExempt($end_date, $supplier_id = null, $purchase_type = null){
        $start_date = '2020-01-01';
        $purchases = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->join('items', 'items.id', '=', 'purchases.item_id')
            ->select('purchases.*','items.name as goods','suppliers.name as supplier', 'suppliers.vrn as vrn')
            ->where('date','>=',$start_date)
            ->where('date','<=',$end_date)
            ->Where('status','APPROVED');
        if($supplier_id != null){
            $purchases->where('supplier_id','=',$supplier_id);
        }if($purchase_type != null){
            $purchases->where('purchase_type','=',$purchase_type);
        }
        return $purchases = $purchases->sum('purchases.amount_vat_exc');
    }



    public static function getTotalAutoPurchasesByDate($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(receipt_total_incl_of_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }
    public static function getTotalNormalPurchasesByDate($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(total_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalPurchasesByDate($start_date, $end_date){
//        return self::getTotalAutoPurchasesByDate($start_date, $end_date) + self::getTotalNormalPurchasesByDate($start_date, $end_date);
        return self::getTotalPurchasesByDateByExempt($start_date, $end_date) + self::getTotalExemptByDate($start_date, $end_date);

    }


    public static function getTotalAutoPurchasesByDateByVat($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->where('receipt_total_tax','!=',0)->select([DB::raw("SUM(receipt_total_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalNormalPurchasesByDateByVat($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->Where('purchase_type',1)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(total_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalPurchasesByDateByVat($start_date, $end_date){
        return self::getTotalAutoPurchasesByDateByVat($start_date, $end_date) + self::getTotalNormalPurchasesByDateByVat($start_date, $end_date);
    }

    public static function getTotalAutoPurchasesByDateByExempt($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->where('receipt_total_tax','=',0)->select([DB::raw("SUM(receipt_total_incl_of_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalNormalPurchasesByDateByExempt($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->Where('purchase_type',2)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(total_amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getTotalPurchasesByDateByExempt($start_date, $end_date){
        return self::getTotalAutoPurchasesByDateByExempt($start_date, $end_date) + self::getTotalNormalPurchasesByDateByExempt($start_date, $end_date);

    }
    public static function getTotalAutoExemptByDate($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->where('receipt_total_tax','!=',0)->select([DB::raw("SUM(receipt_total_excl_of_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }
    public static function getTotalAutoExemptByDateAdjustable($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->where('is_expense','=','YES')->where('receipt_total_tax','!=',0)->select([DB::raw("SUM(receipt_total_excl_of_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }
    public static function getTotalNormalExemptByDate($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount_vat_exc) as amount_vat_exc")])->get()->first()['amount_vat_exc'] ?? 0;

    }
    public static function getTotalNormalExemptByDateAdjustable($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->where('is_expense','=','YES')->select([DB::raw("SUM(amount_vat_exc) as amount_vat_exc")])->get()->first()['amount_vat_exc'] ?? 0;

    }
    public static function getTotalExemptByDate($start_date, $end_date){
        return self::getTotalAutoExemptByDate($start_date, $end_date) + self::getTotalNormalExemptByDate($start_date, $end_date);

    }
    public static function getTotalExemptByDateAdjustable($start_date, $end_date){
        return self::getTotalAutoExemptByDateAdjustable($start_date, $end_date) + self::getTotalNormalExemptByDateAdjustable($start_date, $end_date);

    }
    public static function getTotalAutoVATByDate($start_date, $end_date){
        return Receipt::whereBetween('date', [$start_date, $end_date])->where('receipt_total_tax','!=',0)->select([DB::raw("SUM(receipt_total_tax) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }
    public static function getTotalNormalVATByDate($start_date, $end_date){
        return Purchase::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(vat_amount) as vat_amount")])->get()->first()['vat_amount'] ?? 0;

    }
    public static function getTotalVATByDate($start_date, $end_date){
        return self::getTotalAutoVATByDate($start_date, $end_date) + self::getTotalNormalVATByDate($start_date, $end_date);

    }

}
