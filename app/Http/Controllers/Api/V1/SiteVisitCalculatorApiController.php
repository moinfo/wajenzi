<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\SiteVisitLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Site Visit Calculator API.
 *
 * Mirrors resources/views/calculators/site_visit.blade.php.
 *
 * Compute logic (port of inline JS):
 *   per_day_tzs = travel + local + allowance + food + accommodation
 *   total_tzs   = base_cost_tzs + (per_day_tzs * days)
 *
 * Inputs are accepted in TZS (the underlying storage unit) — the mobile UI may
 * collect them in any display currency and convert back to TZS using the
 * `tzs_rate_per_usd` returned by the GET endpoint.
 */
class SiteVisitCalculatorApiController extends Controller
{
    /** GET /api/v1/calculators/site-visit — lookup data. */
    public function index(): JsonResponse
    {
        try {
            $locations  = SiteVisitLocation::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
            $currencies = Currency::where('is_active', true)->orderBy('code')->orderBy('name')->get();
            $tzsRate    = (float) (Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640);

            return response()->json([
                'success' => true,
                'data'    => [
                    'tzs_rate_per_usd' => $tzsRate,
                    'locations' => $locations->map(fn ($l) => [
                        'id'                       => $l->id,
                        'name'                     => $l->name,
                        'base_cost_tzs'            => (float) $l->base_cost_tzs,
                        'preset_travel_tzs'        => (float) $l->preset_travel_tzs,
                        'preset_local_tzs'         => (float) $l->preset_local_tzs,
                        'preset_allowance_tzs'     => (float) $l->preset_allowance_tzs,
                        'preset_food_tzs'          => (float) $l->preset_food_tzs,
                        'preset_accommodation_tzs' => (float) $l->preset_accommodation_tzs,
                    ])->values(),
                    'currencies' => $currencies->map(fn ($c) => [
                        'id'          => $c->id,
                        'name'        => $c->name,
                        'symbol'      => $c->symbol,
                        'code'        => $c->code,
                        'rate_to_usd' => (float) $c->rate_to_usd,
                    ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Site visit calc index error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load calculator data'], 500);
        }
    }

    /** POST /api/v1/calculators/site-visit/compute */
    public function compute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'location_id'           => 'required|integer|exists:site_visit_locations,id',
                'days'                  => 'required|integer|min:1|max:365',
                'travel_tzs'            => 'sometimes|nullable|numeric|min:0',
                'local_tzs'             => 'sometimes|nullable|numeric|min:0',
                'allowance_tzs'         => 'sometimes|nullable|numeric|min:0',
                'food_tzs'              => 'sometimes|nullable|numeric|min:0',
                'accommodation_tzs'     => 'sometimes|nullable|numeric|min:0',
                'override_base_tzs'     => 'sometimes|nullable|numeric|min:0',
                'notes'                 => 'sometimes|nullable|string|max:500',
            ]);

            $location = SiteVisitLocation::findOrFail($validated['location_id']);
            $days     = (int) $validated['days'];
            $base     = (float) ($validated['override_base_tzs'] ?? $location->base_cost_tzs);
            $travel   = (float) ($validated['travel_tzs']        ?? $location->preset_travel_tzs);
            $local    = (float) ($validated['local_tzs']         ?? $location->preset_local_tzs);
            $allow    = (float) ($validated['allowance_tzs']     ?? $location->preset_allowance_tzs);
            $food     = (float) ($validated['food_tzs']          ?? $location->preset_food_tzs);
            $accom    = (float) ($validated['accommodation_tzs'] ?? $location->preset_accommodation_tzs);

            $perDay = $travel + $local + $allow + $food + $accom;
            $total  = $base + ($perDay * $days);

            $tzsRate = (float) (Currency::where('code', 'TZS')->value('rate_to_usd') ?? 2640);

            $notes    = trim((string) ($validated['notes'] ?? ''));
            $heading  = 'For site visit of a ' . ($notes !== '' ? $notes : '[project description]') . ' at ' . $location->name . '.';
            $bd       = 'Base fee: TZS ' . number_format($base, 0)
                . ($travel  > 0 ? ', Travel: TZS ' . number_format($travel, 0) : '')
                . ($local   > 0 ? ', Local transport: TZS ' . number_format($local, 0) : '')
                . ($allow   > 0 ? ', Allowance: TZS ' . number_format($allow, 0) : '')
                . ($food    > 0 ? ', Food: TZS ' . number_format($food, 0) : '')
                . ($accom   > 0 ? ', Accommodation: TZS ' . number_format($accom, 0) : '')
                . '. Total: TZS ' . number_format($total, 0) . ' (VAT exclusive).';
            $invoiceText = $heading . "\n" . $bd;

            return response()->json([
                'success' => true,
                'data'    => [
                    'location'          => ['id' => $location->id, 'name' => $location->name],
                    'days'              => $days,
                    'base_tzs'          => $base,
                    'travel_tzs'        => $travel,
                    'local_tzs'         => $local,
                    'allowance_tzs'     => $allow,
                    'food_tzs'          => $food,
                    'accommodation_tzs' => $accom,
                    'per_day_tzs'       => $perDay,
                    'total_tzs'         => round($total, 2),
                    'total_usd'         => $tzsRate > 0 ? round($total / $tzsRate, 2) : 0.0,
                    'tzs_rate_per_usd'  => $tzsRate,
                    'invoice_text'      => $invoiceText,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Site visit calc compute error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Compute failed: ' . $e->getMessage()], 500);
        }
    }
}
