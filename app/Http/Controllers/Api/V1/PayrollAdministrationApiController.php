<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PayrollController;
use App\Models\Payroll;
use App\Models\PayrollNetSalary;
use App\Services\WebTableExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollAdministrationApiController extends Controller
{
    /**
     * GET payroll-administration/create-preview?month=&year=
     *
     * The table of the web's "Create New Payroll" modal (forms/payroll_form):
     * every staff member's computed payroll for the month.
     */
    public function createPreview(Request $request, WebTableExporter $web): JsonResponse
    {
        [$month, $year] = $this->monthYear($request);

        if (Payroll::getIsPayrollOpened($month, $year)) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        try {
            $form = $this->webPayrollForm($web, $request->user(), $month, $year);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $form['rows'],
            'meta' => ['month' => (int) $month, 'year' => (int) $year, 'headers' => $form['headers']],
        ]);
    }

    /**
     * POST payroll-administration/create {month, year}
     *
     * The web's "Create New Payroll" submit: the values the web form computes
     * are posted to PayrollController::create_payroll, which writes the payroll
     * and every staff member's salary, deduction, loan, advance and net lines.
     * Here the same form is rendered for the user and the same method is run
     * (inside one transaction), so the result is identical to the web's.
     */
    public function createFromWeb(Request $request, WebTableExporter $web): JsonResponse
    {
        [$month, $year] = $this->monthYear($request);

        if (Payroll::getIsPayrollOpened($month, $year)) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        try {
            $form = $this->webPayrollForm($web, $request->user(), $month, $year);
            if (!$form['inputs'] || empty($form['inputs']['staff_id'])) {
                return response()->json(['success' => false, 'message' => 'There is no payroll data to create.'], 422);
            }

            $web_request = Request::create('/payroll/create_payroll', 'POST', $form['inputs']);
            $web_request->setUserResolver(fn () => $request->user());

            DB::transaction(function () use ($web_request) {
                app(PayrollController::class)->create_payroll($web_request);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::error('Create payroll failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to create the payroll.'], 500);
        }

        $payroll = Payroll::with('user:id,name')->where('month', $month)->where('year', $year)->latest('id')->first();

        return response()->json([
            'success' => true,
            'data' => $payroll ? $this->transformPayroll($payroll) : null,
        ], 201);
    }

    /** @return array{0:int,1:int} */
    private function monthYear(Request $request): array
    {
        $v = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        return [(int) $v['month'], (int) $v['year']];
    }

    /**
     * Render the web payroll form for a month and read it: the preview table
     * and the hidden inputs the web form would post to create_payroll.
     *
     * @return array{headers:string[], rows:array<int,array<string,string>>, inputs:array<string,mixed>}
     */
    private function webPayrollForm(WebTableExporter $web, $user, int $month, int $year): array
    {
        $html = $web->render('/ajax/form', [
            'formName' => 'payroll_form',
            'className' => 'Payroll',
            'month' => $month,
            'year' => $year,
        ], $user);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $headers = [];
        foreach ($xp->query('//table[1]//thead//th') as $th) {
            $headers[] = trim(preg_replace('/\s+/u', ' ', $th->textContent));
        }

        $rows = [];
        foreach ($xp->query('//table[1]//tbody/tr') as $tr) {
            $cells = [];
            foreach ($xp->query('./td', $tr) as $i => $td) {
                $cells[$headers[$i] ?? (string) $i] = trim(preg_replace('/\s+/u', ' ', $td->textContent));
            }
            // Skip group-heading rows such as "HQ's Payroll" (no staff name).
            if (($cells['Name'] ?? '') !== '') {
                $rows[] = $cells;
            }
        }

        $inputs = [];
        foreach ($xp->query("//form[contains(@action,'create_payroll')]//input[@type='hidden'][@name]") as $input) {
            $name = $input->getAttribute('name');
            if ($name === '_token') {
                continue;
            }
            $value = $input->getAttribute('value');
            if (str_ends_with($name, '[]')) {
                $inputs[substr($name, 0, -2)][] = $value;
            } else {
                $inputs[$name] = $value;
            }
        }

        return ['headers' => $headers, 'rows' => $rows, 'inputs' => $inputs];
    }

    public function index(): JsonResponse
    {
        $payrolls = Payroll::with('user:id,name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payrolls->map(fn (Payroll $payroll) => $this->transformPayroll($payroll))->values(),
            'meta' => [
                'total' => $payrolls->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    ['name' => 'CREATED'],
                    ['name' => 'SUBMITTED'],
                    ['name' => 'APPROVED'],
                    ['name' => 'REJECTED'],
                    ['name' => 'CLOSED'],
                    ['name' => 'PAID'],
                ],
                'months' => collect(range(1, 12))->map(fn (int $month) => [
                    'id' => $month,
                    'name' => date('F', strtotime(sprintf('2000-%02d-01', $month))),
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $payroll = Payroll::with('user:id,name')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformPayroll($payroll),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => 'required|string|max:255|unique:payrolls,document_number',
            'payroll_number' => 'required|string|max:255|unique:payrolls,payroll_number',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'status' => 'required|in:CREATED,SUBMITTED,APPROVED,REJECTED,CLOSED,PAID',
            'submitted_date' => 'required|date',
        ]);

        $exists = Payroll::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        $payroll = new Payroll();
        $payroll->document_number = $validated['document_number'];
        $payroll->payroll_number = $validated['payroll_number'];
        $payroll->year = $validated['year'];
        $payroll->month = $validated['month'];
        $payroll->status = $validated['status'];
        $payroll->submitted_date = $validated['submitted_date'];
        $payroll->created_by_id = $request->user()?->id ?? 1;
        $payroll->save();
        $payroll->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Payroll created successfully',
            'data' => $this->transformPayroll($payroll),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::with('user:id,name')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        $validated = $request->validate([
            'document_number' => 'required|string|max:255|unique:payrolls,document_number,' . $id,
            'payroll_number' => 'required|string|max:255|unique:payrolls,payroll_number,' . $id,
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'status' => 'required|in:CREATED,SUBMITTED,APPROVED,REJECTED,CLOSED,PAID',
            'submitted_date' => 'required|date',
        ]);

        $exists = Payroll::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        $payroll->document_number = $validated['document_number'];
        $payroll->payroll_number = $validated['payroll_number'];
        $payroll->year = $validated['year'];
        $payroll->month = $validated['month'];
        $payroll->status = $validated['status'];
        $payroll->submitted_date = $validated['submitted_date'];
        $payroll->save();
        $payroll->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Payroll updated successfully',
            'data' => $this->transformPayroll($payroll),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        if ($payroll->status === 'APPROVED') {
            return response()->json([
                'success' => false,
                'message' => 'Approved payroll cannot be deleted',
            ], 422);
        }

        $payroll->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payroll deleted successfully',
        ]);
    }

    private function transformPayroll(Payroll $payroll): array
    {
        return [
            'id' => $payroll->id,
            'document_number' => $payroll->document_number,
            'payroll_number' => $payroll->payroll_number,
            'year' => $payroll->year,
            'month' => $payroll->month,
            'month_name' => date('F', strtotime(sprintf('2000-%02d-01', $payroll->month))),
            'status' => $payroll->approvalStatus?->status ?? $payroll->status,
            'submitted_date' => $payroll->submitted_date,
            'created_by_id' => $payroll->created_by_id,
            'created_by_name' => $payroll->user?->name,
            'payroll_amount' => (float) PayrollNetSalary::getTotalNetSalaryByPayroll($payroll->id),
            'created_at' => $payroll->created_at?->toISOString(),
            'updated_at' => $payroll->updated_at?->toISOString(),
        ];
    }
}
