<?php

namespace App\Livewire\Order;

use App\Models\Order;
use App\Models\User;
use App\Models\ReceiptSetting;
use App\Models\KotCancelReason;
use App\Models\PusherSetting;
use App\Models\DeliveryPlatform;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Orders extends Component
{

    use LivewireAlert;
    use WithPagination;

    protected $listeners = ['refreshOrders' => '$refresh'];

    public $orderID;
    public $filterOrders;
    public $dateRangeType;
    public $startDate;
    public $endDate;
    public $receiptSettings;
    public $waiters;
    public $filterWaiter;
    public $pollingEnabled = true;
    public $pollingInterval = 10;
    public $filterOrderType = '';
    public $deliveryApps;
    public $filterDeliveryApp = '';
    public $cancelReasons;
    public $selectedCancelReason;
    public $cancelComment;
    public $perPage = 20;
    public $hasMore = false;
    public $isLoadingMore = false;

    public function mount()
    {
        $tz = timezone();

        // Load date range type from cookie
        $this->dateRangeType = request()->cookie('orders_date_range_type', 'today');
        $this->startDate = Carbon::now($tz)->startOfWeek()->format('m/d/Y');
        $this->endDate = Carbon::now($tz)->endOfWeek()->format('m/d/Y');
        $this->waiters = cache()->remember('waiters_' . restaurant()->id, 60 * 60 * 24, function () {
            return User::role('Waiter_' . restaurant()->id)->get();
        });
        $this->deliveryApps = DeliveryPlatform::all();

        // Load polling settings from cookies
        $this->pollingEnabled = filter_var(request()->cookie('orders_polling_enabled', 'true'), FILTER_VALIDATE_BOOLEAN);
        $this->pollingInterval = (int)request()->cookie('orders_polling_interval', 10);


        if (!is_null($this->orderID)) {
            $this->dispatch('showOrderDetail', id: $this->orderID);
        }

        $this->setDateRange();
        $this->cancelReasons = KotCancelReason::where('cancel_order', true)->get();

        if (user()->hasRole('Waiter_' . user()->restaurant_id)) {
            $this->filterWaiter = user()->id;
        }
    }

    private function resetPerPage(): void
    {
        $this->perPage = 20;
    }

    #[On('newOrderCreated')]
    public function handleNewOrder($data = null)
    {
        $this->showNewOrderNotification();
    }

    #[On('viewOrder')]
    public function viewOrder($data)
    {

        if (is_array($data) && isset($data['orderID'])) {
            $orderId = $data['orderID'];
            $url = route('pos.kot', [$orderId]) . '?show-order-detail=true';
            $this->js("window.location.href = '{$url}'");
            return;
        }

        Log::warning('viewOrder: Invalid data format', ['data' => $data]);
    }

    /**
     * Show notification for new orders
     */
    private function showNewOrderNotification()
    {
        $recentOrder = Order::with('table', 'customer')
            ->where('status', '<>', 'draft')
            ->whereNotNull('order_number')
            ->orderBy(DB::raw('CAST(order_number AS UNSIGNED)'), 'desc')
            ->first();

        if ($recentOrder) {
            // Build order description
            $orderDescription = __('modules.order.newOrderReceived') . ': ' . $recentOrder->show_formatted_order_number;

            // Add table info if it exists
            if ($recentOrder->table && $recentOrder->table->table_code) {
                $orderDescription .= ' - ' . __('modules.table.table') . ': ' . $recentOrder->table->table_code;
            }
            // Add customer info for delivery/pickup orders
            else if ($recentOrder->customer && $recentOrder->customer->name) {
                $orderDescription .= ' - ' . $recentOrder->customer->name;
            }

            // Add order type
            if ($recentOrder->order_type) {
                $orderType = __('modules.order.' . $recentOrder->order_type);
                $orderDescription .= ' (' . $orderType . ')';
            }

            $this->confirm($orderDescription, [
                'position' => 'center',
                'confirmButtonText' => __('modules.order.viewOrder'),
                'confirmButtonColor' => '#16a34a',
                'onConfirmed' => 'viewOrder',
                'showCancelButton' => true,
                'cancelButtonText' => __('app.close'),
                'data' => [
                    'orderID' => $recentOrder->id
                ]
            ]);
        }

        // Mark notification as shown in session
        session()->put('new_order_notification_pending', false);
    }

    public function refreshNewOrders()
    {
        $this->dispatch('$refresh');
    }

    private function getOrdersCount()
    {
        $tz = timezone();

        $start = Carbon::createFromFormat('m/d/Y', $this->startDate, $tz)
            ->startOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        $end = Carbon::createFromFormat('m/d/Y', $this->endDate, $tz)
            ->endOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        return Order::where('orders.date_time', '>=', $start)
            ->where('orders.date_time', '<=', $end)
            ->count();
    }

    public function updatedDateRangeType($value)
    {
        cookie()->queue(cookie('orders_date_range_type', $value, 60 * 24 * 30)); // 30 days
        $this->resetPerPage();
    }

    public function updatedPollingEnabled($value)
    {
        cookie()->queue(cookie('orders_polling_enabled', $value ? 'true' : 'false', 60 * 24 * 30)); // 30 days
    }

    public function updatedPollingInterval($value)
    {
        cookie()->queue(cookie('orders_polling_interval', (int)$value, 60 * 24 * 30)); // 30 days
    }

    public function updatedFilterOrders(): void
    {
        $this->resetPerPage();
    }

    public function updatedFilterOrderType(): void
    {
        $this->resetPerPage();
    }

    public function updatedFilterDeliveryApp(): void
    {
        $this->resetPerPage();
    }

    public function updatedFilterWaiter(): void
    {
        $this->resetPerPage();
    }

    public function setDateRange()
    {
        $tz = timezone();

        switch ($this->dateRangeType) {
            case 'today':
                $this->startDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
                $this->endDate = Carbon::now($tz)->startOfDay()->format('m/d/Y');
                break;

            case 'currentWeek':
                $this->startDate = Carbon::now($tz)->startOfWeek()->format('m/d/Y');
                $this->endDate = Carbon::now($tz)->endOfWeek()->format('m/d/Y');
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
                $this->endDate = Carbon::now($tz)->endOfMonth()->format('m/d/Y');
                break;

            case 'lastMonth':
                $this->startDate = Carbon::now($tz)->subMonth()->startOfMonth()->format('m/d/Y');
                $this->endDate = Carbon::now($tz)->subMonth()->endOfMonth()->format('m/d/Y');
                break;

            case 'currentYear':
                $this->startDate = Carbon::now($tz)->startOfYear()->format('m/d/Y');
                $this->endDate = Carbon::now($tz)->endOfYear()->format('m/d/Y');
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
        $this->resetPerPage();
    }

    #[On('setEndDate')]
    public function setEndDate($end)
    {
        $this->endDate = $end;
        $this->resetPerPage();
    }

    public function showTableOrderDetail($id)
    {
        return $this->redirect(route('pos.order', [$id]), navigate: true);
    }

    public function confirmCancelOrder()
    {
        // Validate that a cancel reason is provided
        if (!$this->selectedCancelReason && !$this->cancelComment) {
            $this->dispatchBrowserEvent('orderCancelled', ['message' => __('modules.settings.cancelReasonRequired'), 'type' => 'error']);
            return;
        }

        $order = Order::find($this->orderID);
        $order->status = 'cancelled';
        $order->cancel_reason_id = $this->selectedCancelReason;
        $order->cancel_comment = $this->cancelComment;
        $order->cancelled_by = auth()->id();
        $order->save();

        $this->dispatchBrowserEvent('orderCancelled', ['message' => __('messages.orderCanceled')]);
    }

    public function loadMore(): void
    {
        if ($this->isLoadingMore || !$this->hasMore) {
            return;
        }

        $this->isLoadingMore = true;
        $this->perPage += 20;
    }

    public function render()
    {
        $data = $this->fetchOrders();
        $orders = $data['orders'];
        $ordersTotal = $data['ordersTotal'];
        $statusCounts = $data['statusCounts'];

        // Check for new orders and show popup
        $playSound = false;
        $pendingNotification = session()->get('new_order_notification_pending', false);

        if ($pendingNotification) {
            $playSound = true;
            $this->showNewOrderNotification();
        }

        $kotCount = $statusCounts['kot'] ?? 0;
        $billedCount = $statusCounts['billed'] ?? 0;
        $paymentDue = $statusCounts['payment_due'] ?? 0;
        $paidOrders = $statusCounts['paid'] ?? 0;
        $canceledOrders = $statusCounts['canceled'] ?? 0;
        $outDeliveryOrders = $statusCounts['out_for_delivery'] ?? 0;
        $deliveredOrders = $statusCounts['delivered'] ?? 0;
        $draftOrders = $statusCounts['draft'] ?? 0;

        $receiptSettings = restaurant()->receiptSetting;

        return view('livewire.order.orders', [
            'orders' => $orders,
            'ordersTotal' => $ordersTotal,
            'hasMore' => $this->hasMore,
            'kotCount' => $kotCount,
            'billedCount' => $billedCount,
            'paymentDueCount' => $paymentDue,
            'paidOrdersCount' => $paidOrders,
            'canceledOrdersCount' => $canceledOrders,
            'outDeliveryOrdersCount' => $outDeliveryOrders,
            'deliveredOrdersCount' => $deliveredOrders,
            'draftOrdersCount' => $draftOrders,
            'receiptSettings' => $receiptSettings, // Pass the fetched receipt settings to the view
            'orderID' => $this->orderID,
            'playSound' => $playSound ?? false,
        ]);
    }

    private function fetchOrders(): array
    {
        $tz = timezone();

        $start = Carbon::createFromFormat('m/d/Y', $this->startDate, $tz)
            ->startOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        $end = Carbon::createFromFormat('m/d/Y', $this->endDate, $tz)
            ->endOfDay()
            ->setTimezone('UTC')
            ->toDateTimeString();

        $ordersQuery = Order::withCount('items')
            ->with('table', 'waiter', 'customer', 'orderType', 'deliveryApp')
            ->orderBy('id', 'desc')
            ->where('orders.date_time', '>=', $start)
            ->where('orders.date_time', '<=', $end);

        if (!empty($this->filterOrderType)) {
            $ordersQuery->where('order_type', $this->filterOrderType);
        }

        if (!empty($this->filterDeliveryApp)) {
            if ($this->filterDeliveryApp === 'direct') {
                $ordersQuery->whereNull('delivery_app_id');
            } else {
                $ordersQuery->where('delivery_app_id', $this->filterDeliveryApp);
            }
        }

        if (!empty($this->filterOrders)) {
            $ordersQuery->where('status', $this->filterOrders);
        }

        if ($this->filterWaiter) {
            $ordersQuery->where('waiter_id', $this->filterWaiter);
        }

        $statusCounts = (clone $ordersQuery)
            ->reorder() // clear ordering to avoid ONLY_FULL_GROUP_BY issues
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $ordersTotal = (clone $ordersQuery)->count();
        $orders = $ordersQuery->take($this->perPage)->get();
        $this->hasMore = $ordersTotal > $orders->count();
        $this->isLoadingMore = false;

        return [
            'orders' => $orders,
            'ordersTotal' => $ordersTotal,
            'statusCounts' => $statusCounts,
        ];
    }
}
