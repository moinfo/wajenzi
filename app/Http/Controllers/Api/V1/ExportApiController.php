<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\ProformaController;
use App\Http\Controllers\Billing\QuotationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectBoqController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectSiteVisitController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SalesDailyReportController;
use App\Http\Controllers\SiteDailyReportController;
use App\Http\Controllers\SiteReportController;
use App\Models\BillingDocument;
use App\Models\BillingPayment;
use App\Models\ProjectDocument;
use App\Models\ProjectSiteVisit;
use App\Models\SalesDailyReport;
use App\Models\SiteDailyReport;
use App\Services\WebTableExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mobile counterparts of the web "Export / Download" buttons.
 *
 * Every endpoint runs the same web controller action that produces the file on
 * the web, so the mobile download is byte-for-byte the same document. The file
 * is returned as base64 JSON (same shape as labor/contracts/{id}/pdf):
 *
 *   { success, data: { filename, mime_type, file_base64, pdf_base64? } }
 */
class ExportApiController extends Controller
{
    // ── BOQ ────────────────────────────────────────────────────────────────

    public function boqPdf(int $id): JsonResponse
    {
        return $this->wrap(app(ProjectBoqController::class)->exportPdf($id));
    }

    public function boqCsv(int $id): JsonResponse
    {
        return $this->wrap(app(ProjectBoqController::class)->exportCsv($id));
    }

    public function boqTemplateCsv(int $id): JsonResponse
    {
        return $this->wrap(app(ProjectBoqController::class)->exportTemplateCsv($id));
    }

    // ── Sales daily reports ───────────────────────────────────────────────

    public function salesSummaryPdf(Request $request): JsonResponse
    {
        return $this->wrap(app(SalesDailyReportController::class)->summaryPdf($request));
    }

    public function salesSummaryExcel(Request $request): JsonResponse
    {
        return $this->wrap(app(SalesDailyReportController::class)->summaryExcel($request));
    }

    public function salesReportPdf(int $id): JsonResponse
    {
        // The web page renders this view as HTML only; render it as a PDF for mobile.
        $report = SalesDailyReport::with([
            'preparedBy',
            'leadFollowups.clientSource',
            'salesActivities',
            'customerAcquisitionCost',
            'clientConcerns',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pages.sales.sales_daily_report_pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $this->file(
            $pdf->output(),
            'sales-daily-report-' . $report->report_date->format('Y-m-d') . '.pdf',
            'application/pdf'
        );
    }

    // ── Site visits ───────────────────────────────────────────────────────

    public function siteVisitInvoicePdf(Request $request, int $id): JsonResponse
    {
        $visit = ProjectSiteVisit::findOrFail($id);
        if (!$visit->invoice_number) {
            return response()->json([
                'success' => false,
                'message' => 'No invoice has been prepared for this visit yet.',
            ], 422);
        }

        $request->merge(['download' => 1]);

        return $this->wrap(app(ProjectSiteVisitController::class)->invoicePdf($request, $id));
    }

    // ── Billing ───────────────────────────────────────────────────────────

    public function paymentReceiptPdf(int $id): JsonResponse
    {
        $payment = BillingPayment::findOrFail($id);

        return $this->wrap(app(PaymentController::class)->receiptPDF($payment));
    }

    public function invoicePdf(int $id): JsonResponse
    {
        $doc = BillingDocument::findOrFail($id);

        return $this->wrap(app(InvoiceController::class)->generatePDF($doc));
    }

    public function invoiceReceiptPdf(int $id): JsonResponse
    {
        $doc = BillingDocument::findOrFail($id);

        return $this->wrap(app(InvoiceController::class)->generateReceipt($doc));
    }

    public function quotationPdf(int $id): JsonResponse
    {
        $doc = BillingDocument::findOrFail($id);

        return $this->wrap(app(QuotationController::class)->generatePDF($doc));
    }

    public function proformaPdf(int $id): JsonResponse
    {
        $doc = BillingDocument::findOrFail($id);

        return $this->wrap(app(ProformaController::class)->generatePDF($doc));
    }

    // ── Project documents / dashboard calendars ───────────────────────────

    public function projectDocument(int $id): JsonResponse
    {
        $doc = ProjectDocument::findOrFail($id);

        return $this->wrap(app(ProjectDocumentController::class)->download($doc->id));
    }

    public function followupsCalendar(Request $request): JsonResponse
    {
        return $this->wrap(app(DashboardController::class)->exportFollowupsToCalendar($request));
    }

    public function invoicesCalendar(Request $request): JsonResponse
    {
        return $this->wrap(app(DashboardController::class)->exportInvoicesToCalendar($request));
    }

    // ── Web list tables (DataTables Excel / PDF buttons) ──────────────────

    /**
     * GET exports/table?route=<web route name>&format=excel|pdf[&params[..]=..]
     */
    public function table(Request $request, WebTableExporter $exporter): JsonResponse
    {
        $format = $request->query('format', 'excel');
        if (!in_array($format, ['excel', 'pdf'], true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported format'], 422);
        }

        try {
            $table = $exporter->read(
                (string) $request->query('route'),
                (array) $request->query('params', []),
                $request->user(),
                (int) $request->query('index', 0),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $name = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $request->query('route')) . '_' . date('Y-m-d');

        return $format === 'pdf'
            ? $this->file($exporter->pdf($table), $name . '.pdf', 'application/pdf')
            : $this->file($exporter->xlsx($table), $name . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ── Site daily reports ────────────────────────────────────────────────
    public function siteDailyReportTxt(Request $request, int $id): JsonResponse
    {
        // Mirrors the permission middleware on the web controller (index/show).
        if (!$request->user()?->canAny(['View All Site Reports', 'View Own Site Reports'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $report = SiteDailyReport::findOrFail($id);

        return $this->wrap(app(SiteDailyReportController::class)->export($report));
    }

    // ── Procurement / reports ─────────────────────────────────────────────

    public function siteCostReportCsv(Request $request): JsonResponse
    {
        return $this->wrap(app(SiteReportController::class)->export($request));
    }

    public function attendanceReportExcel(Request $request): JsonResponse
    {
        $request->merge(['export' => 'excel']);

        return $this->wrap(app(ReportsController::class)->attendances_report($request));
    }

    public function dailyAttendanceReportExcel(Request $request): JsonResponse
    {
        $request->merge(['export' => 'excel']);

        return $this->wrap(app(ReportsController::class)->daily_attendances_report($request));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Capture the file produced by a web controller and return it as base64 JSON. */
    private function wrap(mixed $response): JsonResponse
    {
        if (!$response instanceof Response) {
            return response()->json(['success' => false, 'message' => 'Nothing to export.'], 422);
        }
        if ($response->getStatusCode() >= 300) {
            return response()->json(['success' => false, 'message' => 'Nothing to export.'], 422);
        }

        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();
            $content = (string) ob_get_clean();
        } elseif ($response instanceof BinaryFileResponse) {
            $content = (string) file_get_contents($response->getFile()->getPathname());
        } else {
            $content = (string) $response->getContent();
        }

        $disposition = (string) $response->headers->get('Content-Disposition', '');
        $filename = 'export';
        if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $m)) {
            $filename = rawurldecode($m[1]);
        }
        $mime = strtok((string) $response->headers->get('Content-Type', 'application/octet-stream'), ';');

        return $this->file($content, $filename, $mime ?: 'application/octet-stream');
    }

    private function file(string $content, string $filename, string $mime): JsonResponse
    {
        $b64 = base64_encode($content);
        $data = [
            'filename'    => $filename,
            'mime_type'   => $mime,
            'file_base64' => $b64,
        ];
        if ($mime === 'application/pdf') {
            $data['pdf_base64'] = $b64;
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}
