<?php

namespace Modules\Inventory\Livewire\PurchaseOrder;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\PurchaseOrder;
use Modules\Inventory\Entities\PurchaseOrderPayment;
use Modules\Inventory\Entities\Supplier;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Modules\Inventory\Notifications\SendPurchaseOrder;

class PurchaseOrderList extends Component
{
    use WithPagination;

    public $search = '';
    public $supplierId;
    public $status = '';
    public $confirmingDeletion = false;
    public $purchaseOrderToDelete;
    public $confirmingSend = false;
    public $purchaseOrderToSend;
    public $confirmingCancel = false;
    public $purchaseOrderToCancel;
    public $showPaymentModal = false;
    public $paymentForm = [
        'purchase_order_id' => null,
        'amount' => '',
        'payment_method' => 'cash',
        'paid_at' => '',
        'reference' => '',
        'notes' => '',
    ];

    protected $listeners = [
        'purchaseOrderSaved' => '$refresh',
        'purchaseOrderSent' => '$refresh',
        'purchaseOrderCancelled' => '$refresh',
    ];

    public function mount()
    {
        // Initialize with last 30 days by default
        $this->dateRange = now()->subDays(30)->format('Y-m-d') . ' to ' . now()->format('Y-m-d');
        $this->paymentForm['paid_at'] = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSupplierId()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'supplierId', 'status']);
        $this->resetPage();
    }

    public function confirmDelete(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrderToDelete = $purchaseOrder;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if ($this->purchaseOrderToDelete) {
            $this->purchaseOrderToDelete->delete();
            $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.deleted_successfully'));
        }

        $this->confirmingDeletion = false;
        $this->purchaseOrderToDelete = null;
    }

    public function confirmSend(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrderToSend = $purchaseOrder;
        $this->confirmingSend = true;
    }

    public function send()
    {
        if ($this->purchaseOrderToSend) {
            $this->purchaseOrderToSend->update(['status' => 'sent']);
            $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.sent_successfully'));
            $this->purchaseOrderToSend->supplier->notify(new SendPurchaseOrder($this->purchaseOrderToSend));
        }

        $this->confirmingSend = false;
        $this->purchaseOrderToSend = null;
    }

    public function confirmCancel(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrderToCancel = $purchaseOrder;
        $this->confirmingCancel = true;
    }

    public function cancel()
    {
        if ($this->purchaseOrderToCancel) {
            $this->purchaseOrderToCancel->update(['status' => 'cancelled']);
            $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.cancelled_successfully'));
        }

        $this->confirmingCancel = false;
        $this->purchaseOrderToCancel = null;
    }

    public function openPaymentModal(PurchaseOrder $purchaseOrder)
    {
        $this->resetErrorBag();
        $this->paymentForm = [
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => '',
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d'),
            'reference' => '',
            'notes' => '',
        ];

        $this->showPaymentModal = true;
    }

    public function savePayment()
    {
        $validated = $this->validate([
            'paymentForm.purchase_order_id' => 'required|exists:purchase_orders,id',
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.payment_method' => 'nullable|string|max:120',
            'paymentForm.paid_at' => 'required|date',
            'paymentForm.reference' => 'nullable|string|max:190',
            'paymentForm.notes' => 'nullable|string|max:1000',
        ])['paymentForm'];

        $purchaseOrder = PurchaseOrder::query()
            ->where('branch_id', branch()->id)
            ->findOrFail($validated['purchase_order_id']);

        $currentPaid = $purchaseOrder->payments()->sum('amount');
        $remaining = max($purchaseOrder->total_amount - $currentPaid, 0);

        if ($validated['amount'] > $remaining) {
            $this->addError('paymentForm.amount', trans('inventory::modules.purchaseOrder.payments.validation.exceeds_due'));
            return;
        }

        PurchaseOrderPayment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'branch_id' => branch()->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'paid_at' => $validated['paid_at'],
            'reference' => $validated['reference'],
            'notes' => $validated['notes'],
            'created_by' => user()?->id,
        ]);

        $this->showPaymentModal = false;
        $this->paymentForm = [
            'purchase_order_id' => null,
            'amount' => '',
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d'),
            'reference' => '',
            'notes' => '',
        ];

        $this->dispatch('purchaseOrderSaved');
        $this->dispatch('notify-success', trans('inventory::modules.purchaseOrder.payments.saved'));
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.inventoryItem.unit']);
        
        // Configure PDF
        $pdf = PDF::loadView('inventory::pdfs.purchase-order', [
            'purchaseOrder' => $purchaseOrder
        ])->setPaper('a4');
        
        // Set additional PDF options for better font handling
        $pdf->getDomPDF()->set_option('defaultFont', 'Arial');
        $pdf->getDomPDF()->set_option('isRemoteEnabled', true);
        $pdf->getDomPDF()->set_option('isPhpEnabled', true);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, "PO-{$purchaseOrder->po_number}.pdf");
    }

    protected function getStats()
    {
        return [
            'total_orders' => PurchaseOrder::where('branch_id', branch()->id)->count(),
            'pending_orders' => PurchaseOrder::where('branch_id', branch()->id)
                ->whereIn('status', ['draft', 'sent', 'partially_received'])
                ->count(),
            'completed_orders' => PurchaseOrder::where('branch_id', branch()->id)
                ->where('status', 'received')
                ->count()
        ];
    }

    public function render()
    {
        $query = PurchaseOrder::query()
            ->where('branch_id', branch()->id)
            ->with(['supplier', 'items.inventoryItem'])
            ->withSum('payments as paid_amount', 'amount')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('po_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($query) {
                            $query->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->supplierId, function ($query) {
                $query->where('supplier_id', $this->supplierId);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest();

        return view('inventory::livewire.purchase-order.purchase-order-list', [
            'purchaseOrders' => $query->paginate(10),
            'suppliers' => Supplier::where('restaurant_id', restaurant()->id)
                ->orderBy('name')
                ->get(),
            'statuses' => [
                'draft' => trans('inventory::modules.purchaseOrder.status.draft'),
                'sent' => trans('inventory::modules.purchaseOrder.status.sent'),
                'received' => trans('inventory::modules.purchaseOrder.status.received'),
                'partially_received' => trans('inventory::modules.purchaseOrder.status.partially_received'),
                'cancelled' => trans('inventory::modules.purchaseOrder.status.cancelled'),
            ],
            'paymentMethods' => $this->paymentMethods(),
            'stats' => $this->getStats(),
        ]);
    }

    private function paymentMethods(): array
    {
        return [
            'cash' => trans('inventory::modules.purchaseOrder.payments.methods.cash'),
            'bank_transfer' => trans('inventory::modules.purchaseOrder.payments.methods.bank_transfer'),
            'card' => trans('inventory::modules.purchaseOrder.payments.methods.card'),
            'cheque' => trans('inventory::modules.purchaseOrder.payments.methods.cheque'),
            'other' => trans('inventory::modules.purchaseOrder.payments.methods.other'),
        ];
    }
} 