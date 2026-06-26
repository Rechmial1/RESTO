<?php

namespace Modules\Inventory\Livewire\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderPayment;
use Modules\Inventory\Entities\Supplier;

class PurchaseOrderReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $supplierId = '';
    public $status = '';
    public $paymentStatus = '';
    public $perPage = 15;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'supplierId', 'status', 'paymentStatus', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->supplierId = '';
        $this->status = '';
        $this->paymentStatus = '';
        $this->perPage = 15;
        $this->resetPage();
    }

    public function render()
    {
        $purchaseOrders = $this->baseQuery()->paginate((int) $this->perPage);

        return view('inventory::livewire.reports.purchase-order-report', [
            'purchaseOrders' => $purchaseOrders,
            'stats' => $this->getStats(),
            'suppliers' => $this->getSuppliers(),
            'orderStatuses' => $this->orderStatuses(),
            'paymentStatusOptions' => $this->paymentStatusOptions(),
        ]);
    }

    public function exportCsv()
    {
        $fileName = 'purchase-order-report-' . now()->format('Ymd_His') . '.csv';
        $orders = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                trans('inventory::modules.reports.purchase_orders.table.po_number'),
                trans('inventory::modules.reports.purchase_orders.table.supplier'),
                trans('inventory::modules.reports.purchase_orders.table.order_date'),
                trans('inventory::modules.reports.purchase_orders.table.status'),
                trans('inventory::modules.reports.purchase_orders.table.total'),
                trans('inventory::modules.reports.purchase_orders.table.paid'),
                trans('inventory::modules.reports.purchase_orders.table.due'),
                trans('inventory::modules.reports.purchase_orders.table.progress'),
            ]);

            foreach ($orders as $order) {
                $paidAmount = (float) $order->paid_amount;
                $dueAmount = max($order->total_amount - $paidAmount, 0);
                $progress = $order->total_amount > 0 ? min(($paidAmount / $order->total_amount) * 100, 100) : 0;

                fputcsv($handle, [
                    $order->po_number,
                    $order->supplier?->name,
                    optional($order->order_date)->format('Y-m-d'),
                    $order->status,
                    $order->total_amount,
                    $paidAmount,
                    $dueAmount,
                    $progress . '%',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function baseQuery(): Builder
    {
        $query = PurchaseOrder::query()
            ->where('branch_id', branch()->id)
            ->select('purchase_orders.*')
            ->selectSub($this->paidAmountSubquery(), 'paid_amount')
            ->with('supplier');

        if ($this->startDate) {
            $query->whereDate('order_date', '>=', Carbon::parse($this->startDate)->format('Y-m-d'));
        }

        if ($this->endDate) {
            $query->whereDate('order_date', '<=', Carbon::parse($this->endDate)->format('Y-m-d'));
        }

        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $this->applyPaymentStatusFilter($query)->orderByDesc('order_date');
    }

    private function paidAmountSubquery(): Builder
    {
        return PurchaseOrderPayment::query()
            ->selectRaw('COALESCE(SUM(amount), 0)')
            ->whereColumn('purchase_order_payments.purchase_order_id', 'purchase_orders.id');
    }

    private function applyPaymentStatusFilter(Builder $query): Builder
    {
        return $query->when($this->paymentStatus, function (Builder $innerQuery) {
            return match ($this->paymentStatus) {
                'paid' => $innerQuery->havingRaw('paid_amount >= total_amount'),
                'partial' => $innerQuery->havingRaw('paid_amount > 0 AND paid_amount < total_amount'),
                'unpaid' => $innerQuery->havingRaw('paid_amount = 0'),
                default => $innerQuery,
            };
        });
    }

    private function getStats(): array
    {
        $orders = $this->baseQuery()->get(['id', 'total_amount', 'paid_amount']);

        $totalAmount = $orders->sum('total_amount');
        $paidAmount = $orders->sum(fn ($order) => (float) $order->paid_amount);
        $dueAmount = max($totalAmount - $paidAmount, 0);

        return [
            'order_count' => $orders->count(),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'average_progress' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 1) : 0,
        ];
    }

    private function getSuppliers()
    {
        return Supplier::query()
            ->where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();
    }

    private function orderStatuses(): array
    {
        return [
            '' => trans('inventory::modules.purchaseOrder.all_status'),
            'draft' => trans('inventory::modules.purchaseOrder.status.draft'),
            'sent' => trans('inventory::modules.purchaseOrder.status.sent'),
            'received' => trans('inventory::modules.purchaseOrder.status.received'),
            'partially_received' => trans('inventory::modules.purchaseOrder.status.partially_received'),
            'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
        ];
    }

    private function paymentStatusOptions(): array
    {
        return [
            '' => trans('inventory::modules.reports.purchase_orders.filters.all_payment_status'),
            'paid' => trans('inventory::modules.reports.purchase_orders.payment_status_options.paid'),
            'partial' => trans('inventory::modules.reports.purchase_orders.payment_status_options.partial'),
            'unpaid' => trans('inventory::modules.reports.purchase_orders.payment_status_options.unpaid'),
        ];
    }
}

