<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StatutoryInvoicePayment;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatutorySchedulesReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?? date('Y'));
        if ($year < 2019 || $year > 2100) {
            $year = (int) date('Y');
        }

        $products = Product::with('subCategory:id,name')
            ->orderBy('name')
            ->get();

        $startDateYear = sprintf('%d-01-01', $year);
        $endDateYear = sprintf('%d-12-31', $year);
        $start = new DateTime($startDateYear);
        $start->modify('first day of this month');
        $end = new DateTime($endDateYear);
        $end->modify('first day of next month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);

        $months = [];
        foreach ($period as $dt) {
            $monthStart = $dt->format('Y-m-01');
            $months[] = [
                'label' => $dt->format('F, Y'),
                'start_date' => $monthStart,
            ];
        }

        $items = $products->map(function (Product $product) use ($months) {
            $amount = (float) ($product->amount ?? 0);
            [$billingCycleName, $annualTotal, $monthlyAmount] = $this->billingMetrics(
                (int) ($product->billing_cycle ?? 0),
                $amount
            );

            $monthly = collect($months)->map(function (array $month) use ($product, $amount) {
                $paidAmount = (float) StatutoryInvoicePayment::getPaidAmountByDate(
                    $amount,
                    (int) ($product->billing_cycle ?? 0),
                    (string) $product->issue_date,
                    (string) $product->due_date,
                    $month['start_date']
                );

                return [
                    'label' => $month['label'],
                    'amount' => $paidAmount,
                    'is_paid' => $paidAmount > 0,
                ];
            })->values();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sub_category_name' => $product->subCategory?->name,
                'per_annually' => $annualTotal,
                'per_monthly' => $monthlyAmount,
                'per_bill' => $amount,
                'billing_cycle' => (int) ($product->billing_cycle ?? 0),
                'billing_cycle_name' => $billingCycleName,
                'issue_date' => $product->issue_date,
                'due_date' => $product->due_date,
                'monthly' => $monthly,
                'total' => $monthly->sum('amount'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'months' => $months,
                'items' => $items,
                'available_years' => collect(range((int) date('Y') + 1, 2019))
                    ->sortDesc()
                    ->values(),
            ],
        ]);
    }

    private function billingMetrics(int $billingCycle, float $amount): array
    {
        return match ($billingCycle) {
            0 => ['One Time', $amount * 1, $amount],
            12 => ['Annually', $amount * 1, ($amount * 1) / 12],
            3 => ['Quarterly', $amount * 3, ($amount * 3) / 12],
            6 => ['Semi-Annually', $amount * 2, ($amount * 2) / 12],
            1 => ['Monthly', $amount * 12, $amount],
            default => ['Unknown', 0, 0],
        };
    }
}
