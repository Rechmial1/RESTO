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

class CashInOutReport extends Component
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
    public $type = '';
    
    // Report data
    public $transactions;
    public $summary = [];

    public function mount()
    {
        // Set default branch to current branch
        $this->branchId = branch()->id ?? null;
        
        // If user can view all reports, default to all; else restrict to self
        $this->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
        
        $this->transactions = collect();
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

    public function updatedType()
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
            $this->transactions = collect();
            $this->summary = [];
            return;
        }

        // Get sessions in date range
        $sessionQuery = CashRegisterSession::where('restaurant_id', restaurant()->id)
            ->whereBetween('opened_at', [
                Carbon::createFromFormat('m/d/Y', $this->startDate)->startOfDay(),
                Carbon::createFromFormat('m/d/Y', $this->endDate)->endOfDay()
            ]);

        if ($this->branchId) {
            $sessionQuery->where('branch_id', $this->branchId);
        }

        if ($this->registerId) {
            $sessionQuery->where('cash_register_id', $this->registerId);
        }

        if ($this->cashierId) {
            $sessionQuery->where('opened_by', $this->cashierId);
        }

        $sessionIds = $sessionQuery->pluck('id');

        // Get cash in/out/safe_drop transactions with optional type filter
        $query = CashRegisterTransaction::with(['session.cashier', 'session.register'])
            ->whereIn('cash_register_session_id', $sessionIds);

        if (in_array($this->type, ['cash_in', 'cash_out', 'safe_drop'], true)) {
            $query->where('type', $this->type);
        } else {
            $query->whereIn('type', ['cash_in', 'cash_out', 'safe_drop']);
        }

        $this->transactions = $query->orderBy('created_at', 'desc')->get();
        
        $this->calculateSummary();
    }

    private function calculateSummary()
    {
        $totalCashIn = $this->transactions->where('type', 'cash_in')->sum('amount');
        $totalCashOut = $this->transactions->where('type', 'cash_out')->sum('amount');
        $totalSafeDrop = $this->transactions->where('type', 'safe_drop')->sum('amount');
        $totalTransactions = $this->transactions->count();
        $cashInCount = $this->transactions->where('type', 'cash_in')->count();
        $cashOutCount = $this->transactions->where('type', 'cash_out')->count();
        $safeDropCount = $this->transactions->where('type', 'safe_drop')->count();

        $this->summary = [
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'total_safe_drop' => $totalSafeDrop,
            'net_cash_flow' => $totalCashIn - $totalCashOut - $totalSafeDrop,
            'total_transactions' => $totalTransactions,
            'cash_in_count' => $cashInCount,
            'cash_out_count' => $cashOutCount,
            'safe_drop_count' => $safeDropCount,
        ];
    }

    public function render()
    {
        return view('cashregister::livewire.reports.cash-in-out-report');
    }
}
