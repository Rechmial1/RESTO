<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Order;
use App\Models\Tax;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TaxReportExport;

class TaxReport extends Component
{
    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $startTime = '00:00'; // Default start time
    public $endTime = '23:59';  // Default end time
    public $showDateOrdersModal = false;
    public $selectedDate = null;
    public $selectedDateOrders = [];
    public $selectedDateTotals = [
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0
    ];
    public $activeTab = 'byTaxType';

    // Cache for processed orders data
    private $cachedOrders = null;
    private $cachedOrderDetails = null;

    public function mount()
    {
        abort_if(!in_array('Report', restaurant_modules()), 403);
        abort_if((!user_can('Show Reports')), 403);

        $tz = timezone();

        // Load date range type from cookie
        $this->dateRangeType = request()->cookie('tax_report_date_range_type', 'today');
        $this->setDateRange();
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('tax_report_date_range_type', $value, 60 * 24 * 30)); // 30 days
        $this->setDateRange();
        $this->invalidateCache();
    }

    public function setDateRange()
    {
        $tz = timezone();

        switch ($this->dateRangeType) {
        case 'today':
            $this->startDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
            break;

        case 'lastWeek':
            $this->startDate = Carbon::now($tz)->subWeek()->startOfWeek()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->subWeek()->endOfWeek()->format('m/d/Y');
            break;

        case 'last7Days':
            $this->startDate = Carbon::now($tz)->subDays(7)->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
            break;

        case 'currentMonth':
            $this->startDate = Carbon::now($tz)->startOfMonth()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
            break;

        case 'lastMonth':
            $this->startDate = Carbon::now($tz)->subMonth()->startOfMonth()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->subMonth()->endOfMonth()->format('m/d/Y');
            break;

        case 'currentYear':
            $this->startDate = Carbon::now($tz)->startOfYear()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
            break;

        case 'lastYear':
            $this->startDate = Carbon::now($tz)->subYear()->startOfYear()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->subYear()->endOfYear()->format('m/d/Y');
            break;

        default:
            $this->startDate = Carbon::now($tz)->startOfWeek()->format('m/d/Y');
            $this->endDate = Carbon::now($tz)->endOfWeek()->format('m/d/Y');
            break;
        }
    }

    #[On('setStartDate')]
    public function setStartDate($start)
    {
        $this->startDate = $start;
        $this->invalidateCache();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->invalidateCache();
    }

    public function updatedStartTime($value)
    {
        $this->invalidateCache();
    }

    public function updatedEndTime($value)
    {
        $this->invalidateCache();
    }

    /**
     * Invalidate cached data when filters change
     */
    private function invalidateCache()
    {
        $this->cachedOrders = null;
        $this->cachedOrderDetails = null;
    }

    public function exportReport($exportType)
    {
        if (!in_array('Export Report', restaurant_modules())) {
            $this->dispatch('showUpgradeLicense');
            return;
        }

        $dateTimeData = $this->prepareDateTimeData();
        $data = [];

        // Use cached data if available, otherwise process fresh
        $orderDetails = $this->getOrderDetails();

        switch ($exportType) {
            case 'byTaxType':
                $data = array_values($this->buildTaxBreakdownFromOrderDetails($orderDetails));
                break;
            case 'byDate':
                $data = array_values($this->buildTaxByDateFromOrderDetails($orderDetails, $dateTimeData['timezone']));
                break;
            case 'byOrder':
                $data = $orderDetails;
                break;
        }

        $formattedStartTime = Carbon::createFromFormat('H:i', $this->startTime)->format('h:i A');
        $formattedEndTime = Carbon::createFromFormat('H:i', $this->endTime)->format('h:i A');

        return Excel::download(
            new TaxReportExport(
                $exportType,
                $data,
                $this->startDate,
                $this->endDate,
                $formattedStartTime,
                $formattedEndTime,
                $dateTimeData['timezone']
            ),
            'tax-report-' . $exportType . '-' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Build base query for orders with time filtering
     */
    private function buildOrdersQuery($dateTimeData, $withRelations = ['items', 'taxes.tax'])
    {
        $offset = Carbon::now($dateTimeData['timezone'])->format('P');

        $query = Order::with($withRelations)
            ->where('branch_id', branch()->id)
            ->where('status', 'paid')
            ->where(function($q) {
                $q->whereNull('orders.invoice_type')
                ->orWhere('orders.invoice_type', '!=', 'FA');
            })
            ->whereBetween('date_time', [$dateTimeData['startDateTime'], $dateTimeData['endDateTime']])
            ->where(function ($q) use ($dateTimeData, $offset) {
                if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
                    $q->whereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) BETWEEN ? AND ?", [$offset, $dateTimeData['startTime'], $dateTimeData['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dateTimeData, $offset) {
                        $sub->whereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) >= ?", [$offset, $dateTimeData['startTime']])
                            ->orWhereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) <= ?", [$offset, $dateTimeData['endTime']]);
                    });
                }
            });

        return $query;
    }

    /**
     * Process item-level taxes and return breakdown
     */
    private function processItemTaxes($item, &$orderTaxAmount, &$orderTaxBreakdown, $order = null, $includeItemDetails = false)
    {
        if ($item->tax_amount <= 0) {
            return [];
        }

        $orderTaxAmount += $item->tax_amount;
        $itemTaxDetails = [];
        $taxBreakup = $item->tax_breakup ? json_decode($item->tax_breakup, true) : null;

        if ($taxBreakup && is_array($taxBreakup)) {
            foreach ($taxBreakup as $taxName => $taxInfo) {
                $taxAmount = is_array($taxInfo) ? round(($taxInfo['amount'] ?? 0) * $item->quantity, 2) : 0;
                $taxPercent = is_array($taxInfo) ? ($taxInfo['percent'] ?? 0) : 0;

                if (!isset($orderTaxBreakdown[$taxName])) {
                    $orderTaxBreakdown[$taxName] = [
                        'name' => $taxName,
                        'percent' => $taxPercent,
                        'amount' => 0
                    ];
                }
                $orderTaxBreakdown[$taxName]['amount'] = round($orderTaxBreakdown[$taxName]['amount'] + $taxAmount, 2);

                // For item tax details
                if ($includeItemDetails && $order && (isset($item->menuItem) || isset($item->menuItemVariation))) {
                    $itemTaxDetails[] = [
                        'order_number' => $order->order_number,
                        'order_date' => $order->date_time,
                        'item_name' => $item->menuItem->name ?? ($item->menuItemVariation->name ?? 'N/A'),
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'amount' => $item->amount,
                        'tax_name' => $taxName,
                        'tax_percent' => $taxPercent,
                        'tax_amount' => $taxAmount
                    ];
                }
            }
        } else {
            $taxPercent = $item->tax_percentage ?? 0;
            $taxName = 'Tax (' . number_format($taxPercent, 2) . '%)';

            if (!isset($orderTaxBreakdown[$taxName])) {
                $orderTaxBreakdown[$taxName] = [
                    'name' => $taxName,
                    'percent' => $taxPercent,
                    'amount' => 0
                ];
            }
            $orderTaxBreakdown[$taxName]['amount'] = round($orderTaxBreakdown[$taxName]['amount'] + $item->tax_amount, 2);

            // For item tax details
            if ($includeItemDetails && $order && (isset($item->menuItem) || isset($item->menuItemVariation))) {
                $itemTaxDetails[] = [
                    'order_number' => $order->order_number,
                    'order_date' => $order->date_time,
                    'item_name' => $item->menuItem->name ?? ($item->menuItemVariation->name ?? 'N/A'),
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'amount' => $item->amount,
                    'tax_name' => $taxName,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $item->tax_amount
                ];
            }
        }

        return $itemTaxDetails;
    }

    /**
     * Process order-level taxes
     */
    private function processOrderTaxes($order, &$orderTaxAmount, &$orderTaxBreakdown)
    {
        foreach ($order->taxes as $orderTax) {
            $tax = $orderTax->tax;

            // Skip if tax relationship is missing
            if (!$tax) {
                continue;
            }

            $taxName = $tax->tax_name;
            $taxPercent = $tax->tax_percent;

            $subtotal = $order->sub_total ?? 0;
            $discountAmount = $order->discount_amount ?? 0;
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = round(($taxPercent / 100) * $taxableAmount, 2);

            $orderTaxAmount += $taxAmount;

            if (!isset($orderTaxBreakdown[$taxName])) {
                $orderTaxBreakdown[$taxName] = [
                    'name' => $taxName,
                    'percent' => $taxPercent,
                    'amount' => 0
                ];
            }
            $orderTaxBreakdown[$taxName]['amount'] = round($orderTaxBreakdown[$taxName]['amount'] + $taxAmount, 2);
        }
    }

    /**
     * Get processed order details (cached)
     */
    private function getOrderDetails()
    {
        if ($this->cachedOrderDetails !== null) {
            return $this->cachedOrderDetails;
        }

        $dateTimeData = $this->prepareDateTimeData();
        $orders = $this->buildOrdersQuery($dateTimeData, ['items.menuItem', 'items.menuItemVariation', 'taxes.tax'])
            ->orderBy('date_time', 'desc')
            ->get();

        $this->cachedOrders = $orders;
        $orderDetails = [];

        foreach ($orders as $order) {
            $orderTaxAmount = 0;
            $orderTaxBreakdown = [];

            // Process item-level taxes
            foreach ($order->items as $item) {
                $this->processItemTaxes($item, $orderTaxAmount, $orderTaxBreakdown, $order, false);
            }

            // Process order-level taxes
            $this->processOrderTaxes($order, $orderTaxAmount, $orderTaxBreakdown);

            // Fallback to total_tax_amount if no tax calculated
            if ($orderTaxAmount == 0 && isset($order->total_tax_amount) && $order->total_tax_amount > 0) {
                $orderTaxAmount = $order->total_tax_amount;
            }

            // Round order tax amount to 2 decimal places
            $orderTaxAmount = round($orderTaxAmount, 2);

            $orderDetails[] = [
                'order' => $order,
                'tax_amount' => $orderTaxAmount,
                'tax_breakdown' => $orderTaxBreakdown,
                'subtotal' => $order->sub_total ?? 0,
                'total' => $order->total ?? 0
            ];
        }

        $this->cachedOrderDetails = $orderDetails;
        return $orderDetails;
    }

    /**
     * Build tax breakdown from order details
     */
    private function buildTaxBreakdownFromOrderDetails($orderDetails)
    {
        $taxBreakdown = [];

        foreach ($orderDetails as $orderDetail) {
            $orderTaxBreakdown = $orderDetail['tax_breakdown'];

            foreach ($orderTaxBreakdown as $taxName => $taxInfo) {
                if (!isset($taxBreakdown[$taxName])) {
                    $taxBreakdown[$taxName] = [
                        'name' => $taxName,
                        'percent' => $taxInfo['percent'],
                        'total_amount' => 0
                    ];
                }

                $taxBreakdown[$taxName]['total_amount'] = round($taxBreakdown[$taxName]['total_amount'] + $taxInfo['amount'], 2);
            }
        }

        return $taxBreakdown;
    }

    /**
     * Build tax by date from order details
     */
    private function buildTaxByDateFromOrderDetails($orderDetails, $timezone)
    {
        $taxByDate = [];

        foreach ($orderDetails as $orderDetail) {
            $order = $orderDetail['order'];
            $orderDate = Carbon::parse($order->date_time, 'UTC')
                ->setTimezone($timezone)
                ->format('Y-m-d');

            if (!isset($taxByDate[$orderDate])) {
                $taxByDate[$orderDate] = [
                    'date' => $orderDate,
                    'formatted_date' => Carbon::parse($order->date_time, 'UTC')
                        ->setTimezone($timezone)
                        ->format('M d, Y'),
                    'total_tax' => 0,
                    'total_revenue' => 0,
                    'total_orders' => 0,
                    'total_items' => 0,
                    'tax_breakdown' => []
                ];
            }

            $taxByDate[$orderDate]['total_tax'] = round($taxByDate[$orderDate]['total_tax'] + $orderDetail['tax_amount'], 2);
            $taxByDate[$orderDate]['total_revenue'] = round($taxByDate[$orderDate]['total_revenue'] + $orderDetail['total'], 2);
            $taxByDate[$orderDate]['total_orders'] += 1;
            $taxByDate[$orderDate]['total_items'] += $order->items->sum('quantity');

            foreach ($orderDetail['tax_breakdown'] as $taxName => $taxInfo) {
                if (!isset($taxByDate[$orderDate]['tax_breakdown'][$taxName])) {
                    $taxByDate[$orderDate]['tax_breakdown'][$taxName] = [
                        'name' => $taxName,
                        'percent' => $taxInfo['percent'],
                        'amount' => 0
                    ];
                }
                $taxByDate[$orderDate]['tax_breakdown'][$taxName]['amount'] = round($taxByDate[$orderDate]['tax_breakdown'][$taxName]['amount'] + $taxInfo['amount'], 2);
            }
        }

        krsort($taxByDate);
        return $taxByDate;
    }

    /**
     * Get item tax details from order details
     */
    private function getItemTaxDetailsFromOrderDetails($orderDetails)
    {
        $itemTaxDetails = [];

        foreach ($orderDetails as $orderDetail) {
            $order = $orderDetail['order'];

            foreach ($order->items as $item) {
                if ($item->tax_amount > 0) {
                    $dummyAmount = 0;
                    $dummyBreakdown = [];
                    $itemTaxDetails = array_merge(
                        $itemTaxDetails,
                        $this->processItemTaxes($item, $dummyAmount, $dummyBreakdown, $order, true)
                    );
                }
            }
        }

        return $itemTaxDetails;
    }

    public function openDateOrdersModal($date)
    {
        $this->selectedDate = $date;
        $this->loadDateOrders($date);
        $this->showDateOrdersModal = true;
    }

    public function closeDateOrdersModal()
    {
        $this->showDateOrdersModal = false;
        $this->selectedDate = null;
        $this->selectedDateOrders = [];
        $this->selectedDateTotals = [
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0
        ];
    }

    private function loadDateOrders($date)
    {
        $dateTimeData = $this->prepareDateTimeData();
        $offset = Carbon::now($dateTimeData['timezone'])->format('P');

        // Parse the date and create date range for that specific date
        $dateCarbon = Carbon::createFromFormat('Y-m-d', $date, $dateTimeData['timezone']);
        $startDateTime = $dateCarbon->copy()->setTimeFromTimeString($this->startTime ?: '00:00')->setTimezone('UTC')->toDateTimeString();
        $endDateTime = $dateCarbon->copy()->setTimeFromTimeString($this->endTime ?: '23:59')->setTimezone('UTC')->toDateTimeString();

        // Get orders for this specific date
        $orders = Order::with(['items.menuItem', 'items.menuItemVariation', 'taxes.tax'])
            ->where('branch_id', branch()->id)
            ->where('status', 'paid')
            ->where(function($q) {
                $q->whereNull('orders.invoice_type')
                ->orWhere('orders.invoice_type', '!=', 'FA');
            })
            ->whereBetween('date_time', [$startDateTime, $endDateTime])
            ->where(function ($q) use ($dateTimeData, $offset) {
                if ($dateTimeData['startTime'] < $dateTimeData['endTime']) {
                    $q->whereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) BETWEEN ? AND ?", [$offset, $dateTimeData['startTime'], $dateTimeData['endTime']]);
                } else {
                    $q->where(function ($sub) use ($dateTimeData, $offset) {
                        $sub->whereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) >= ?", [$offset, $dateTimeData['startTime']])
                            ->orWhereRaw("TIME(CONVERT_TZ(date_time, '+00:00', ?)) <= ?", [$offset, $dateTimeData['endTime']]);
                    });
                }
            })
            ->orderBy('date_time', 'desc')
            ->get();

        // Process orders using optimized methods
        $this->selectedDateOrders = [];
        foreach ($orders as $order) {
            $orderTaxAmount = 0;
            $orderTaxBreakdown = [];

            // Process item-level taxes
            foreach ($order->items as $item) {
                $this->processItemTaxes($item, $orderTaxAmount, $orderTaxBreakdown, $order, false);
            }

            // Process order-level taxes
            $this->processOrderTaxes($order, $orderTaxAmount, $orderTaxBreakdown);

            // Fallback to total_tax_amount if no tax calculated
            if ($orderTaxAmount == 0 && isset($order->total_tax_amount) && $order->total_tax_amount > 0) {
                $orderTaxAmount = $order->total_tax_amount;
            }

            // Round order tax amount to 2 decimal places
            $orderTaxAmount = round($orderTaxAmount, 2);

            $this->selectedDateOrders[] = [
                'order' => $order,
                'tax_amount' => $orderTaxAmount,
                'tax_breakdown' => $orderTaxBreakdown,
                'subtotal' => $order->sub_total ?? 0,
                'total' => $order->total ?? 0
            ];
        }

        // Calculate totals efficiently
        $this->selectedDateTotals = [
            'subtotal' => array_sum(array_column($this->selectedDateOrders, 'subtotal')),
            'tax' => array_sum(array_column($this->selectedDateOrders, 'tax_amount')),
            'total' => array_sum(array_column($this->selectedDateOrders, 'total'))
        ];
    }

    private function prepareDateTimeData()
    {
        $timezone = timezone();

        // Ensure time is in H:i format
        $startTimeStr = $this->startTime ?: '00:00';
        $endTimeStr = $this->endTime ?: '23:59';

        // Parse dates and times
        $startDateCarbon = Carbon::createFromFormat('m/d/Y', $this->startDate, $timezone);
        $endDateCarbon = Carbon::createFromFormat('m/d/Y', $this->endDate, $timezone);

        // Set times on the dates
        $startDateTime = $startDateCarbon->copy()->setTimeFromTimeString($startTimeStr)->setTimezone('UTC')->toDateTimeString();
        $endDateTime = $endDateCarbon->copy()->setTimeFromTimeString($endTimeStr)->setTimezone('UTC')->toDateTimeString();

        // Keep times in restaurant timezone for SQL TIME() comparison (we'll convert datetime in SQL)
        $startTime = $startTimeStr;
        $endTime = $endTimeStr;

        return compact('timezone', 'startDateTime', 'endDateTime', 'startTime', 'endTime');
    }

    public function render()
    {
        $dateTimeData = $this->prepareDateTimeData();
        $tz = timezone();

        // Get processed order details (this caches the result)
        $orderDetails = $this->getOrderDetails();
        $orders = $this->cachedOrders;

        // Get today's orders for today's summary
        $todayStart = Carbon::now($tz)->startOfDay()->setTimezone('UTC')->toDateTimeString();
        $todayEnd = Carbon::now($tz)->endOfDay()->setTimezone('UTC')->toDateTimeString();

        $todayOrders = Order::with(['items', 'taxes.tax'])
            ->where('branch_id', branch()->id)
            ->where('status', 'paid')
            ->where(function($q) {
                $q->whereNull('orders.invoice_type')
                ->orWhere('orders.invoice_type', '!=', 'FA');
            })
            ->whereBetween('date_time', [$todayStart, $todayEnd])
            ->get();

        // Get all taxes
        $allTaxes = Tax::where('restaurant_id', restaurant()->id)->get();

        // Build tax breakdown from order details
        $taxBreakdown = $this->buildTaxBreakdownFromOrderDetails($orderDetails);

        // Get item tax details
        $itemTaxDetails = $this->getItemTaxDetailsFromOrderDetails($orderDetails);

        // Calculate overall totals efficiently - sum from breakdown to ensure accuracy
        $totalTax = 0;
        foreach ($taxBreakdown as $tax) {
            $totalTax += $tax['total_amount'];
        }
        $totalTax = round($totalTax, 2);
        $totalAmount = round(array_sum(array_column($orderDetails, 'total')), 2);
        $totalOrders = count($orderDetails);
        $totalItems = $orders->sum(function($order) {
            return $order->items->sum('quantity');
        });

        // Calculate today's tax summary
        $todayTaxTotal = 0;
        foreach ($todayOrders as $todayOrder) {
            $orderTax = $todayOrder->items->sum('tax_amount') ?? 0;

            foreach ($todayOrder->taxes as $orderTaxRelation) {
                $tax = $orderTaxRelation->tax;

                // Skip if tax relationship is missing
                if (!$tax) {
                    continue;
                }

                $subtotal = $todayOrder->sub_total ?? 0;
                $discountAmount = $todayOrder->discount_amount ?? 0;
                $taxableAmount = $subtotal - $discountAmount;
                $orderTax += round(($tax->tax_percent / 100) * $taxableAmount, 2);
            }

            if ($orderTax == 0 && isset($todayOrder->total_tax_amount) && $todayOrder->total_tax_amount > 0) {
                $orderTax = $todayOrder->total_tax_amount;
            }

            $todayTaxTotal = round($todayTaxTotal + $orderTax, 2);
        }
        $todayOrdersCount = $todayOrders->count();
        $todayRevenue = $todayOrders->sum('total');

        // Build tax by date from order details
        $taxByDate = $this->buildTaxByDateFromOrderDetails($orderDetails, $dateTimeData['timezone']);

        return view('livewire.reports.tax-report', [
            'orders' => $orders,
            'orderDetails' => $orderDetails,
            'itemTaxDetails' => $itemTaxDetails,
            'taxBreakdown' => $taxBreakdown,
            'totalTax' => $totalTax,
            'totalAmount' => $totalAmount,
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'todayTaxTotal' => $todayTaxTotal,
            'todayOrdersCount' => $todayOrdersCount,
            'todayRevenue' => $todayRevenue,
            'allTaxes' => $allTaxes,
            'taxByDate' => $taxByDate,
        ]);
    }

}

