<?php

namespace Modules\CashRegister\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;
use Modules\CashRegister\Entities\CashRegisterCount;
use Modules\CashRegister\Entities\CashRegister;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\CashRegister\Traits\CashRegisterPrintTrait;

class ZReport extends Component
{
    use LivewireAlert, CashRegisterPrintTrait;
    public $branches = [];
    public $registers = [];
    public $cashiers = [];
    
    // Filters
    public $branchId = '';
    public $registerId = '';
    public $cashierId = '';
    public $dateRangeType = 'today';
    public $startDate = '';
    public $endDate = '';
    
    // Report data
    public $reportData = null;
    public $selectedSession = null;
    public $denominations;
    public $sessions = [];

    public function mount()
    {
        // Set default branch to current branch
        $this->branchId = branch()->id ?? null;
        
        // If user can view all reports, default to all; else restrict to self
        $this->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
        
        $this->loadBranches();
        $this->loadRegisters();
        $this->loadCashiers();
        $this->setDateRange();

        // If a specific session is requested via query, adjust filters and preselect it
        $requestedSessionId = request()->query('session_id');
        if ($requestedSessionId) {
            $session = CashRegisterSession::with(['cashier', 'register', 'closer', 'branch'])
                ->where('restaurant_id', restaurant()->id)
                ->find($requestedSessionId);

            if ($session) {
                $this->dateRangeType = 'custom';
                $this->startDate = optional($session->closed_at)->copy()->startOfDay()->format('m/d/Y');
                $this->endDate = optional($session->closed_at)->copy()->endOfDay()->format('m/d/Y');
                $this->generateReport();
                // Ensure the selected session is exactly the requested one
                $this->selectedSession = $session;
                $this->calculateReportData();
            }
        }
    }

    public function loadBranches()
    {
        $this->branches = Branch::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    public function loadRegisters()
    {
        $query = CashRegister::where('restaurant_id', restaurant()->id);
        
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }
        
        $this->registers = $query->orderBy('name')->get();
    }

    public function loadCashiers()
    {
        $this->cashiers = User::withoutGlobalScope(\App\Scopes\BranchScope::class)
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    public function setDateRange()
    {
        $ranges = [
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['this_month'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
        
        $this->generateReport();
    }

    public function updatedBranchId()
    {
        $this->loadRegisters();
        $this->loadCashiers();
        $this->generateReport();
    }

    public function updatedRegisterId()
    {
        $this->generateReport();
    }

    public function updatedCashierId()
    {
        $this->generateReport();
    }

    public function updatedDateRangeType()
    {
        $this->setDateRange();
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
        $this->dateRangeType = 'custom';
        $this->generateReport();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->dateRangeType = 'custom';
        $this->generateReport();
    }

    public function generateReport()
    {
        if (!$this->startDate || !$this->endDate) {
            $this->reportData = null;
            return;
        }

        $query = CashRegisterSession::with(['cashier', 'register', 'closer', 'branch'])
            ->where('restaurant_id', restaurant()->id)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [
                Carbon::createFromFormat('m/d/Y', $this->startDate)->startOfDay(),
                Carbon::createFromFormat('m/d/Y', $this->endDate)->endOfDay()
            ]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->registerId) {
            $query->where('cash_register_id', $this->registerId);
        }

        if ($this->cashierId) {
            $query->where('opened_by', $this->cashierId);
        }

        $sessions = $query->orderBy('closed_at', 'desc')->get();
        $this->sessions = $sessions;

        if ($sessions->isEmpty()) {
            $this->reportData = null;
            return;
        }

        // Default to the most recent closed session
        $this->selectedSession = $sessions->first();

        if (!$this->selectedSession) {
            $this->reportData = null;
            return;
        }

        $this->calculateReportData();
    }

    private function calculateReportData()
    {
        $session = $this->selectedSession;
        
        // Get transactions for this session
        $transactions = CashRegisterTransaction::where('cash_register_session_id', $session->id)->get();
        
        $openingFloat = (float) $session->opening_float;
        $cashSales = $transactions->where('type', 'cash_sale')->sum('amount');
        $changeGiven = $transactions->where('type', 'change_given')->sum('amount');
        $cashIn = $transactions->where('type', 'cash_in')->sum('amount');
        $cashOut = $transactions->where('type', 'cash_out')->sum('amount');
        $safeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
        $refunds = $transactions->where('type', 'refund')->sum('amount');
        
        $expectedCash = $openingFloat + $cashSales + $cashIn - $changeGiven - $cashOut - $safeDrops - $refunds;

        // Get denomination counts using the correct relationship
        $this->denominations = CashRegisterCount::with('denomination')
            ->where('cash_register_session_id', $session->id)
            ->where('count', '>', 0)
            ->get();
            
        // Debug: Log denomination counts
        Log::info('[Z-Report] Session ID: ' . $session->id);
        Log::info('[Z-Report] Denominations found: ' . $this->denominations->count());
        foreach ($this->denominations as $denom) {
            Log::info('[Z-Report] Denomination: ' . ($denom->denomination->value ?? 'N/A') . ' × ' . $denom->count . ' = ' . $denom->subtotal);
        }

        $this->reportData = [
            'session' => $session,
            'opening_float' => $openingFloat,
            'cash_sales' => $cashSales,
            'change_given' => $changeGiven,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'safe_drops' => $safeDrops,
            'refunds' => $refunds,
            'expected_cash' => $expectedCash,
            'counted_cash' => (float) $session->counted_cash,
            'discrepancy' => (float) $session->discrepancy,
            'generated_at' => now(),
        ];
    }

    public function printReport()
    {
        if (!$this->reportData) {
            $this->alert('error', 'No report data available to print.');
            return;
        }

        // Use the proper print system from the trait
        $this->printZReport($this->selectedSession->id, $this->reportData);
    }

    public function selectSession(int $sessionId): void
    {
        $session = CashRegisterSession::with(['cashier', 'branch', 'register'])->find($sessionId);
        if (!$session) {
            $this->reportData = null;
            $this->selectedSession = null;
            return;
        }

        $this->selectedSession = $session;
        $this->calculateReportData();
    }

    public function render()
    {
        return view('cashregister::livewire.reports.z-report');
    }
}
