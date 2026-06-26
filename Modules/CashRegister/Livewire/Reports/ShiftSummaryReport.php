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

class ShiftSummaryReport extends Component
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
    public $shifts;
    public $summary = [];

    public function mount()
    {
        // Set default branch to current branch
        $this->branchId = branch()->id ?? null;
        
        // If user can view all reports, default to all; else restrict to self
        $this->cashierId = user_can('View Cash Register Reports') ? '' : user()->id;
        
        $this->shifts = collect();
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
            $this->shifts = collect();
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

        $this->shifts = $query->orderBy('opened_at', 'desc')->get();
        
        $this->calculateSummary();
    }

    private function calculateSummary()
    {
        $totalShifts = $this->shifts->count();
        $completedShifts = $this->shifts->where('status', 'closed')->count();
        $pendingShifts = $this->shifts->where('status', 'pending')->count();
        $openShifts = $this->shifts->where('status', 'open')->count();
        
        $totalOpeningFloat = $this->shifts->sum('opening_float');
        $totalExpectedCash = $this->shifts->sum('expected_cash');
        $totalCountedCash = $this->shifts->sum('counted_cash');
        $totalDiscrepancy = $this->shifts->sum('discrepancy');
        
        // Calculate average session duration
        $completedSessionsWithDuration = $this->shifts->where('status', 'closed')->filter(function($session) {
            return $session->closed_at && $session->opened_at;
        });
        
        $totalDurationMinutes = $completedSessionsWithDuration->sum(function($session) {
            return $session->opened_at->diffInMinutes($session->closed_at);
        });
        
        $averageDurationMinutes = $completedSessionsWithDuration->count() > 0 
            ? $totalDurationMinutes / $completedSessionsWithDuration->count() 
            : 0;

        $this->summary = [
            'total_shifts' => $totalShifts,
            'completed_shifts' => $completedShifts,
            'pending_shifts' => $pendingShifts,
            'open_shifts' => $openShifts,
            'total_opening_float' => $totalOpeningFloat,
            'total_expected_cash' => $totalExpectedCash,
            'total_counted_cash' => $totalCountedCash,
            'total_discrepancy' => $totalDiscrepancy,
            'average_duration_minutes' => $averageDurationMinutes,
            'completion_rate' => $totalShifts > 0 ? ($completedShifts / $totalShifts) * 100 : 0,
        ];
    }

    public function getSessionDuration($session)
    {
        if (!$session->closed_at || !$session->opened_at) {
            return 'Ongoing';
        }
        
        $minutes = $session->opened_at->diffInMinutes($session->closed_at);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        return $remainingMinutes . 'm';
    }

    public function getSessionType($session)
    {
        if (!$session->opened_at) {
            return 'Unknown';
        }        
        // Apply timezone to get the correct local hour
        $hour = $session->opened_at->timezone(timezone())->hour;
        
        if ($hour >= 6 && $hour < 12) {
            return 'Morning';
        } elseif ($hour >= 12 && $hour < 18) {
            return 'Afternoon';
        } elseif ($hour >= 18 && $hour < 24) {
            return 'Evening';
        } else {
            return 'Night';
        }
    }

    public function render()
    {
        return view('cashregister::livewire.reports.shift-summary-report');
    }
}
