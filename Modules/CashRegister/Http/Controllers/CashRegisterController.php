<?php

namespace Modules\CashRegister\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashRegisterController extends Controller
{

    public function __construct()
    {
        abort_if(!in_array('Cash Register', restaurant_modules()), 403);
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cashregister::index');
    }

    public function dashboard()
    {
        return view('cashregister::dashboard');
    }

    public function cashier()
    {
        abort_if(!(user_can('Manage Cash Register Settings') || user_can('Open Cash Register')), 403);
        return view('cashregister::cashier');
    }

    public function reports()
    {
        return view('cashregister::reports');
    }

    public function denominationsIndex()
    {

        return view('cashregister::denominations.index');
    }

    public function printThermalReport(\Illuminate\Http\Request $request)
    {

        try {
            $content = $request->input('content');
            $type = $request->input('type');
            $restaurantId = $request->input('restaurant_id');
            $branchId = $request->input('branch_id');

            // Find an available thermal printer for this branch
            $printer = \App\Models\Printer::where('restaurant_id', $restaurantId)
                ->where(function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                })
                ->where('print_format', 'like', 'thermal%')
                ->first();

            if (!$printer) {
                // Try to find any printer for this restaurant
                $printer = \App\Models\Printer::where('restaurant_id', $restaurantId)
                    ->first();
            }

            if (!$printer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No thermal printer configured for this branch/restaurant.'
                ]);
            }

            // Create print job
            $printJob = \App\Models\PrintJob::create([
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'printer_id' => $printer->id,
                'image_filename' => 'thermal_report_' . time() . '.html',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Report sent to thermal printer successfully',
                'print_job_id' => $printJob->id
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to send report to printer: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cashregister::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('cashregister::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('cashregister::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}


    private function streamCsv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $handle = fopen('php://output', 'w');
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportDiscrepancy(Request $request): StreamedResponse
    {

        $filename = 'discrepancy_report_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            // Header
            fputcsv($handle, ['Date', 'Branch', 'Cashier', 'Expected', 'Counted', 'Discrepancy', 'Status', 'Manager Note']);

            $component = app(\Modules\CashRegister\Livewire\Reports\DiscrepancyReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope: show all if allowed, else self-only
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->sessions as $s) {
                fputcsv($handle, [
                    optional($s->closed_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    $s->expected_cash,
                    $s->counted_cash,
                    $s->discrepancy,
                    $s->status,
                    $s->closing_reason,
                ]);
            }
        });
    }

    public function exportCashLedger(Request $request): StreamedResponse
    {

        $filename = 'cash_ledger_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Date', 'Branch', 'Cashier', 'Opening Float', 'Cash Sales', 'Cash In', 'Cash Out', 'Safe Drops', 'Expected', 'Counted', 'Discrepancy']);

            $component = app(\Modules\CashRegister\Livewire\Reports\CashLedgerReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->sessions as $s) {
                $transactions = \Modules\CashRegister\Entities\CashRegisterTransaction::where('cash_register_session_id', $s->id)->get();
                fputcsv($handle, [
                    optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    $s->opening_float,
                    $transactions->where('type', 'cash_sale')->sum('amount'),
                    $transactions->where('type', 'cash_in')->sum('amount'),
                    $transactions->where('type', 'cash_out')->sum('amount'),
                    $transactions->where('type', 'safe_drop')->sum('amount'),
                    $s->expected_cash,
                    $s->counted_cash,
                    $s->discrepancy,
                ]);
            }
        });
    }

    public function exportCashInOut(Request $request): StreamedResponse
    {

        $filename = 'cash_in_out_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Date & Time', 'Branch', 'Cashier', 'Type', 'Amount', 'Reason']);

            $component = app(\Modules\CashRegister\Livewire\Reports\CashInOutReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            $component->registerId = $request->query('register');
            // Permission-based scope: if no permission, force to self
            $component->cashierId = user_can('View Cash Register Reports') ? $request->query('cashier') : user()->id;
            $component->type = $request->query('type');
            $component->generateReport();

            foreach ($component->transactions as $t) {
                // Match table label: translate app.<type>; fallback to Title Case if missing
                $typeKey = 'app.' . $t->type;
                $translated = __($typeKey);
                $typeLabel = $translated !== $typeKey ? $translated : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $t->type));

                fputcsv($handle, [
                    optional($t->created_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $t->session->branch->name ?? 'N/A',
                    $t->session->cashier->name ?? 'N/A',
                    $typeLabel,
                    $t->amount,
                    $t->reason,
                ]);
            }
        });
    }

    public function exportSessionSummary(Request $request): StreamedResponse
    {

        $filename = 'session_summary_' . now()->format('Ymd_His') . '.csv';
        return $this->streamCsv($filename, function ($handle) use ($request) {
            fputcsv($handle, ['Opened', 'Branch', 'Cashier', 'Session Type', 'Duration', 'Opening Float', 'Expected', 'Counted', 'Discrepancy', 'Status']);

            $component = app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class);
            $component->startDate = $request->query('start');
            $component->endDate = $request->query('end');
            $component->branchId = $request->query('branch') ?: branch()->id ?? null;
            // Permission-based scope
            $component->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
            $component->generateReport();

            foreach ($component->shifts as $s) {
                fputcsv($handle, [
                    optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A'),
                    $s->branch->name ?? 'N/A',
                    $s->cashier->name ?? 'N/A',
                    app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class)->getSessionType($s),
                    app(\Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class)->getSessionDuration($s),
                    $s->opening_float,
                    $s->expected_cash,
                    $s->counted_cash,
                    $s->discrepancy,
                    $s->status,
                ]);
            }
        });
    }

    /**
     * Print X-Report for browser popup
     */
    public function printXReport($sessionId, $width = 80, $thermal = false)
    {
        // Get the session data
        $session = \Modules\CashRegister\Entities\CashRegisterSession::with(['branch', 'register', 'cashier'])
            ->findOrFail($sessionId);

        // Generate report data (similar to XReport Livewire component)
        $reportData = [
            'generated_at' => now(),
            'session' => $session,
            'opening_float' => $session->opening_float,
            'cash_sales' => $session->transactions()->where('type', 'cash_sale')->sum('amount'),
            'cash_in' => $session->transactions()->where('type', 'cash_in')->sum('amount'),
            'cash_out' => $session->transactions()->where('type', 'cash_out')->sum('amount'),
            'safe_drops' => $session->transactions()->where('type', 'safe_drop')->sum('amount'),
            'refunds' => $session->transactions()->where('type', 'refund')->sum('amount'),
            'expected_cash' => $session->expected_cash,
        ];

        return view('cashregister::print.x-report', compact('reportData', 'width', 'thermal'));
    }

    /**
     * Print Z-Report for browser popup
     */
    public function printZReport($sessionId, $width = 80, $thermal = false)
    {
        // Get the session data
        $session = \Modules\CashRegister\Entities\CashRegisterSession::with(['branch', 'register', 'cashier'])
            ->findOrFail($sessionId);

        // Generate report data (similar to ZReport Livewire component)
        $reportData = [
            'generated_at' => now(),
            'session' => $session,
            'opening_float' => $session->opening_float,
            'cash_sales' => $session->transactions()->where('type', 'cash_sale')->sum('amount'),
            'cash_in' => $session->transactions()->where('type', 'cash_in')->sum('amount'),
            'cash_out' => $session->transactions()->where('type', 'cash_out')->sum('amount'),
            'safe_drops' => $session->transactions()->where('type', 'safe_drop')->sum('amount'),
            'refunds' => $session->transactions()->where('type', 'refund')->sum('amount'),
            'expected_cash' => $session->expected_cash,
            'counted_cash' => $session->counted_cash,
            'discrepancy' => $session->discrepancy,
        ];

        // Get denominations for this session
        $denominations = \Modules\CashRegister\Entities\CashRegisterCount::with('denomination')
            ->where('cash_register_session_id', $sessionId)
            ->where('count', '>', 0)
            ->get();

        return view('cashregister::print.z-report', compact('reportData', 'width', 'thermal', 'denominations'));
    }
}
