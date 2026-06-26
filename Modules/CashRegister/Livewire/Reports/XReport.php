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
// use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\CashRegister\Traits\CashRegisterPrintTrait;

class XReport extends Component
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
    public $selectedSessionId = null;
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
            'this_month' => [now()->startOfMonth(), now()->endOfDay()],
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

        

        // Try multiple date formats
        $formats = ['m/d/Y', 'd-m-Y', 'Y-m-d', 'm/d/y', 'd/m/Y'];
        $startDate = null;
        $endDate = null;
        
        foreach ($formats as $format) {
            try {
                $startDate = Carbon::createFromFormat($format, $this->startDate)->startOfDay();
                $endDate = Carbon::createFromFormat($format, $this->endDate)->endOfDay();
                
                break;
            } catch (\Exception $e) {
                continue;
            }
        }
        
        if (!$startDate || !$endDate) {
            
            $this->reportData = null;
            return;
        }

        // Simple approach: filter by opened_at date range
        $query = CashRegisterSession::with(['cashier', 'branch', 'register'])
            ->where('restaurant_id', restaurant()->id)
            ->whereBetween('opened_at', [$startDate, $endDate]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->registerId) {
            $query->where('cash_register_id', $this->registerId);
        }

        if ($this->cashierId) {
            $query->where('opened_by', $this->cashierId);
        }

        $sessions = $query->orderBy('opened_at', 'desc')->get();
        $this->sessions = $sessions;
        
        
        
        

        if ($sessions->isEmpty()) {
            
            $this->selectedSession = null;
            $this->reportData = null;
            return;
        }

        // If a session is selected via dropdown, use it
        if ($this->selectedSessionId) {
            $selectedSession = $sessions->firstWhere('id', (int) $this->selectedSessionId);
        } else {
            // Otherwise show current open session or the most recent session
            $selectedSession = $sessions->firstWhere('status', 'open')
                ?? $sessions->first();
        }

        if (!$selectedSession) {
            $this->selectedSession = null;
            $this->reportData = null;
            return;
        }
        
        // Reload the session with relationships to ensure they are available
        $this->selectedSession = CashRegisterSession::with(['cashier', 'branch', 'register'])
            ->find($selectedSession->id);

        $this->calculateReportData();
    }

    public function updatedSelectedSessionId()
    {
        if (!$this->selectedSessionId) {
            return;
        }

        // Ensure selected session exists within current list
        $session = collect($this->sessions)->firstWhere('id', (int) $this->selectedSessionId);
        if (!$session) {
            return;
        }

        $this->selectedSession = CashRegisterSession::with(['cashier', 'branch', 'register'])
            ->find($this->selectedSessionId);

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
        $this->printXReport($this->selectedSession->id, $this->reportData);
    }

    public function render()
    {
        return view('cashregister::livewire.reports.x-report');
    }
}
