<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ProjectClient;
use App\Models\SiteVisitLocation;

class SiteVisitController extends Controller
{
    public function index()
    {
        $locations  = SiteVisitLocation::active()->orderBy('sort_order')->get();
        $currencies = Currency::active()->orderBy('code')->get();
        $clients    = ProjectClient::orderBy('first_name')->orderBy('last_name')->get();
        $tzsRate    = Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640;

        return view('calculators.site_visit', compact('locations', 'currencies', 'tzsRate', 'clients'));
    }
}
