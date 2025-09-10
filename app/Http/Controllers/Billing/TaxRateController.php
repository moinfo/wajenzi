<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingTaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index()
    {
        $taxRates = BillingTaxRate::orderBy('name')->get();
        
        return view('billing.tax-rates.index', compact('taxRates'));
    }

    public function create()
    {
        return view('billing.tax-rates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:billing_tax_rates,code',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default) {
            BillingTaxRate::where('is_default', true)->update(['is_default' => false]);
        }

        $taxRate = BillingTaxRate::create($request->all());

        return redirect()
            ->route('billing.tax-rates.show', $taxRate)
            ->with('success', 'Tax rate created successfully.');
    }

    public function show(BillingTaxRate $taxRate)
    {
        $taxRate->loadCount('documentItems');
        
        $recentItems = $taxRate->documentItems()
            ->with(['document.client'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('billing.tax-rates.show', compact('taxRate', 'recentItems'));
    }

    public function edit(BillingTaxRate $taxRate)
    {
        return view('billing.tax-rates.edit', compact('taxRate'));
    }

    public function update(Request $request, BillingTaxRate $taxRate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:billing_tax_rates,code,' . $taxRate->id,
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default && !$taxRate->is_default) {
            BillingTaxRate::where('is_default', true)->update(['is_default' => false]);
        }

        $taxRate->update($request->all());

        return redirect()
            ->route('billing.tax-rates.show', $taxRate)
            ->with('success', 'Tax rate updated successfully.');
    }

    public function destroy(BillingTaxRate $taxRate)
    {
        if ($taxRate->documentItems()->count() > 0) {
            return back()->with('error', 'Cannot delete tax rate that is being used in documents. Deactivate instead.');
        }

        if ($taxRate->products()->count() > 0) {
            return back()->with('error', 'Cannot delete tax rate that is assigned to products. Deactivate instead.');
        }

        $taxRate->delete();

        return redirect()
            ->route('billing.tax-rates.index')
            ->with('success', 'Tax rate deleted successfully.');
    }

    public function activate(BillingTaxRate $taxRate)
    {
        $taxRate->update(['is_active' => true]);

        return back()->with('success', 'Tax rate activated successfully.');
    }

    public function deactivate(BillingTaxRate $taxRate)
    {
        if ($taxRate->is_default) {
            return back()->with('error', 'Cannot deactivate the default tax rate.');
        }

        $taxRate->update(['is_active' => false]);

        return back()->with('success', 'Tax rate deactivated successfully.');
    }

    public function setDefault(BillingTaxRate $taxRate)
    {
        if (!$taxRate->is_active) {
            return back()->with('error', 'Cannot set an inactive tax rate as default.');
        }

        BillingTaxRate::where('is_default', true)->update(['is_default' => false]);
        $taxRate->update(['is_default' => true]);

        return back()->with('success', 'Tax rate set as default successfully.');
    }
}