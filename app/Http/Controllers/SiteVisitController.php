<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\SiteVisitLocation;

class SiteVisitController extends Controller
{
    public function index()
    {
        $locations  = SiteVisitLocation::active()->orderBy('sort_order')->get();
        $currencies = Currency::active()->orderBy('sort_order')->get();
        $tzsRate    = Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640;

        return view('calculators.site_visit', compact('locations', 'currencies', 'tzsRate'));
    }
}
