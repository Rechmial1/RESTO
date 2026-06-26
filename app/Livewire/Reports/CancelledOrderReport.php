<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\User;
use App\Models\KotCancelReason;
use App\Scopes\BranchScope;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use App\Exports\CancelledOrderReportExport;
use Maatwebsite\Excel\Facades\Excel;

class CancelledOrderReport extends Component
{
    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $currencyId;
    public $selectedCancelReason = '';
    public $selectedCancelledBy = '';
    public $cancelReasons = [];
    public $users = [];
    public $cancelledOrders = [];
    public $totalCancelledAmount = 0;
    public $totalCancelledOrders = 0;

    public function mount()
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $this->currencyId = restaurant()->currency_id;
        $this->dateRangeType = request()->cookie('cancelled_order_report_date_range_type', 'currentWeek');
        $this->setDateRange();

        // Load cancellation reasons
        $this->cancelReasons = KotCancelReason::where('restaurant_id', restaurant()->id)->get();

        // Load all users for the restaurant (current branch and restaurant-level users)
        $this->users = User::withoutGlobalScope(BranchScope::class)
            ->where(function($q) {
                return $q->where('branch_id', branch()->id)
                    ->orWhereNull('branch_id');
            })
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    public function setDateRange()
    {
        $tz = timezone();

        $ranges = [
            'today' => [Carbon::now($tz)->startOfDay(), Carbon::now($tz)->endOfDay()],
            'lastWeek' => [Carbon::now($tz)->subWeek()->startOfWeek(), Carbon::now($tz)->subWeek()->endOfWeek()],
            'last7Days' => [Carbon::now($tz)->subDays(7), Carbon::now($tz)->endOfDay()],
            'currentMonth' => [Carbon::now($tz)->startOfMonth(), Carbon::now($tz)->endOfDay()],
            'lastMonth' => [Carbon::now($tz)->subMonth()->startOfMonth(), Carbon::now($tz)->subMonth()->endOfMonth()],
            'currentYear' => [Carbon::now($tz)->startOfYear(), Carbon::now($tz)->endOfDay()],
            'lastYear' => [Carbon::now($tz)->subYear()->startOfYear(), Carbon::now($tz)->subYear()->endOfYear()],
            'currentWeek' => [Carbon::now($tz)->startOfWeek(), Carbon::now($tz)->endOfWeek()],
        ];

        [$start, $end] = $ranges[$this->dateRangeType] ?? $ranges['currentWeek'];
        $this->startDate = $start->format('m/d/Y');
        $this->endDate = $end->format('m/d/Y');
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('cancelled_order_report_date_range_type', $value, 60 * 24 * 30));
        $this->setDateRange();
    }

    private function prepareDateData()
    {
        $timezone = timezone();
        $offset = Carbon::now($timezone)->format('P');

        $startDateTime = Carbon::createFromFormat('m/d/Y', $this->startDate, $timezone)
            ->startOfDay()
            ->setTimezone('UTC')->toDateTimeString();

        $endDateTime = Carbon::createFromFormat('m/d/Y', $this->endDate, $timezone)
            ->endOfDay()
            ->setTimezone('UTC')->toDateTimeString();

        return compact('timezone', 'offset', 'startDateTime', 'endDateTime');
    }

    public function exportReport()
    {
        if (!in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');
            return;
        }

        $dateData = $this->prepareDateData();

        return Excel::download(
            new CancelledOrderReportExport(
                $dateData['startDateTime'],
                $dateData['endDateTime'],
                $dateData['timezone'],
                $dateData['offset'],
                $this->selectedCancelReason,
                $this->selectedCancelledBy,
                $this->currencyId
            ),
            'cancelled-order-report-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function render()
    {
        $dateData = $this->prepareDateData();

        // Query cancelled orders
        $query = Order::with(['customer', 'cancelReason', 'cancelledBy', 'table', 'waiter.roles'])
            ->where('status', 'canceled')
            ->where('order_status', 'cancelled')
            ->whereBetween('updated_at', [$dateData['startDateTime'], $dateData['endDateTime']]);

        // Filter by cancellation reason
        if ($this->selectedCancelReason) {
            $query->where('cancel_reason_id', $this->selectedCancelReason);
        }

        // Filter by who cancelled
        if ($this->selectedCancelledBy) {
            $query->where('cancelled_by', $this->selectedCancelledBy);
        }

        $cancelledOrders = $query->orderBy('updated_at', 'desc')->get();
        $this->cancelledOrders = $cancelledOrders;
        $this->totalCancelledOrders = $cancelledOrders->count();
        $this->totalCancelledAmount = $cancelledOrders->sum('total');

        // Calculate top 3 cancelled reasons
        $reasonCounts = [];
        foreach ($cancelledOrders as $order) {
            if ($order->cancelReason) {
                $reasonId = $order->cancelReason->id;
                $reasonName = $order->cancelReason->reason;
                if (!isset($reasonCounts[$reasonId])) {
                    $reasonCounts[$reasonId] = [
                        'name' => $reasonName,
                        'count' => 0
                    ];
                }
                $reasonCounts[$reasonId]['count']++;
            } elseif ($order->cancel_reason_text) {
                // Handle custom reasons
                $customKey = 'custom_' . md5($order->cancel_reason_text);
                if (!isset($reasonCounts[$customKey])) {
                    $reasonCounts[$customKey] = [
                        'name' => $order->cancel_reason_text,
                        'count' => 0
                    ];
                }
                $reasonCounts[$customKey]['count']++;
            }
        }

        // Get top 3 reasons sorted by count (descending)
        $topCancelledReasons = collect($reasonCounts)
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->toArray();

        return view('livewire.reports.cancelled-order-report', [
            'cancelledOrders' => $this->cancelledOrders,
            'totalCancelledOrders' => $this->totalCancelledOrders,
            'totalCancelledAmount' => $this->totalCancelledAmount,
            'currencyId' => $this->currencyId,
            'cancelReasons' => $this->cancelReasons,
            'users' => $this->users,
            'selectedCancelReason' => $this->selectedCancelReason,
            'selectedCancelledBy' => $this->selectedCancelledBy,
            'topCancelledReasons' => $topCancelledReasons,
        ]);
    }
}

