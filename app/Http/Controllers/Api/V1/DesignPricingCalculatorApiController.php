<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\DesignServiceAddon;
use App\Models\DesignServicePackage;
use App\Models\DesignSpecialStructure;
use App\Models\SiteVisitLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Design Pricing Calculator API.
 *
 * Mirrors the portal calculator at resources/views/calculators/design_pricing.blade.php.
 *
 * Compute logic ported directly from the inline JS in that blade:
 *   - Standard building:  total_usd = package.price_usd
 *                                   + extra_floors * (silver_low.price_usd / 2)
 *                                   + sum(selected_addons.price_low_or_high_usd)
 *     where extra_floors = max(0, floors - 1) for high-rise, else 0.
 *   - Special structure:  total_tzs = length_m * width_m * rate_tzs_per_sqm
 *                                   (quoted in TZS only per pricing spec).
 *   - AirBnB / multi-unit (≤ 2 units): total_usd = platinum.price_usd
 *                                                + (units - 1) * (silver_low.price_usd / 2).
 *     If units > 2 — escalate to CEO/MD.
 */
class DesignPricingCalculatorApiController extends Controller
{
    /** GET /api/v1/calculators/design-pricing — all prerequisite lookup data. */
    public function index(): JsonResponse
    {
        try {
            $lowPackages       = DesignServicePackage::where('is_active', true)->where('rise_type', 'low')->orderBy('sort_order')->orderBy('id')->get();
            $highPackages      = DesignServicePackage::where('is_active', true)->where('rise_type', 'high')->orderBy('sort_order')->orderBy('id')->get();
            $addons            = DesignServiceAddon::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
            $specialStructures = DesignSpecialStructure::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
            $currencies        = Currency::where('is_active', true)->orderBy('code')->orderBy('name')->get();
            $locations         = SiteVisitLocation::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

            $tzsRate = (float) (Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640);

            return response()->json([
                'success' => true,
                'data'    => [
                    'tzs_rate_per_usd'    => $tzsRate,
                    'low_packages'        => $lowPackages->map(fn ($p) => $this->packageItem($p))->values(),
                    'high_packages'       => $highPackages->map(fn ($p) => $this->packageItem($p))->values(),
                    'addons'              => $addons->map(fn ($a) => $this->addonItem($a))->values(),
                    'special_structures'  => $specialStructures->map(fn ($s) => $this->specialItem($s))->values(),
                    'currencies'          => $currencies->map(fn ($c) => $this->currencyItem($c))->values(),
                    'locations'           => $locations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Design pricing index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load calculator data'], 500);
        }
    }

    /** POST /api/v1/calculators/design-pricing/compute */
    public function compute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'mode'              => 'required|string|in:standard,special,airbnb',
                // Standard
                'rise_type'         => 'required_if:mode,standard|string|in:low,high',
                'package_id'        => 'required_if:mode,standard|integer|exists:design_service_packages,id',
                'floors'            => 'nullable|integer|min:1|max:50',
                'addon_ids'         => 'sometimes|array',
                'addon_ids.*'       => 'integer|exists:design_service_addons,id',
                // Special
                'special_id'        => 'required_if:mode,special|integer|exists:design_special_structures,id',
                'length_m'          => 'required_if:mode,special|numeric|min:0',
                'width_m'           => 'required_if:mode,special|numeric|min:0',
                // AirBnB / multi-unit
                'units'             => 'required_if:mode,airbnb|integer|min:1',
                // Display
                'display_currency'  => 'sometimes|string|max:10',
            ]);

            $tzsRate = (float) (Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640);

            return match ($validated['mode']) {
                'standard' => $this->computeStandard($validated, $tzsRate),
                'special'  => $this->computeSpecial($validated, $tzsRate),
                'airbnb'   => $this->computeAirbnb($validated, $tzsRate),
            };
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Design pricing compute error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Compute failed: ' . $e->getMessage()], 500);
        }
    }

    private function computeStandard(array $in, float $tzsRate): JsonResponse
    {
        $package = DesignServicePackage::find($in['package_id']);
        if (! $package || $package->rise_type !== $in['rise_type']) {
            return response()->json(['success' => false, 'message' => 'Package does not match rise type'], 422);
        }

        $rise        = $in['rise_type'];
        $floors      = (int) ($in['floors'] ?? 1);
        $extraFloors = $rise === 'high' ? max(0, $floors - 1) : 0;

        // Use a silver low-rise as the "extra floor" reference (port of JS logic).
        $silverPkg = DesignServicePackage::where('rise_type', $rise)
            ->whereRaw('LOWER(name) LIKE ?', ['%silver%'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first()
            ?? DesignServicePackage::where('rise_type', $rise)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

        $perFloorUsd = $silverPkg ? ((float) $silverPkg->price_usd / 2.0) : 0.0;
        $extraCost   = $extraFloors * $perFloorUsd;

        $addonIds   = $in['addon_ids'] ?? [];
        $addonRows  = DesignServiceAddon::whereIn('id', $addonIds)->where('is_active', true)->get();
        $addonLines = [];
        $addonCost  = 0.0;
        foreach ($addonRows as $a) {
            $price = $rise === 'low' ? (float) $a->price_low_usd : (float) $a->price_high_usd;
            $addonCost += $price;
            $addonLines[] = [
                'addon_id'  => $a->id,
                'name'      => $a->name,
                'price_usd' => $price,
            ];
        }

        $totalUsd = (float) $package->price_usd + $extraCost + $addonCost;

        $allServices = collect($package->included_services ?? [])
            ->merge(collect($addonRows)->pluck('name'))
            ->filter()
            ->values()
            ->all();

        $invoiceText = $package->name . ' design package (' . $rise . '-rise)'
            . ($extraFloors > 0 ? ', including ' . $extraFloors . ' additional storey(s)' : '')
            . '. Includes: ' . (empty($allServices) ? '—' : implode(', ', $allServices))
            . '. Total: ' . $this->formatUsd($totalUsd) . ' (VAT exclusive).';

        return response()->json([
            'success' => true,
            'data'    => [
                'mode'           => 'standard',
                'total_usd'      => round($totalUsd, 2),
                'total_tzs'      => round($totalUsd * $tzsRate, 2),
                'breakdown'      => [
                    [
                        'label' => $package->name . ' (' . $rise . '-rise)',
                        'value_usd' => (float) $package->price_usd,
                    ],
                    ...$extraFloors > 0 ? [[
                        'label'     => 'Extra ' . $extraFloors . ' floor(s)',
                        'value_usd' => $extraCost,
                    ]] : [],
                    ...array_map(fn ($a) => [
                        'label'     => $a['name'],
                        'value_usd' => $a['price_usd'],
                    ], $addonLines),
                ],
                'invoice_text'   => $invoiceText,
                'extra_floors'   => $extraFloors,
                'per_floor_usd'  => $perFloorUsd,
            ],
        ]);
    }

    private function computeSpecial(array $in, float $tzsRate): JsonResponse
    {
        $structure = DesignSpecialStructure::find($in['special_id']);
        if (! $structure) {
            return response()->json(['success' => false, 'message' => 'Special structure not found'], 422);
        }
        $length = (float) $in['length_m'];
        $width  = (float) $in['width_m'];
        $sqm    = $length * $width;
        $totalTzs = $sqm * (float) $structure->rate_tzs_per_sqm;

        $invoiceText = $structure->name . ' design'
            . ', dimensions ' . $length . ' m × ' . $width . ' m (' . number_format($sqm, 0) . ' m²)'
            . '. Total: TZS ' . number_format($totalTzs, 0) . ' (VAT exclusive).';

        return response()->json([
            'success' => true,
            'data'    => [
                'mode'         => 'special',
                'total_tzs'    => round($totalTzs, 2),
                'total_usd'    => $tzsRate > 0 ? round($totalTzs / $tzsRate, 2) : 0,
                'area_sqm'     => $sqm,
                'rate_per_sqm' => (float) $structure->rate_tzs_per_sqm,
                'breakdown'    => [
                    ['label' => 'Structure', 'value' => $structure->name],
                    ['label' => 'Rate', 'value_tzs' => (float) $structure->rate_tzs_per_sqm, 'unit' => 'TZS/m²'],
                    ['label' => 'Dimensions', 'value' => $length . ' m × ' . $width . ' m'],
                    ['label' => 'Area', 'value' => number_format($sqm, 2) . ' m²'],
                ],
                'invoice_text' => $invoiceText,
            ],
        ]);
    }

    private function computeAirbnb(array $in, float $tzsRate): JsonResponse
    {
        $units = (int) $in['units'];
        if ($units > 2) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'mode'         => 'airbnb',
                    'escalate'     => true,
                    'message'      => 'More than 2 units — must be escalated to CEO/MD for pricing guidance.',
                    'total_usd'    => null,
                ],
            ]);
        }

        $platinum = DesignServicePackage::where('rise_type', 'low')
            ->where('is_active', true)
            ->whereRaw('LOWER(name) LIKE ?', ['%platinum%'])
            ->orderBy('sort_order')->first();
        $silverLow = DesignServicePackage::where('rise_type', 'low')
            ->where('is_active', true)
            ->whereRaw('LOWER(name) LIKE ?', ['%silver%'])
            ->orderBy('sort_order')->first()
            ?? DesignServicePackage::where('rise_type', 'low')->where('is_active', true)->orderBy('sort_order')->first();

        $platPrice    = $platinum ? (float) $platinum->price_usd : 580.0; // fallback per JS default
        $silverPrice  = $silverLow ? (float) $silverLow->price_usd : 320.0;
        $extra        = ($units - 1) * ($silverPrice / 2.0);
        $totalUsd     = $platPrice + $extra;

        return response()->json([
            'success' => true,
            'data'    => [
                'mode'         => 'airbnb',
                'escalate'     => false,
                'units'        => $units,
                'total_usd'    => round($totalUsd, 2),
                'total_tzs'    => round($totalUsd * $tzsRate, 2),
                'breakdown'    => [
                    ['label' => 'Platinum (low-rise base)', 'value_usd' => $platPrice],
                    ...$units > 1 ? [[
                        'label' => ($units - 1) . ' extra unit(s) × ' . $this->formatUsd($silverPrice / 2),
                        'value_usd' => $extra,
                    ]] : [],
                ],
                'invoice_text' => 'AirBnB / multi-unit design (' . $units . ' unit' . ($units > 1 ? 's' : '') . '). Total: ' . $this->formatUsd($totalUsd) . ' (VAT exclusive).',
            ],
        ]);
    }

    private function formatUsd(float $value): string
    {
        return 'USD ' . number_format(round($value), 0);
    }

    private function packageItem(DesignServicePackage $p): array
    {
        return [
            'id'                => $p->id,
            'name'              => $p->name,
            'rise_type'         => $p->rise_type,
            'price_usd'         => (float) $p->price_usd,
            'included_services' => $p->included_services ?? [],
            'sort_order'        => (int) $p->sort_order,
        ];
    }

    private function addonItem(DesignServiceAddon $a): array
    {
        return [
            'id'             => $a->id,
            'name'           => $a->name,
            'price_low_usd'  => (float) $a->price_low_usd,
            'price_high_usd' => (float) $a->price_high_usd,
            'sort_order'     => (int) $a->sort_order,
        ];
    }

    private function specialItem(DesignSpecialStructure $s): array
    {
        return [
            'id'               => $s->id,
            'name'             => $s->name,
            'rate_tzs_per_sqm' => (float) $s->rate_tzs_per_sqm,
            'sort_order'       => (int) $s->sort_order,
        ];
    }

    private function currencyItem(Currency $c): array
    {
        return [
            'id'          => $c->id,
            'name'        => $c->name,
            'symbol'      => $c->symbol,
            'code'        => $c->code,
            'rate_to_usd' => (float) $c->rate_to_usd,
        ];
    }
}
