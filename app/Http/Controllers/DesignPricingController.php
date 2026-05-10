<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\DesignServiceAddon;
use App\Models\DesignServicePackage;
use App\Models\DesignSpecialStructure;
use App\Models\ProjectClient;
use App\Models\SiteVisitLocation;

class DesignPricingController extends Controller
{
    public function index()
    {
        $lowPackages      = DesignServicePackage::active()->lowRise()->orderBy('sort_order')->get();
        $highPackages     = DesignServicePackage::active()->highRise()->orderBy('sort_order')->get();
        $addons           = DesignServiceAddon::active()->orderBy('sort_order')->get();
        $specialStructures= DesignSpecialStructure::active()->orderBy('sort_order')->get();
        $currencies       = Currency::active()->orderBy('code')->get();
        $locations        = SiteVisitLocation::active()->orderBy('sort_order')->pluck('name');
        $clients          = ProjectClient::orderBy('first_name')->orderBy('last_name')->get();

        $tzsRate = Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640;

        return view('calculators.design_pricing', compact(
            'lowPackages', 'highPackages', 'addons', 'specialStructures', 'currencies', 'tzsRate', 'locations', 'clients'
        ));
    }
}
