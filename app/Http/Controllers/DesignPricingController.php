<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\DesignServiceAddon;
use App\Models\DesignServicePackage;
use App\Models\DesignSpecialStructure;

class DesignPricingController extends Controller
{
    public function index()
    {
        $lowPackages      = DesignServicePackage::active()->lowRise()->orderBy('sort_order')->get();
        $highPackages     = DesignServicePackage::active()->highRise()->orderBy('sort_order')->get();
        $addons           = DesignServiceAddon::active()->orderBy('sort_order')->get();
        $specialStructures= DesignSpecialStructure::active()->orderBy('sort_order')->get();
        $currencies       = Currency::active()->orderBy('sort_order')->get();

        // TZS rate needed to convert special-structure (TZS) prices to other currencies
        $tzsRate = Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640;

        return view('calculators.design_pricing', compact(
            'lowPackages', 'highPackages', 'addons', 'specialStructures', 'currencies', 'tzsRate'
        ));
    }
}
