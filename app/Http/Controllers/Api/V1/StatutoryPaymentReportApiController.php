<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StatutoryPayment;
use App\Models\SubCategory;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatutoryPaymentReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = (int) ($request->input('year') ?? date('Y'));
        if ($year < 2019 || $year > 2100) {
            $year = (int) date('Y');
        }

        $subCategories = SubCategory::query()
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
            $subCategoryValues = [];

            foreach ($subCategories as $subCategory) {
                $subCategoryValues[] = [
                    'sub_category_id' => $subCategory->id,
                    'sub_category_name' => $subCategory->name,
                    'amount' => (float) StatutoryPayment::getTotalPaymentBySubCategory(
                        $subCategory->id,
                        $startDate,
                        $endDate
                    ),
                ];
            }

            $rows[] = [
                'label' => $dt->format('F, Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sub_categories' => $subCategoryValues,
                'total' => (float) StatutoryPayment::getTotalPayment(
                    $startDate,
                    $endDate
                ),
            ];
        }

        $footer = $subCategories->map(fn (SubCategory $subCategory) => [
            'sub_category_id' => $subCategory->id,
            'sub_category_name' => $subCategory->name,
            'amount' => (float) StatutoryPayment::getTotalPaymentBySubCategory(
                $subCategory->id,
                $startDateYear,
                $endDateYear
            ),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'sub_categories' => $subCategories->map(fn (SubCategory $subCategory) => [
                    'id' => $subCategory->id,
                    'name' => $subCategory->name,
                ])->values(),
                'rows' => $rows,
                'footer' => $footer,
                'year_total' => (float) StatutoryPayment::getTotalPayment(
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
