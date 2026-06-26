<?php

namespace Modules\CashRegister\Traits;

use Exception;
use App\Models\PrintJob;
use App\Models\Printer;
use App\Events\PrintJobCreated;
use Illuminate\Support\Facades\Log;
use Modules\CashRegister\Models\CashRegisterSession;

trait CashRegisterPrintTrait
{
    protected $imageFilename = null;
    protected $printerSetting;

    /**
     * Get active printer for cash register reports
     */
    private function getActivePrinter()
    {
        return Printer::where('is_active', 1)
            ->where('restaurant_id', restaurant()->id)
            ->first();
    }

    /**
     * Get print width in mm
     */
    private function getPrintWidth($printerSetting = null)
    {
        return match ($printerSetting?->print_format ?? 'thermal80mm') {
            'thermal56mm' => 56,
            'thermal112mm' => 112,
            default => 80,
        };
    }

    /**
     * Print X-Report
     */
    public function printXReport($sessionId, $reportData)
    {
        Log::info('[CashRegister] printXReport called for session ' . $sessionId);

        $this->printerSetting = $this->getActivePrinter();

        // Always generate image first (same-as main project's flow)
        $this->generateXReportImage($sessionId, $reportData);

        // Then create the print job record
        $this->executeXReportPrint($sessionId, $reportData);
    }

    private function generateXReportImage($sessionId, $reportData)
    {
        Log::info('[CashRegister] generateXReportImage called for session ' . $sessionId);
        try {
            // Small delay to avoid race conditions
            usleep(200000); // 200ms

            $width = $this->getPrintWidth($this->printerSetting);
            $thermal = true;
            $content = view('cashregister::print.x-report', compact('reportData', 'width', 'thermal'))->render();

            $this->dispatch('saveReportImageFromPrint', $sessionId, $content, 'x-report');
            Log::info('[CashRegister] X-Report image save event dispatched for session ' . $sessionId);
        } catch (Exception $e) {
            Log::error('[CashRegister] Failed to dispatch X-Report image save event: ' . $e->getMessage());
            Log::error('[CashRegister] Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function executeXReportPrint($sessionId, $reportData)
    {
        $this->imageFilename = 'x-report-' . $sessionId . '.png';
        $branchId = $reportData['session']->branch_id ?? null;

        $this->createReportPrintJob($branchId);
        $this->alert('success', 'X-Report sent to printer successfully.');
    }

    /**
     * Print Z-Report
     */
    public function printZReport($sessionId, $reportData)
    {
        Log::info('[CashRegister] printZReport called for session ' . $sessionId);

        $this->printerSetting = $this->getActivePrinter();

        // Always generate image first (same-as main project's flow)
        $this->generateZReportImage($sessionId, $reportData);

        // Then create the print job record
        $this->executeZReportPrint($sessionId, $reportData);
    }

    private function generateZReportImage($sessionId, $reportData)
    {
        Log::info('[CashRegister] generateZReportImage called for session ' . $sessionId);
        try {
            // Small delay to avoid race conditions
            usleep(200000); // 200ms

            $width = $this->getPrintWidth($this->printerSetting);
            $thermal = true;

            // Get denominations for this session
            $denominations = \Modules\CashRegister\Entities\CashRegisterCount::with('denomination')
                ->where('cash_register_session_id', $sessionId)
                ->where('count', '>', 0)
                ->get();

            $content = view('cashregister::print.z-report', compact('reportData', 'width', 'thermal', 'denominations'))->render();

            $this->dispatch('saveReportImageFromPrint', $sessionId, $content, 'z-report');
            Log::info('[CashRegister] Z-Report image save event dispatched for session ' . $sessionId);
        } catch (Exception $e) {
            Log::error('[CashRegister] Failed to dispatch Z-Report image save event: ' . $e->getMessage());
            Log::error('[CashRegister] Stack trace: ' . $e->getTraceAsString());
        }
    }

    private function executeZReportPrint($sessionId, $reportData)
    {
        $this->imageFilename = 'z-report-' . $sessionId . '.png';
        $branchId = $reportData['session']->branch_id ?? null;

        $this->createReportPrintJob($branchId);
        $this->alert('success', 'Z-Report sent to printer successfully.');
    }

    /**
     * Create print job record for cash register reports (same-as main project flow)
     */
    private function createReportPrintJob($branchId = null)
    {
        $printJob = PrintJob::create([
            'image_filename' => $this->imageFilename,
            'restaurant_id' => restaurant()->id,
            'branch_id' => $branchId,
            'status' => 'pending',
            'printer_id' => $this->printerSetting->id ?? null,
        ]);

        // Dispatch event for print job creation
        event(new PrintJobCreated($printJob));

        return $printJob;
    }
}
