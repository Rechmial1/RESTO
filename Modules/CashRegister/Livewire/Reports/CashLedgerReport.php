<?php

namespace Modules\CashRegister\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;
use Modules\CashRegister\Entities\CashRegister;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CashLedgerReport extends Component
{
    public $branches = [];
    public $registers = [];
    public $cashiers = [];
    
    // Filters
    public $branchId = '';
    public $registerId = '';
    public $cashierId = '';
    public $dateRangeType = 'this_month';
    public $startDate = '';
    public $endDate = '';
    
    // Report data
    public $sessions;
    public $summary = [];

    public function mount()
    {
        // Set default branch to current branch
        $this->branchId = branch()->id ?? null;
        
        // If user can view all reports, default to all; else restrict to self
        $this->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
        
        $this->sessions = collect();
        $this->loadBranches();
        $this->loadRegisters();
        $this->loadCashiers();
        $this->setDateRange();
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
            $this->sessions = collect();
            $this->summary = [];
            return;
        }

        $query = CashRegisterSession::with(['cashier', 'register', 'closer'])
            ->where('restaurant_id', restaurant()->id)
            ->whereBetween('opened_at', [
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

        $this->sessions = $query->orderBy('opened_at', 'desc')->get();
        
        $this->calculateSummary();
    }

    private function calculateSummary()
    {
        $totalSessions = $this->sessions->count();
        $totalOpeningFloat = $this->sessions->sum('opening_float');
        $totalExpectedCash = $this->sessions->sum('expected_cash');
        $totalCountedCash = $this->sessions->sum('counted_cash');
        $totalDiscrepancy = $this->sessions->sum('discrepancy');
        
        // Get all transactions for these sessions
        $sessionIds = $this->sessions->pluck('id');
        $transactions = CashRegisterTransaction::whereIn('cash_register_session_id', $sessionIds)->get();
        
        $totalCashSales = $transactions->where('type', 'cash_sale')->sum('amount');
        $totalCashIn = $transactions->where('type', 'cash_in')->sum('amount');
        $totalCashOut = $transactions->where('type', 'cash_out')->sum('amount');
        $totalSafeDrops = $transactions->where('type', 'safe_drop')->sum('amount');
        $totalChangeGiven = $transactions->where('type', 'change_given')->sum('amount');
        $totalRefunds = $transactions->where('type', 'refund')->sum('amount');

        $this->summary = [
            'total_sessions' => $totalSessions,
            'total_opening_float' => $totalOpeningFloat,
            'total_expected_cash' => $totalExpectedCash,
            'total_counted_cash' => $totalCountedCash,
            'total_discrepancy' => $totalDiscrepancy,
            'total_cash_sales' => $totalCashSales,
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'total_safe_drops' => $totalSafeDrops,
            'total_change_given' => $totalChangeGiven,
            'total_refunds' => $totalRefunds,
        ];
    }

    public function render()
    {
        return view('cashregister::livewire.reports.cash-ledger-report');
    }
}
