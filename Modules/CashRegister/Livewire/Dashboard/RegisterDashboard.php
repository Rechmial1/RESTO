<?php

namespace Modules\CashRegister\Livewire\Dashboard;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;

class RegisterDashboard extends Component
{
    public int $sessionsWithDiscrepancy7Days = 0;
    public string $largestCashOutReason = '-';
    public float $largestCashOutAmount = 0.0;
    public float $avgDiscrepancy30Days = 0.0;
    public float $totalCashSalesToday = 0.0;
    public float $safeDropsToday = 0.0;
    public float $pctLargestCashOut30 = 0.0;
    public float $pctAvgDisc30 = 0.0;
    public float $pctCashToday = 0.0;
    public float $pctSafeDropToday = 0.0;

    public function mount(): void
    {
        $this->refreshMetrics();
    } 

    public function refreshMetrics(): void
    {
        $restaurantId = restaurant()->id ?? null;
        $branchId = branch()->id ?? null;
        $userId = user()->id;

        // Get the current user's open session
        $currentSession = CashRegisterSession::where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        // Do not early-return; compute stats from all of this user's sessions for today (open or closed)

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // Include cash_in along with cash_sale for today's total cash inflow - from all of the current user's sessions in this branch
        $this->totalCashSalesToday = (float) CashRegisterTransaction::query()
            ->whereBetween('happened_at', [$todayStart, $todayEnd])
            ->whereIn('type', ['cash_sale', 'cash_in'])
            ->whereHas('session', function ($q) use ($userId, $restaurantId, $branchId) {
                $q->where('opened_by', $userId)
                  ->when($restaurantId, fn($qq) => $qq->where('restaurant_id', $restaurantId))
                  ->when($branchId, fn($qq) => $qq->where('branch_id', $branchId));
            })
            ->sum('amount');

        // Get user's yesterday session for comparison
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();

        $yesterdaySession = CashRegisterSession::where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('opened_at', [$yesterdayStart, $yesterdayEnd])
            ->latest('opened_at')
            ->first();

        $yesterdayCash = (float) CashRegisterTransaction::query()
            ->whereBetween('happened_at', [$yesterdayStart, $yesterdayEnd])
            ->whereIn('type', ['cash_sale', 'cash_in'])
            ->whereHas('session', function ($q) use ($userId, $restaurantId, $branchId) {
                $q->where('opened_by', $userId)
                  ->when($restaurantId, fn($qq) => $qq->where('restaurant_id', $restaurantId))
                  ->when($branchId, fn($qq) => $qq->where('branch_id', $branchId));
            })
            ->sum('amount');

        $this->pctCashToday = $yesterdayCash > 0
            ? (($this->totalCashSalesToday - $yesterdayCash) / $yesterdayCash) * 100
            : ($this->totalCashSalesToday > 0 ? 100 : 0);

        $this->safeDropsToday = (float) CashRegisterTransaction::query()
            ->whereBetween('happened_at', [$todayStart, $todayEnd])
            ->where('type', 'safe_drop')
            ->whereHas('session', function ($q) use ($userId, $restaurantId, $branchId) {
                $q->where('opened_by', $userId)
                  ->when($restaurantId, fn($qq) => $qq->where('restaurant_id', $restaurantId))
                  ->when($branchId, fn($qq) => $qq->where('branch_id', $branchId));
            })
            ->sum('amount');

        $yesterdaySafe = (float) CashRegisterTransaction::query()
            ->whereBetween('happened_at', [$yesterdayStart, $yesterdayEnd])
            ->where('type', 'safe_drop')
            ->whereHas('session', function ($q) use ($userId, $restaurantId, $branchId) {
                $q->where('opened_by', $userId)
                  ->when($restaurantId, fn($qq) => $qq->where('restaurant_id', $restaurantId))
                  ->when($branchId, fn($qq) => $qq->where('branch_id', $branchId));
            })
            ->sum('amount');

        $this->pctSafeDropToday = $yesterdaySafe > 0
            ? (($this->safeDropsToday - $yesterdaySafe) / $yesterdaySafe) * 100
            : ($this->safeDropsToday > 0 ? 100 : 0);

        // Only count discrepancies from this user's sessions
        $this->sessionsWithDiscrepancy7Days = (int) CashRegisterSession::query()
            ->where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('closed_at', '>=', now()->subDays(7))
            ->where('status', 'closed')
            ->whereRaw('ABS(discrepancy) <> 0')
            ->count();

        $windowStart = now()->subDays(30);
        $prevWindowStart = now()->subDays(60);
        $prevWindowEnd = now()->subDays(30)->endOfDay();

        // Get user's sessions in the last 30 days
        $userSessionIds = CashRegisterSession::where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('opened_at', '>=', $windowStart)
            ->pluck('id');

        $largest = CashRegisterTransaction::query()
            ->select('reason', DB::raw('SUM(amount) as total'))
            ->whereIn('cash_register_session_id', $userSessionIds)
            ->where('type', 'cash_out')
            ->where('happened_at', '>=', $windowStart)
            ->groupBy('reason')
            ->orderByDesc('total')
            ->first();

        if ($largest) {
            $this->largestCashOutReason = $largest->reason ?: '—';
            $this->largestCashOutAmount = (float) $largest->total;
            
            // Get previous 30-day window user sessions
            $prevUserSessionIds = CashRegisterSession::where('opened_by', $userId)
                ->where('restaurant_id', $restaurantId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereBetween('opened_at', [$prevWindowStart, $prevWindowEnd])
                ->pluck('id');
                
            // Percentage change for the same reason compared to previous 30-day window
            $prevReasonTotal = (float) CashRegisterTransaction::query()
                ->whereIn('cash_register_session_id', $prevUserSessionIds)
                ->where('type', 'cash_out')
                ->whereBetween('happened_at', [$prevWindowStart, $prevWindowEnd])
                ->where('reason', $this->largestCashOutReason)
                ->sum('amount');

            $this->pctLargestCashOut30 = $prevReasonTotal > 0
                ? (($this->largestCashOutAmount - $prevReasonTotal) / $prevReasonTotal) * 100
                : ($this->largestCashOutAmount > 0 ? 100 : 0);
        } else {
            $this->largestCashOutReason = '—';
            $this->largestCashOutAmount = 0.0;
            $this->pctLargestCashOut30 = 0.0;
        }

        // Only calculate average discrepancy for this user's sessions
        $this->avgDiscrepancy30Days = (float) CashRegisterSession::query()
            ->where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('closed_at', '>=', $windowStart)
            ->where('status', 'closed')
            ->select(DB::raw('AVG(ABS(discrepancy)) as avg_disc'))
            ->value('avg_disc') ?? 0.0;

        $prevAvgDisc = (float) (CashRegisterSession::query()
            ->where('opened_by', $userId)
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereBetween('closed_at', [$prevWindowStart, $prevWindowEnd])
            ->where('status', 'closed')
            ->select(DB::raw('AVG(ABS(discrepancy)) as avg_disc'))
            ->value('avg_disc') ?? 0.0);

        $this->pctAvgDisc30 = $prevAvgDisc > 0
            ? (($this->avgDiscrepancy30Days - $prevAvgDisc) / $prevAvgDisc) * 100
            : ($this->avgDiscrepancy30Days > 0 ? 100 : 0);
    }

    public function render()
    {
        return view('cashregister::livewire.dashboard.register-dashboard');
    }
}


