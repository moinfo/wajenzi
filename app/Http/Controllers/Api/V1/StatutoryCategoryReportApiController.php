<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\StatutoryPayment;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatutoryCategoryReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?? date('Y'));
        if ($year < 2019 || $year > 2100) {
            $year = (int) date('Y');
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $startDateYear = sprintf('%d-01-01', $year);
        $endDateYear = sprintf('%d-12-31', $year);

        $start = new DateTime($startDateYear);
        $start->modify('first day of this month');
        $end = new DateTime($endDateYear);
        $end->modify('first day of next month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);

        $rows = [];
        foreach ($period as $dt) {
            $startDate = $dt->format('Y-m-01');
            $endDate = $dt->format('Y-m-t');
            $categoryValues = [];

            foreach ($categories as $category) {
                $categoryValues[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'amount' => (float) StatutoryPayment::getTotalPaymentByCategory(
                        $category->id,
                        $startDate,
                        $endDate
                    ),
                ];
            }

            $rows[] = [
                'label' => $dt->format('F, Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'categories' => $categoryValues,
                'total' => (float) StatutoryPayment::getTotalPaymentByCategoryByDate(
                    $startDate,
                    $endDate
                ),
            ];
        }

        $footer = $categories->map(fn (Category $category) => [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'amount' => (float) StatutoryPayment::getTotalPaymentByCategory(
                $category->id,
                $startDateYear,
                $endDateYear
            ),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'categories' => $categories->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values(),
                'rows' => $rows,
                'footer' => $footer,
                'year_total' => (float) StatutoryPayment::getTotalPaymentByCategoryByDate(
                    $startDateYear,
                    $endDateYear
                ),
                'available_years' => collect(range((int) date('Y') + 1, 2019))
                    ->sortDesc()
                    ->values(),
            ],
        ]);
    }
}
