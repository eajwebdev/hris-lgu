<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\TimeLeaveReportDemoSeeder;
use Database\Seeders\OctoberDtrDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

// Some existing PDF views define global formatting functions. Each case runs
// in its own process, matching the lifetime of a normal PHP web request.
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TimeLeavePdfTest extends TestCase
{
    use DatabaseTransactions;

    public static function reports(): array
    {
        return [
            'DTR first half' => ['dtr-first-half', 'ALEX'],
            'October DTR first half' => ['october-first-half', 'CLYDE'],
            'October DTR second half' => ['october-second-half', 'CLYDE'],
            'DTR second half' => ['dtr-second-half', 'ALEX'],
            'DTR full month' => ['dtr-full-month', 'ALEX'],
            'overtime DTR' => ['dtr-overtime', 'ALEX'],
            'attendance logs' => ['dtr-logs', 'ALEX'],
            'overtime logs' => ['dtr-overtime-logs', 'ALEX'],
            'individual tardiness' => ['tardiness-individual', 'TARDINESS'],
            'all employees tardiness' => ['tardiness-all', 'ALEX'],
            'leave form' => ['leave-form', 'ALEX'],
            'disapproved leave form' => ['leave-disapproved', 'ALEX'],
            'leave transmittal' => ['leave-report', 'ALEX'],
            'event attendance' => ['event-attendance', 'ALEX'],
            'event permanent filter' => ['event-permanent', 'ALEX'],
        ];
    }

    #[DataProvider('reports')]
    public function test_report_returns_a_readable_pdf_with_demo_content(string $report, string $expectedText): void
    {
        $this->seed(TimeLeaveReportDemoSeeder::class);
        $this->actingAs(User::where('role', 'Administrator')->firstOrFail(), 'web');
        $month = Carbon::today('Asia/Manila')->subMonthNoOverflow()->startOfMonth();
        $period = $month->format('Y-m');
        $employee = 'DEMO-RPT-001';
        if (in_array($report, ['october-first-half', 'october-second-half'])) {
            $this->seed(OctoberDtrDemoSeeder::class);
            $period = '2026-10';
            $employee = \App\Models\Employee::where('fname', 'Clyde')->where('lname', 'Abendan')->value('emp_ID');
        }
        $dtr = fn ($part, $overtime = null) => route('dtr-pdf', ['employee' => $employee, 'period' => $part, 'date' => $period, 'overtime' => $overtime]);
        $log = fn ($overtime = null) => route('logDtrView', ['employeeId' => $employee, 'dateFrom' => $month->toDateString(), 'dateTo' => $month->copy()->endOfMonth()->toDateString(), 'overtime' => $overtime]);
        $event = Event::where('title', 'DEMO - Orientation (' . $month->format('F Y') . ')')->firstOrFail();
        $leaveTag = $report === 'leave-disapproved' ? 'DISAPPROVED' : 'APPROVED-VL';
        $leave = LeaveApplication::where('transnum', 'DEMO-' . $month->format('Ym') . '-' . $leaveTag)->firstOrFail();

        $url = match ($report) {
            'dtr-first-half' => $dtr(1), 'dtr-second-half' => $dtr(2), 'dtr-full-month' => $dtr(3),
            'october-first-half' => $dtr(1), 'october-second-half' => $dtr(2),
            'dtr-overtime' => $dtr(3, 1), 'dtr-logs' => $log(), 'dtr-overtime-logs' => $log(1),
            'tardiness-individual' => route('pdfTirednes', ['employeeId' => $employee, 'month' => $period]),
            'tardiness-all' => route('pdfTirednes', ['employeeId' => 0, 'month' => $period]),
            'leave-form', 'leave-disapproved' => route('previewLeave', $leave->id),
            'leave-report' => route('leaveReport'),
            'event-attendance' => route('reportGenrate', ['eventid' => $event->id, 'statusid' => 0]),
            'event-permanent' => route('reportGenrate', ['eventid' => $event->id, 'statusid' => 1]),
        };
        $leaveViewData = [];
        if (in_array($report, ['leave-form', 'leave-disapproved'])) {
            View::composer('leaves.generate-leave', function ($view) use (&$leaveViewData) {
                $leaveViewData = $view->getData();
            });
        }
        $response = $report === 'leave-report'
            ? $this->post($url, ['date' => $month->toDateString() . ' to ' . $month->copy()->endOfMonth()->toDateString()])
            : $this->get($url);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $bytes = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $bytes);
        $document = (new Parser)->parseContent($bytes);
        $this->assertNotEmpty($document->getPages());
        $this->assertStringContainsString($expectedText, strtoupper($document->getText()));
        if (in_array($report, ['october-first-half', 'october-second-half'])) {
            $this->assertStringContainsString('7:55', $document->getText());
            $this->assertStringNotContainsString('Invalid time range', $document->getText());
        }
        if (in_array($report, ['dtr-first-half', 'dtr-second-half', 'dtr-full-month', 'dtr-overtime', 'leave-form', 'leave-disapproved', 'leave-report', 'event-attendance', 'event-permanent'])) {
            $this->assertNotEmpty($document->getObjectsByType('XObject', 'Image'), 'The report must contain its header image.');
            $this->assertStringNotContainsString('Header Image', $document->getText());
        }
        if (in_array($report, ['leave-form', 'leave-disapproved'])) {
            $this->assertCount(2, $document->getPages(), 'The leave application must fit its front and back pages.');
            $this->assertGreaterThanOrEqual(2, count($document->getObjectsByType('XObject', 'Image')));
            $html = new \DOMDocument;
            $previousErrors = libxml_use_internal_errors(true);
            try {
                $html->loadHTML(view('leaves.generate-leave', $leaveViewData)->render());
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrors);
            }
            $xpath = new \DOMXPath($html);
            $checkboxes = $xpath->query("//div[contains(., 'For Approval') and contains(., 'For disapproval due to')]/input[@type='checkbox']");
            $this->assertCount(2, $checkboxes);
            $this->assertSame($report === 'leave-form', $checkboxes->item(0)->hasAttribute('checked'));
            $this->assertSame($report === 'leave-disapproved', $checkboxes->item(1)->hasAttribute('checked'));
        }
        $directory = sys_get_temp_dir() . '/hris-demo-report-pdfs';
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory . '/' . $report . '.pdf', $bytes);
    }
}
