<?php

namespace Modules\CashRegister\Livewire\Approvals;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\CashRegister\Entities\CashRegisterSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
// use Illuminate\Support\Facades\Log;

class ApprovalsList extends Component
{
    use WithPagination, AuthorizesRequests, LivewireAlert;

    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $dateRangeType = 'today';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $branchId = null;
    public ?string $status = 'pending_approval';
    public array $branches = [];

    protected $queryString = ['search', 'dateRangeType', 'startDate', 'endDate', 'branchId', 'status'];

    public function mount(): void
    {
        // Set default branch to current branch
        $this->branchId = branch()->id ?? null;
        
        $this->setDateRange();
        $this->branches = DB::table('branches')
            ->select('id', 'name')
            ->when(restaurant(), fn($q) => $q->where('restaurant_id', restaurant()->id))
            ->orderBy('name')
            ->get()
            ->map(fn($b) => ['id' => $b->id, 'name' => $b->name])
            ->all();
        
    }

    public function setDateRange(): void
    {
        $now = Carbon::now();
        switch ($this->dateRangeType) {
            case 'currentWeek':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'lastWeek':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'last7Days':
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'currentMonth':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'lastMonth':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'currentYear':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                break;
            case 'lastYear':
                $start = $now->copy()->subYear()->startOfYear();
                $end = $now->copy()->subYear()->endOfYear();
                break;
            case 'custom':
                // Don't change dates when custom is selected
                return;
            case 'today':
            default:
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
        }
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
        $this->dateFrom = $start->toDateString();
        $this->dateTo = $end->toDateString();
    }

    public function updatedDateRangeType(): void
    {
        $this->setDateRange();
    }

    public function updatedStartDate(): void
    {
        
        if ($this->startDate) {
            try {
                $parsed = Carbon::createFromFormat('m/d/Y', $this->startDate);
                $this->dateFrom = $parsed->toDateString();
                $this->dateRangeType = 'custom';
                
            } catch (\Exception $e) {
                
            }
        }
    }

    public function updatedEndDate(): void
    {
        
        if ($this->endDate) {
            try {
                $parsed = Carbon::createFromFormat('m/d/Y', $this->endDate);
                $this->dateTo = $parsed->toDateString();
                $this->dateRangeType = 'custom';
                
            } catch (\Exception $e) {
                
            }
        }
    }

    #[On('setStartDate')]
    public function setStartDate($start = null): void
    {
        
        if ($start) {
            $this->startDate = $start;
            $this->dateRangeType = 'custom';
            $this->updateDateFrom();
        }
        
    }

    #[On('setEndDate')]
    public function setEndDate($end = null): void
    {
        
        if ($end) {
            $this->endDate = $end;
            $this->dateRangeType = 'custom';
            $this->updateDateTo();
        }
        
    }

    private function updateDateFrom(): void
    {
        if (!$this->startDate) return;
        
        $formats = ['m/d/Y', 'd-m-Y', 'Y-m-d', 'm/d/y', 'd/m/Y'];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $this->startDate);
                $this->dateFrom = $parsed->toDateString();
                break;
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    private function updateDateTo(): void
    {
        if (!$this->endDate) return;
        
        $formats = ['m/d/Y', 'd-m-Y', 'Y-m-d', 'm/d/y', 'd/m/Y'];
        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $this->endDate);
                $this->dateTo = $parsed->toDateString();
                break;
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'dateFrom', 'dateTo', 'branchId'])) {
            $this->resetPage();
        }
    }

    public function approve(int $sessionId): void
    {
        $this->authorize('Approve Cash Register');

        $session = CashRegisterSession::query()
            ->where('restaurant_id', restaurant()->id)
            ->when(branch(), fn($q) => $q->where('branch_id', branch()->id))
            ->findOrFail($sessionId);

        if ($session->status !== 'pending_approval') {
            $this->alert('error', 'Session is not pending approval');
            return;
        }

        $session->status = 'closed';
        $session->approved_by = user()->id;
        $session->approved_at = now();
        $session->save();

        $this->alert('success', 'Session approved');
    }

    public function reject(int $sessionId): void
    {
        $this->authorize('Approve Cash Register');

        $session = CashRegisterSession::query()
            ->where('restaurant_id', restaurant()->id)
            ->when(branch(), fn($q) => $q->where('branch_id', branch()->id))
            ->findOrFail($sessionId);

        if ($session->status !== 'pending_approval') {
            $this->alert('error', 'Session is not pending approval');
            return;
        }

        $session->status = 'open';
        $session->approved_by = null;
        $session->approved_at = null;
        $session->save();

        $this->alert('success', 'Session sent back to cashier');
    }

    public function render()
    {
        

        $query = CashRegisterSession::query()
            ->with(['cashier', 'closer', 'register'])
            ->where('restaurant_id', restaurant()->id)
            ->when(branch(), fn($q) => $q->where('branch_id', branch()->id))
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            // If user does not have Approve Cash Register, restrict to self; approvers see all
            ->when(!user_can('Approve Cash Register'), fn($q) => $q->where('opened_by', user()->id));

        // Apply date filters exactly like X-Report
        if ($this->startDate && $this->endDate) {
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
            
            if ($startDate && $endDate) {
                $query->whereBetween('closed_at', [$startDate, $endDate]);
                
                // Debug: Check what sessions exist
                
            }
        }

        $query->when($this->search, function ($q) {
            $q->whereHas('cashier', fn($q2) => $q2->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('register', fn($q3) => $q3->where('name', 'like', "%{$this->search}%"));
        });

        $sessions = $query->orderByDesc('closed_at')->paginate(15);

        return view('cashregister::livewire.approvals-list', [
            'sessions' => $sessions,
        ])->layout('layouts.app');
    }
}


