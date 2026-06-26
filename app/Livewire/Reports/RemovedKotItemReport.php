<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\KotItem;
use App\Models\KotCancelReason;
use App\Models\User;
use App\Scopes\BranchScope;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Exports\RemovedKotItemReportExport;
use Maatwebsite\Excel\Facades\Excel;

class RemovedKotItemReport extends Component
{
    public $dateRangeType = 'currentWeek';
    public $startDate;
    public $endDate;
    public $currencyId;
    public $selectedCancelReason = '';
    public $selectedWaiter = '';
    public $cancelReasons = [];
    public $users = [];
    public $removedKotItems = [];
    public $totalRemovedItems = 0;
    public $totalRemovedAmount = 0;

    public function mount()
    {
        abort_unless(in_array('Report', restaurant_modules()), 403);
        abort_unless(user_can('Show Reports'), 403);

        $this->currencyId = restaurant()->currency_id;
        $this->dateRangeType = request()->cookie('removed_kot_item_report_date_range_type', 'currentWeek');
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
        cookie()->queue(cookie('removed_kot_item_report_date_range_type', $value, 60 * 24 * 30));
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
            new RemovedKotItemReportExport(
                $dateData['startDateTime'],
                $dateData['endDateTime'],
                $dateData['timezone'],
                $dateData['offset'],
                $this->selectedCancelReason,
                $this->selectedWaiter,
                $this->currencyId
            ),
            'removed-kot-item-report-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function render()
    {
        $dateData = $this->prepareDateData();

        // Query cancelled/removed KOT items
        // Use withoutGlobalScopes to ensure we get all items even if menuItem is unavailable
        $query = KotItem::withoutGlobalScopes()
            ->with([
                'menuItem' => function($q) {
                    $q->withoutGlobalScopes();
                },
                'menuItemVariation',
                'kot.order.customer',
                'kot.order.table',
                'kot.order.waiter',
                'kot.table',
                'kot',
                'cancelReason',
                'cancelledBy',
                'orderItem' => function($q) {
                    $q->with(['menuItem' => function($subQ) {
                        $subQ->withoutGlobalScopes();
                    }]);
                }
            ])
            ->where('status', 'cancelled')
            ->where(function($q) {
                // Only show items that were intentionally removed (have a cancel reason)
                $q->whereNotNull('cancel_reason_id')
                  ->orWhereNotNull('cancel_reason_text');
            })
            ->whereHas('kot.order', function ($q) {
                $q->where('branch_id', branch()->id);
            })
            ->whereBetween('updated_at', [$dateData['startDateTime'], $dateData['endDateTime']]);

        // Filter by cancellation reason
        if ($this->selectedCancelReason) {
            $query->where('cancel_reason_id', $this->selectedCancelReason);
        }

        // Filter by waiter
        if ($this->selectedWaiter) {
            $query->whereHas('kot.order', function ($q) {
                $q->where('waiter_id', $this->selectedWaiter);
            });
        }

        $removedKotItems = $query->orderBy('updated_at', 'desc')->get();
        $this->removedKotItems = $removedKotItems;
        $this->totalRemovedItems = $removedKotItems->count();

        // Calculate total amount (price * quantity)
        $this->totalRemovedAmount = $removedKotItems->sum(function ($item) {
            // Try to get price from menuItemVariation, then menuItem, then orderItem
            $price = 0;
            if ($item->menuItemVariation) {
                $price = $item->menuItemVariation->price ?? 0;
            } elseif ($item->menuItem) {
                $price = $item->menuItem->price ?? 0;
            } elseif ($item->orderItem && $item->orderItem->menuItem) {
                $price = $item->orderItem->menuItem->price ?? 0;
            }
            return $price * $item->quantity;
        });

        // Calculate top 3 cancellation reasons
        $reasonCounts = [];
        foreach ($removedKotItems as $item) {
            if ($item->cancelReason) {
                $reasonId = $item->cancelReason->id;
                $reasonName = $item->cancelReason->reason;
                if (!isset($reasonCounts[$reasonId])) {
                    $reasonCounts[$reasonId] = [
                        'name' => $reasonName,
                        'count' => 0
                    ];
                }
                $reasonCounts[$reasonId]['count']++;
            } elseif ($item->cancel_reason_text) {
                // Handle custom reasons
                $customKey = 'custom_' . md5($item->cancel_reason_text);
                if (!isset($reasonCounts[$customKey])) {
                    $reasonCounts[$customKey] = [
                        'name' => $item->cancel_reason_text,
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

        // Calculate voided items by waiter
        $waiterCounts = [];
        foreach ($removedKotItems as $item) {
            if ($item->kot && $item->kot->order && $item->kot->order->waiter) {
                $waiterId = $item->kot->order->waiter->id;
                $waiterName = $item->kot->order->waiter->name;
                if (!isset($waiterCounts[$waiterId])) {
                    $waiterCounts[$waiterId] = [
                        'name' => $waiterName,
                        'count' => 0,
                        'amount' => 0
                    ];
                }
                $waiterCounts[$waiterId]['count']++;
                // Try to get price from menuItemVariation, then menuItem, then orderItem
                $price = 0;
                if ($item->menuItemVariation) {
                    $price = $item->menuItemVariation->price ?? 0;
                } elseif ($item->menuItem) {
                    $price = $item->menuItem->price ?? 0;
                } elseif ($item->orderItem && $item->orderItem->menuItem) {
                    $price = $item->orderItem->menuItem->price ?? 0;
                }
                $waiterCounts[$waiterId]['amount'] += $price * $item->quantity;
            }
        }

        // Get top 3 waiters by void count
        $topWaiters = collect($waiterCounts)
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->toArray();

        return view('livewire.reports.removed-kot-item-report', [
            'removedKotItems' => $this->removedKotItems,
            'totalRemovedItems' => $this->totalRemovedItems,
            'totalRemovedAmount' => $this->totalRemovedAmount,
            'currencyId' => $this->currencyId,
            'cancelReasons' => $this->cancelReasons,
            'users' => $this->users,
            'selectedCancelReason' => $this->selectedCancelReason,
            'selectedWaiter' => $this->selectedWaiter,
            'topCancelledReasons' => $topCancelledReasons,
            'topWaiters' => $topWaiters,
        ]);
    }
}

