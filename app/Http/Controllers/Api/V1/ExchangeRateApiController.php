<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateApiController extends Controller
{
    public function index(): JsonResponse
    {
        $rates = ExchangeRate::with([
            'foreignCurrency:id,name,symbol',
            'baseCurrency:id,name,symbol',
        ])->orderByDesc('year')->orderByDesc('month')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $rates->map(fn (ExchangeRate $rate) => $this->transformRate($rate))->values(),
            'meta' => [
                'total' => $rates->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $currencies = Currency::query()->orderBy('name')->get(['id', 'name', 'symbol']);

        return response()->json([
            'success' => true,
            'data' => [
                'currencies' => $currencies->map(fn (Currency $currency) => [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $rate = ExchangeRate::with([
            'foreignCurrency:id,name,symbol',
            'baseCurrency:id,name,symbol',
        ])->find($id);

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformRate($rate),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'foreign_currency_id' => 'required|exists:currencies,id|different:base_currency_id',
            'base_currency_id' => 'required|exists:currencies,id',
            'rate' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $existing = ExchangeRate::query()
            ->where('foreign_currency_id', $validated['foreign_currency_id'])
            ->where('base_currency_id', $validated['base_currency_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An exchange rate already exists for this currency pair and period.',
            ], 422);
        }

        $rate = ExchangeRate::create($validated);
        $rate->load(['foreignCurrency:id,name,symbol', 'baseCurrency:id,name,symbol']);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate created successfully',
            'data' => $this->transformRate($rate),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rate = ExchangeRate::find($id);

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        $validated = $request->validate([
            'foreign_currency_id' => 'sometimes|required|exists:currencies,id|different:base_currency_id',
            'base_currency_id' => 'sometimes|required|exists:currencies,id',
            'rate' => 'sometimes|required|numeric|min:0',
            'month' => 'sometimes|required|integer|min:1|max:12',
            'year' => 'sometimes|required|integer|min:2000|max:2100',
        ]);

        $foreignCurrencyId = $validated['foreign_currency_id'] ?? $rate->foreign_currency_id;
        $baseCurrencyId = $validated['base_currency_id'] ?? $rate->base_currency_id;
        $month = $validated['month'] ?? $rate->month;
        $year = $validated['year'] ?? $rate->year;

        $existing = ExchangeRate::query()
            ->where('foreign_currency_id', $foreignCurrencyId)
            ->where('base_currency_id', $baseCurrencyId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('id', '!=', $rate->id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'An exchange rate already exists for this currency pair and period.',
            ], 422);
        }

        $rate->update($validated);
        $rate->load(['foreignCurrency:id,name,symbol', 'baseCurrency:id,name,symbol']);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate updated successfully',
            'data' => $this->transformRate($rate),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $rate = ExchangeRate::find($id);

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'Exchange rate not found',
            ], 404);
        }

        $rate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate deleted successfully',
        ]);
    }

    private function transformRate(ExchangeRate $rate): array
    {
        return [
            'id' => $rate->id,
            'foreign_currency_id' => $rate->foreign_currency_id,
            'foreign_currency_name' => $rate->foreignCurrency?->name,
            'foreign_currency_symbol' => $rate->foreignCurrency?->symbol,
            'base_currency_id' => $rate->base_currency_id,
            'base_currency_name' => $rate->baseCurrency?->name,
            'base_currency_symbol' => $rate->baseCurrency?->symbol,
            'rate' => (float) $rate->rate,
            'month' => (int) $rate->month,
            'year' => (int) $rate->year,
            'created_at' => $rate->created_at?->toIso8601String(),
            'updated_at' => $rate->updated_at?->toIso8601String(),
        ];
    }
}
