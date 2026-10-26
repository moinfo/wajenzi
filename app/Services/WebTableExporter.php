<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use ZipArchive;

/**
 * Server-side version of the web's DataTables "Excel" / "PDF" buttons.
 *
 * The web builds those files in the browser from the rows of the
 * `.js-dataTable-full` table. Here the same web page is rendered for the
 * calling user (through the normal web kernel, so its middleware and
 * permission checks still apply) and the same table is turned into the file.
 */
class WebTableExporter
{
    /**
     * @return array{title:string, headers:string[], rows:string[][]}
     */
    /**
     * Render a parameterless web page for $user (middleware and permission
     * checks of the web kernel apply) and return its HTML.
     */
    public function html(string $routeName, array $params, $user): string
    {
        $route = Route::getRoutes()->getByName($routeName);
        if (!$route || !in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
            throw new RuntimeException('Unknown export source.');
        }

        return $this->render('/' . ltrim($route->uri(), '/'), $params, $user);
    }

    /**
     * $anyTable: when the page has no DataTables list table (computed reports
     * and statements), fall back to the page's tables in order.
     */
    public function read(string $routeName, array $params, $user, int $index = 0, bool $anyTable = false): array
    {
        $html = $this->html($routeName, $params, $user);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $tables = $xp->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' js-dataTable-full ')]");
        if ($tables->length === 0 && $anyTable) {
            $tables = $xp->query('//div[contains(@class,"content")]//table|//table');
        }
        if ($tables->length === 0 || !$tables->item($index)) {
            throw new RuntimeException('No table to export on this page.');
        }
        $table = $tables->item($index);

        foreach (iterator_to_array($xp->query('.//style|.//script', $table)) as $node) {
            $node->parentNode->removeChild($node);
        }

        $headers = [];
        foreach ($xp->query('.//thead//tr[1]/th|.//thead//tr[1]/td', $table) as $th) {
            $headers[] = $this->text($th->textContent);
        }

        $rows = [];
        foreach ($xp->query('.//tbody/tr', $table) as $tr) {
            $row = [];
            foreach ($xp->query('./td|./th', $tr) as $td) {
                $row[] = $this->text($td->textContent);
            }
            if ($row) {
                $rows[] = $row;
            }
        }

        $title = $xp->query('//title')->item(0)?->textContent ?? $routeName;

        return [
            'title' => $this->text($title),
            'headers' => $headers,
            'rows' => $rows,
            // does the page offer a Start Date / End Date filter?
            'has_date_filter' => $xp->query('//input[@name="start_date"]')->length > 0,
        ];
    }

    public function xlsx(array $t): string
    {
        $all = array_merge([$t['headers']], $t['rows']);
        $sheet = '';
        foreach ($all as $r => $cells) {
            $sheet .= '<row r="' . ($r + 1) . '">';
            foreach ($cells as $c => $value) {
                $ref = $this->column($c) . ($r + 1);
                if (is_numeric($value) && !preg_match('/^0\d/', $value) && strlen($value) < 16) {
                    $sheet .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                } else {
                    $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
                        . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
                }
            }
            $sheet .= '</row>';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheet . '</sheetData></worksheet>');
        $zip->close();

        $content = file_get_contents($tmp);
        @unlink($tmp);

        return $content;
    }

    public function pdf(array $t): string
    {
        $e = fn ($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#111}'
            . 'h2{font-size:13px;margin:0 0 8px}'
            . 'table{width:100%;border-collapse:collapse}'
            . 'th{background:#2563EB;color:#fff;text-align:left}'
            . 'th,td{border:1px solid #ccc;padding:3px 4px;word-wrap:break-word}'
            . 'tr:nth-child(even) td{background:#f5f7fa}'
            . '</style></head><body><h2>' . $e($t['title']) . '</h2><table><thead><tr>';
        foreach ($t['headers'] as $h) {
            $html .= '<th>' . $e($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($t['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $e($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        return Pdf::loadHTML($html)->setPaper('a4', count($t['headers']) > 6 ? 'landscape' : 'portrait')->output();
    }

    /** Render any web URI (GET) for $user through the web kernel. Internal use only. */
    public function render(string $uri, array $params, $user): string
    {
        $original = app('request');

        Auth::guard('web')->setUser($user);
        Auth::shouldUse('web');

        // Many web pages read their filter straight from $_POST (the page's own
        // form posts it), so expose the filter there too for this render.
        $backup = [$_GET, $_POST];
        $_GET = $params + $_GET;
        $_POST = $params + $_POST;

        $sub = Request::create($uri, 'GET', $params);
        $sub->setUserResolver(fn () => $user);

        try {
            $response = app(Kernel::class)->handle($sub);
        } finally {
            [$_GET, $_POST] = $backup;
            app()->instance('request', $original);
            Auth::shouldUse('sanctum');
        }

        if ($response->getStatusCode() === 403) {
            throw new RuntimeException('You do not have access to this export.');
        }
        if ($response->getStatusCode() >= 300) {
            throw new RuntimeException('This page could not be exported (HTTP ' . $response->getStatusCode() . ').');
        }

        return $response->getContent();
    }

    private function text(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ', $s)));
    }

    private function column(int $i): string
    {
        $name = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26) . $name;
        }

        return $name;
    }
}
