<?php

namespace Modules\CashRegister\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegister;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DiscrepancyReport extends Component
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
            $this->sessions = [];
            return;
        }

        // Debug: Log the date values
        Log::info('DiscrepancyReport Dates:', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'startDateType' => gettype($this->startDate),
            'endDateType' => gettype($this->endDate)
        ]);

        // Try multiple date formats
        $formats = ['m/d/Y', 'd-m-Y', 'Y-m-d', 'm/d/y', 'd/m/Y', 'd/m/y', 'Y-m-d H:i:s', 'm/d/Y H:i:s'];
        $startDate = null;
        $endDate = null;
        
        foreach ($formats as $format) {
            try {
                $startDate = Carbon::createFromFormat($format, $this->startDate)->startOfDay();
                $endDate = Carbon::createFromFormat($format, $this->endDate)->endOfDay();
                Log::info('Date parsed successfully with format: ' . $format, [
                    'startDate' => $this->startDate,
                    'endDate' => $this->endDate,
                    'parsedStart' => $startDate->format('Y-m-d H:i:s'),
                    'parsedEnd' => $endDate->format('Y-m-d H:i:s')
                ]);
                break;
            } catch (\Exception $e) {
                continue;
            }
        }
        
        if (!$startDate || !$endDate) {
            Log::error('Could not parse dates with any format, trying Carbon::parse():', [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'triedFormats' => $formats
            ]);
            
            // Try Carbon's parse method as fallback
            try {
                $startDate = Carbon::parse($this->startDate)->startOfDay();
                $endDate = Carbon::parse($this->endDate)->endOfDay();
                Log::info('Dates parsed successfully with Carbon::parse():', [
                    'parsedStart' => $startDate->format('Y-m-d H:i:s'),
                    'parsedEnd' => $endDate->format('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                Log::error('Could not parse dates even with Carbon::parse():', [
                    'startDate' => $this->startDate,
                    'endDate' => $this->endDate,
                    'error' => $e->getMessage()
                ]);
                $this->sessions = [];
                return;
            }
        }

        $query = CashRegisterSession::with(['cashier', 'register', 'closer'])
            ->where('restaurant_id', restaurant()->id)
            ->where('status', 'closed')
            ->whereBetween('closed_at', [$startDate, $endDate])
            // Exclude sessions with no discrepancy (zero or null)
            ->whereNotNull('discrepancy')
            ->where('discrepancy', '!=', 0);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->registerId) {
            $query->where('cash_register_id', $this->registerId);
        }

        if ($this->cashierId) {
            $query->where('opened_by', $this->cashierId);
        }

        $this->sessions = $query->orderBy('closed_at', 'desc')->get();
    }

    public function getDiscrepancyColor($discrepancy)
    {
        $absDiscrepancy = abs($discrepancy);
        
        if ($absDiscrepancy >= 200) {
            return 'text-red-600 dark:text-red-400';
        } elseif ($absDiscrepancy >= 50) {
            return 'text-yellow-600 dark:text-yellow-400';
        } else {
            return 'text-green-600 dark:text-green-400';
        }
    }

    public function getDiscrepancyFlag($discrepancy)
    {
        $absDiscrepancy = abs($discrepancy);
        
        if ($absDiscrepancy >= 200) {
            return 'RED';
        } elseif ($absDiscrepancy >= 50) {
            return 'AMBER';
        } else {
            return 'GREEN';
        }
    }

    public function render()
    {
        return view('cashregister::livewire.reports.discrepancy-report');
    }
}
