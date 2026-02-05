<?php

namespace Database\Seeders;

use App\Models\MaterialInspection;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialMovement;
use App\Models\ProjectMaterialRequest;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\QuotationComparison;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\SupplierReceiving;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcurementTestDataSeeder extends Seeder
{
    protected $output = [];

    /**
     * Run the complete procurement workflow test.
     *
     * Usage: php artisan db:seed --class=ProcurementTestDataSeeder
     */
    public function run(): void
    {
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('   PROCUREMENT WORKFLOW TEST - COMPLETE CYCLE');
        $this->command->info(str_repeat('=', 60) . "\n");

        try {
            // Note: Transaction removed for debugging
            // DB::beginTransaction();

            // Get test data
            $user = User::first();
            $mdUser = User::whereHas('roles', fn($q) => $q->where('name', 'Managing Director'))->first() ?? $user;
            $project = Project::first();

            if (!$project) {
                $this->command->error('No project found. Please create a project first.');
                return;
            }

            // Create BOQ item if not exists
            $boqItem = ProjectBoqItem::first();
            if (!$boqItem) {
                $this->command->info("Creating sample BOQ item...");
                // First check if project_boqs exists
                $boq = DB::table('project_boqs')->where('project_id', $project->id)->first();
                if (!$boq) {
                    $boqId = DB::table('project_boqs')->insertGetId([
                        'project_id' => $project->id,
                        'version' => 1,
                        'type' => 'INTERNAL',
                        'total_amount' => 0,
                        'status' => 'draft',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $boqId = $boq->id;
                }

                $boqItem = ProjectBoqItem::create([
                    'boq_id' => $boqId,
                    'item_code' => 'TEST-001',
                    'description' => 'Test Material - Cement Bags',
                    'unit' => 'bags',
                    'quantity' => 500,
                    'unit_price' => 25000,
                    'total_price' => 12500000,
                    'quantity_requested' => 0,
                    'quantity_ordered' => 0,
                    'quantity_received' => 0,
                    'quantity_used' => 0,
                    'procurement_status' => 'not_started',
                ]);
                $this->command->info("  ✓ Created BOQ item: {$boqItem->item_code}");
            }

            // Create suppliers if less than 3
            $suppliers = Supplier::limit(3)->get();
            if ($suppliers->count() < 3) {
                $this->command->info("Creating sample suppliers...");
                $supplierNames = ['ABC Building Supplies', 'XYZ Construction Materials', 'Quality Hardware Ltd'];
                for ($i = $suppliers->count(); $i < 3; $i++) {
                    $supplier = Supplier::create([
                        'name' => $supplierNames[$i],
                        'phone' => '0700' . rand(100000, 999999),
                        'email' => 'supplier' . ($i + 1) . '@test.com',
                        'address' => 'Test Address ' . ($i + 1),
                    ]);
                    $this->command->info("  ✓ Created supplier: {$supplier->name}");
                }
                $suppliers = Supplier::limit(3)->get();
            }

            if ($suppliers->count() < 3) {
                $this->command->error('Failed to create suppliers. Need at least 3.');
                return;
            }

            $this->command->info("Test Data:");
            $this->command->info("  User: {$user->name}");
            $this->command->info("  MD User: {$mdUser->name}");
            $this->command->info("  Project: {$project->project_name}");
            $this->command->info("  BOQ Item: {$boqItem->item_code}");
            $this->command->info("  Suppliers: " . $suppliers->pluck('name')->join(', '));
            $this->command->info("");

            // STEP 1: Create Material Request
            $this->command->info("STEP 1: Creating Material Request...");
            $request = $this->createMaterialRequest($project, $boqItem, $user);
            $this->command->info("  ✓ Created: {$request->request_number}");
            $this->command->info("  Status: {$request->status}");

            // STEP 2: Approve Material Request
            $this->command->info("\nSTEP 2: Approving Material Request...");
            $request->update([
                'status' => 'APPROVED',
                'approved_by' => $mdUser->id,
            ]);
            $request->refresh();
            $this->command->info("  ✓ Approved by: {$mdUser->name}");
            $this->command->info("  Status: {$request->status}");

            // STEP 3: Add Supplier Quotations
            $this->command->info("\nSTEP 3: Adding Supplier Quotations...");
            $quotations = $this->createSupplierQuotations($request, $suppliers, $user);
            foreach ($quotations as $q) {
                $this->command->info("  ✓ {$q->quotation_number} - {$q->supplier->name} - " . number_format($q->grand_total, 2));
            }
            $this->command->info("  Total quotations: " . count($quotations));

            // STEP 4: Create Quotation Comparison
            $this->command->info("\nSTEP 4: Creating Quotation Comparison...");
            $selectedQuotation = collect($quotations)->sortBy('grand_total')->first();
            $comparison = $this->createQuotationComparison($request, $selectedQuotation, $user);
            $this->command->info("  ✓ Created: {$comparison->comparison_number}");
            $this->command->info("  Recommended: {$selectedQuotation->supplier->name}");
            $this->command->info("  Status: {$comparison->status}");

            // STEP 5: Approve Quotation Comparison
            $this->command->info("\nSTEP 5: Approving Quotation Comparison...");
            $comparison->update([
                'status' => 'APPROVED',
                'approved_by' => $mdUser->id,
                'approved_date' => now(),
            ]);
            // Update quotation statuses
            $selectedQuotation->update(['status' => 'selected']);
            SupplierQuotation::where('material_request_id', $request->id)
                ->where('id', '!=', $selectedQuotation->id)
                ->update(['status' => 'rejected']);
            $comparison->refresh();
            $this->command->info("  ✓ Approved by: {$mdUser->name}");
            $this->command->info("  Status: {$comparison->status}");

            // STEP 6: Create Purchase Order
            $this->command->info("\nSTEP 6: Creating Purchase Order...");
            $purchase = Purchase::createFromComparison($comparison, $user->id);
            $this->command->info("  ✓ Purchase ID: {$purchase->id}");
            $this->command->info("  Supplier: {$purchase->supplier->name}");
            $this->command->info("  Amount: " . number_format($purchase->total_amount, 2));

            // STEP 7: Record Delivery
            $this->command->info("\nSTEP 7: Recording Delivery...");
            $receiving = $this->createSupplierReceiving($purchase, $request);
            $this->command->info("  ✓ Receiving ID: {$receiving->id}");
            $this->command->info("  Delivery Note: {$receiving->delivery_note_number}");
            $this->command->info("  Quantity: {$receiving->quantity_delivered}");
            $this->command->info("  Status: {$receiving->status}");

            // STEP 8: Create Material Inspection
            $this->command->info("\nSTEP 8: Creating Material Inspection...");
            $inspection = $this->createMaterialInspection($receiving, $boqItem, $user);
            $this->command->info("  ✓ Created: {$inspection->inspection_number}");
            $this->command->info("  Quantity Accepted: {$inspection->quantity_accepted}");
            $this->command->info("  Status: {$inspection->status}");

            // Record BOQ before approval
            $boqItem->refresh();
            $beforeReceived = $boqItem->quantity_received ?? 0;

            // STEP 9: Verify and Approve Inspection
            $this->command->info("\nSTEP 9: Verifying and Approving Inspection...");

            // Verify
            $inspection->update(['verifier_id' => $user->id]);
            $this->command->info("  ✓ Verified by: {$user->name}");

            // Approve
            $inspection->update(['status' => 'APPROVED']);
            $this->command->info("  ✓ Approved - Status: {$inspection->status}");

            // STEP 10: Update Stock (Triggered by approval)
            $this->command->info("\nSTEP 10: Updating Stock...");
            if (!$inspection->stock_updated) {
                $inspection->updateStock();
                $inspection->refresh();
            }
            $this->command->info("  ✓ Stock Updated: " . ($inspection->stock_updated ? 'YES' : 'NO'));
            $this->command->info("  Stock Updated At: " . ($inspection->stock_updated_at ?? 'N/A'));

            // Verify inventory
            $inventory = ProjectMaterialInventory::where('project_id', $project->id)
                ->where('boq_item_id', $boqItem->id)
                ->first();

            if ($inventory) {
                $this->command->info("  Inventory Quantity: {$inventory->quantity}");
            }

            // Verify movement
            $movement = ProjectMaterialMovement::where('reference_type', MaterialInspection::class)
                ->where('reference_id', $inspection->id)
                ->first();

            if ($movement) {
                $this->command->info("  Movement: {$movement->movement_number} ({$movement->movement_type})");
            }

            // Verify BOQ
            $boqItem->refresh();
            $this->command->info("  BOQ Received: {$beforeReceived} → {$boqItem->quantity_received}");

            // DB::commit();

            // Summary
            $this->command->info("\n" . str_repeat('=', 60));
            $this->command->info('   TEST COMPLETED SUCCESSFULLY!');
            $this->command->info(str_repeat('=', 60));
            $this->command->info("\nSummary:");
            $this->command->info("  Material Request: {$request->request_number}");
            $this->command->info("  Quotation Comparison: {$comparison->comparison_number}");
            $this->command->info("  Material Inspection: {$inspection->inspection_number}");
            $this->command->info("  Stock Updated: " . ($inspection->stock_updated ? 'YES' : 'NO'));
            $this->command->info("");

        } catch (\Exception $e) {
            // DB::rollBack();
            $this->command->error("\nTest failed: " . $e->getMessage());
            $this->command->error("Line: " . $e->getLine());
            $this->command->error("File: " . $e->getFile());
        }
    }

    /**
     * Step 1: Create Material Request
     */
    private function createMaterialRequest($project, $boqItem, $user): ProjectMaterialRequest
    {
        return ProjectMaterialRequest::create([
            'project_id' => $project->id,
            'boq_item_id' => $boqItem->id,
            'requester_id' => $user->id,
            'quantity_requested' => 100,
            'unit' => $boqItem->unit ?? 'pcs',
            'required_date' => now()->addDays(7),
            'purpose' => 'Test procurement workflow - auto generated',
            'priority' => 'medium',
            'status' => 'pending',
        ]);
    }

    /**
     * Step 3: Create Supplier Quotations
     */
    private function createSupplierQuotations($request, $suppliers, $user): array
    {
        $quotations = [];
        $prices = [10000, 12000, 9500]; // Different prices for comparison

        foreach ($suppliers as $index => $supplier) {
            $unitPrice = $prices[$index] ?? rand(8000, 15000);
            $quantity = $request->quantity_requested;
            $totalAmount = $unitPrice * $quantity;
            $vatAmount = $totalAmount * 0.18; // 18% VAT

            $quotations[] = SupplierQuotation::create([
                'material_request_id' => $request->id,
                'supplier_id' => $supplier->id,
                'quotation_date' => now(),
                'valid_until' => now()->addDays(30),
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total_amount' => $totalAmount,
                'vat_amount' => $vatAmount,
                'delivery_time_days' => rand(5, 14),
                'payment_terms' => '30 days',
                'status' => 'received',
                'created_by' => $user->id,
            ]);
        }

        return $quotations;
    }

    /**
     * Step 4: Create Quotation Comparison
     */
    private function createQuotationComparison($request, $selectedQuotation, $user): QuotationComparison
    {
        return QuotationComparison::create([
            'material_request_id' => $request->id,
            'comparison_date' => now(),
            'selected_quotation_id' => $selectedQuotation->id,
            'recommended_supplier_id' => $selectedQuotation->supplier_id,
            'recommendation_reason' => 'Lowest price with acceptable delivery time - TEST',
            'prepared_by' => $user->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Step 7: Create Supplier Receiving
     */
    private function createSupplierReceiving($purchase, $request): SupplierReceiving
    {
        $receiving = SupplierReceiving::create([
            'purchase_id' => $purchase->id,
            'project_id' => $purchase->project_id,
            'supplier_id' => $purchase->supplier_id,
            'date' => now(),
            'delivery_note_number' => 'DN-TEST-' . rand(1000, 9999),
            'quantity_ordered' => $request->quantity_requested,
            'quantity_delivered' => $request->quantity_requested,
            'condition' => 'good',
            'status' => 'pending',
            'amount' => $purchase->total_amount,
        ]);
        // Refresh to get the correct ID
        return SupplierReceiving::find($receiving->id) ?? $receiving->fresh();
    }

    /**
     * Step 8: Create Material Inspection
     */
    private function createMaterialInspection($receiving, $boqItem, $user): MaterialInspection
    {
        return MaterialInspection::create([
            'supplier_receiving_id' => $receiving->id,
            'project_id' => $receiving->project_id,
            'boq_item_id' => $boqItem->id,
            'inspection_date' => now(),
            'quantity_delivered' => $receiving->quantity_delivered,
            'quantity_accepted' => $receiving->quantity_delivered, // Accept all
            'quantity_rejected' => 0,
            'overall_condition' => 'good',
            'overall_result' => 'pass',
            'inspector_id' => $user->id,
            'status' => 'pending',
        ]);
    }
}
