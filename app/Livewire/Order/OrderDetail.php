<?php

namespace App\Livewire\Order;

use App\Models\Tax;
use App\Models\Order;
use App\Models\Table;
use App\Models\Printer;
use Livewire\Component;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderCharge;
use Livewire\Attributes\On;
use App\Traits\PrinterSetting;
use App\Models\KotCancelReason;
use App\Models\DeliveryExecutive;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\User;
use App\Scopes\BranchScope;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderDetail extends Component
{

    use LivewireAlert, PrinterSetting;

    public $order;
    public $taxes;
    public $total = 0;
    public $subTotal = 0;
    public $showOrderDetail = false;
    public $showAddCustomerModal = false;
    public $showTableModal = false;
    public $showTableChangeConfirmationModal = false;
    public $pendingTable = null;
    public $cancelOrderModal = false;
    public $deleteOrderModal = false;
    public $tableNo;
    public $tableId;
    public $orderStatus;
    public $discountAmount = 0;
    public $deliveryExecutives;
    public $deliveryExecutive;
    public $orderProgressStatus;
    public $fromPos = null;
    public $confirmDeleteModal = false;
    public $cancelReasons;
    public $cancelReason;
    public $cancelReasonText;
    public $totalTaxAmount = 0;
    public $taxMode;
    public $currencyId;
    public $users;
    public $selectWaiter;
    public $confirmDeleteItemModal = false;
    public $itemToDelete;

    public $showReturnModal = false;
    public $returnOrder = null;
    public $originalOrder = null;
    public $itemsToReturn = [];
    public $returnReason = '';
    public $returnTotal = 0;

    public function mount()
    {
        $this->total = 0;
        $this->subTotal = 0;
        $this->taxes = Tax::all();
        $this->deliveryExecutives = cache()->remember('delivery_executives_' . restaurant()->id, 60 * 60 * 24, function () {
            return DeliveryExecutive::where('status', 'available')->get();
        });
        if ($this->order) {
            $this->deliveryExecutive = $this->order->delivery_executive_id;
        }
        $this->cancelReasons = KotCancelReason::where('cancel_order', true)->get();

        $this->users = cache()->remember('waiters_' . restaurant()->id, 60 * 60 * 24, function () {
            return User::withoutGlobalScope(BranchScope::class)
            ->where(function ($q) {
                return $q->where('branch_id', branch()->id)
                    ->orWhereNull('branch_id');
            })
            ->role('waiter_' . restaurant()->id)
            ->where('restaurant_id', restaurant()->id)
            ->get();
        });
    }

    public function printOrder($orderId)
    {


        $orderPlaces = \App\Models\MultipleOrder::with('printerSetting')->get();

        foreach ($orderPlaces as $orderPlace) {
            $printerSetting = $orderPlace->printerSetting;
        }

        try {

            switch ($printerSetting?->printing_choice) {
            case 'directPrint':

                $this->handleOrderPrint($orderId);
                    break;
            default:
                $url = route('orders.print', $orderId);
                $this->dispatch('print_location', $url);
                    break;
            }
        } catch (\Throwable $e) {
            $this->alert('error', __('messages.printerNotConnected') . ' : ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    #[On('showOrderDetail')]
    public function showOrder($id, $fromPos = null)
    {
        $this->order = Order::with('items', 'items.menuItem','items.menuItem.taxes', 'items.menuItemVariation', 'items.modifierOptions', 'payments', 'cancelReason')->find($id);
        $this->orderStatus = $this->order->status;
        $this->fromPos = $fromPos;
        $this->orderProgressStatus = $this->order->order_status->value;
        $restaurant = restaurant();
        $this->currencyId = $restaurant->currency_id;
        $this->taxMode = $this->order?->tax_mode ?? ($this->restaurant->tax_mode ?? 'order');

        if ($this->taxMode === 'item') {
            $this->totalTaxAmount = $this->order?->total_tax_amount ?? 0;
        }

        $this->selectWaiter = $this->order->waiter_id;
        $this->showOrderDetail = true;

        // Reset table change confirmation modal state to prevent it from opening automatically
        $this->showTableChangeConfirmationModal = false;
        $this->pendingTable = null;
    }

    #[On('setTable')]
    public function setTable(Table $table)
    {
        // Check if there's an existing table assigned
        $hasTable = !is_null($this->tableNo) ||
                   ($this->order && $this->order->table);

        if ($hasTable) {
            // Store the selected table temporarily and show confirmation modal
            $this->pendingTable = $table;
            $this->showTableModal = false;
            $this->showTableChangeConfirmationModal = true;
        } else {
            // No existing table, apply immediately
            $this->tableNo = $table->table_code;
            $this->tableId = $table->id;

            if ($this->order) {
                $currentOrder = Order::where('id', $this->order->id)->first();

                Table::where('id', $currentOrder->table_id)->update([
                    'available_status' => 'available'
                ]);

                $currentOrder->update(['table_id' => $table->id]);

                if ($this->order->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                    Table::where('id', $this->tableId)->update([
                        'available_status' => 'running'
                    ]);
                }

                $this->order->fresh();
                $this->dispatch('showOrderDetail', id: $this->order->id);
            }

            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');

            $this->showTableModal = false;
        }
    }

    public function openTableChangeConfirmation()
    {
        // Always open table modal first
        $this->showTableModal = true;
        $this->dispatch('refreshSetTableComponent');
    }

    public function confirmTableChange()
    {
        if (!$this->pendingTable) {
            $this->showTableChangeConfirmationModal = false;
            return;
        }

        // Apply the table change
        $table = $this->pendingTable;

        $this->tableNo = $table->table_code;
        $this->tableId = $table->id;

        if ($this->order) {
            $currentOrder = Order::where('id', $this->order->id)->first();

            // Release previous table
            if ($currentOrder->table_id) {
                Table::where('id', $currentOrder->table_id)->update([
                    'available_status' => 'available'
                ]);
            }

            $currentOrder->update(['table_id' => $table->id]);

            if ($this->order->date_time->format('d-m-Y') == now()->format('d-m-Y')) {
                Table::where('id', $this->tableId)->update([
                    'available_status' => 'running'
                ]);
            }

            $this->order->fresh();
            $this->dispatch('showOrderDetail', id: $this->order->id);
        }

        // Clear pending table and close modals
        $this->pendingTable = null;
        $this->showTableChangeConfirmationModal = false;

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function cancelTableChange()
    {
        // Unlock the pending table if it was locked
        if ($this->pendingTable) {
            $tableModel = Table::find($this->pendingTable->id);
            if ($tableModel) {
                $tableModel->unlock(null, true);
            }
        }

        // Clear pending table and close modal
        $this->pendingTable = null;
        $this->showTableChangeConfirmationModal = false;
    }

    public function saveOrderStatus()
    {
        if ($this->order) {
            Order::where('id', $this->order->id)->update(['status' => $this->orderStatus]);

            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');
        }
    }

    public function showAddCustomer($id)
    {
        $this->order = Order::find($id);
        $this->showAddCustomerModal = true;
    }

    public function showDeleteItemModal($id)
    {
        $this->itemToDelete = $id;
        $this->confirmDeleteItemModal = true;
    }

    public function deleteOrderItems($id)
    {
        $orderItem = OrderItem::find($id);

        if ($orderItem) {
            $kotItems = KotItem::where('menu_item_id', $orderItem->menu_item_id)
                ->where('menu_item_variation_id', $orderItem->menu_item_variation_id)
                ->where('quantity', $orderItem->quantity)
                ->whereHas('kot', function($query) use ($orderItem) {
                    $query->where('order_id', $orderItem->order_id);
                })
                ->get();

            foreach ($kotItems as $kotItem) {
                $kotItem->delete();
            }
        }

        OrderItem::destroy($id);

        if ($this->order) {
            $this->order->refresh();

            if ($this->order->items->count() === 0) {
                $this->deleteOrder($this->order->id);
                return;
            }

            // Recalculate order totals properly
            $this->recalculateOrderTotals();
        }

        $this->confirmDeleteItemModal = false;
        $this->itemToDelete = null;

        $this->alert('success', __('messages.orderItemDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('refreshPos');
    }

    public function updatedOrderProgressStatus($value)
    {
        if (empty($this->order) || is_null($value)) {
            return;
        }

        $this->order->update(['order_status' => $value]);
        $this->orderProgressStatus = $value;

        if ($value === 'confirmed') {
            $this->order->kot->each(function ($kot) {
                $kot->update(['status' => 'in_kitchen']);
            });
        }

        $this->dispatch('posOrderSuccess');
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function saveOrder($action)
    {

        switch ($action) {
        case 'bill':
            $successMessage = __('messages.billedSuccess');
            $status = 'billed';
            $tableStatus = 'running';
                break;

        case 'kot':
                return $this->redirect(route('pos.show', $this->order->table_id), navigate: true);
        }

        $taxes = Tax::all();

        Order::where('id', $this->order->id)->update([
            'date_time' => now(),
            'status' => $status
        ]);

        if ($status == 'billed') {
            $totalTaxAmount = 0;

            foreach ($this->order->kot as $kot) {
                foreach ($kot->items as $item) {
                    $price = (($item->menu_item_variation_id) ? $item->menuItemVariation->price : $item->menuItem->price);
                    $amount = $price * $item->quantity;

                    // Calculate tax for item-level taxation
                    $taxAmount = 0;
                    $taxPercentage = 0;
                    $taxBreakup = null;

                    if ($this->taxMode === 'item') {
                        $menuItem = $item->menuItem;
                        $taxes = $menuItem->taxes ?? collect();
                        $isInclusive = restaurant()->tax_inclusive ?? false;

                        if ($taxes->isNotEmpty()) {
                            $taxResult = MenuItem::calculateItemTaxes($price, $taxes, $isInclusive);
                            $taxAmount = $taxResult['tax_amount'] * $item->quantity;
                            $taxPercentage = $taxResult['tax_percentage'];
                            $taxBreakup = json_encode($taxResult['tax_breakdown']);
                            $totalTaxAmount += $taxAmount;
                        }
                    }

                    OrderItem::create([
                        'order_id' => $this->order->id,
                        'menu_item_id' => $item->menu_item_id,
                        'menu_item_variation_id' => $item->menu_item_variation_id,
                        'quantity' => $item->quantity,
                        'price' => $price,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'tax_percentage' => $taxPercentage,
                        'tax_breakup' => $taxBreakup,
                    ]);
                }
            }

            if ($this->taxMode === 'order') {
                foreach ($taxes as $value) {
                    OrderTax::create([
                        'order_id' => $this->order->id,
                        'tax_id' => $value->id
                    ]);
                }
            }

            $this->total = 0;
            $this->subTotal = 0;

            foreach ($this->order->load('items')->items as $value) {
                if ($this->taxMode === 'item') {
                    $isInclusive = restaurant()->tax_inclusive ?? false;
                    if ($isInclusive) {
                        // For inclusive tax: subtract tax from amount to get subtotal
                        $this->subTotal += ($value->amount - ($value->tax_amount ?? 0));
                    } else {
                        // For exclusive tax: amount is subtotal
                        $this->subTotal += $value->amount;
                    }
                } else {
                    $this->subTotal += $value->amount;
                }
                $this->total += $value->amount;
            }

            // Calculate taxes for order-level taxation
            if ($this->taxMode === 'order') {
                foreach ($taxes as $value) {
                    $taxAmount = ($value->tax_percent / 100) * $this->subTotal;
                    $this->total += $taxAmount;
                    $totalTaxAmount += $taxAmount;
                }
            } elseif ($this->taxMode === 'item') {
                $isInclusive = restaurant()->tax_inclusive ?? false;
                if (!$isInclusive) {
                    // For exclusive taxes, add tax to total
                    $this->total += $totalTaxAmount;
                }
            }

            // Apply discounts
            if ($this->order->discount_type === 'percent') {
                $this->discountAmount = round(($this->subTotal * $this->order->discount_value) / 100, 2);
            } elseif ($this->order->discount_type === 'fixed') {
                $this->discountAmount = min($this->order->discount_value, $this->subTotal);
            }

            $this->total -= $this->discountAmount ?? 0;

            Order::where('id', $this->order->id)->update([
                'sub_total' => $this->subTotal,
                'total' => $this->total,
                'discount_amount' => $this->discountAmount,
                'total_tax_amount' => $totalTaxAmount,
            ]);
        }

        Table::where('id', $this->tableId)->update([
            'available_status' => $tableStatus
        ]);


        $this->alert('success', $successMessage, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        if ($status == 'billed') {
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');
            $this->dispatch('resetPos');
        }
    }

    public function showPayment($id)
    {
        $this->dispatch('showPaymentModal', id: $id);
    }

    public function cancelOrderStatus($id)
    {
        // Validate that a cancel reason is provided
        if (!$this->cancelReason && !$this->cancelReasonText) {
            $this->alert('error', __('modules.settings.cancelReasonRequired'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return;
        }

        if ($id) {
            $order = Order::find($id);

            if ($order) {
                $order->update([
                    'status' => 'canceled',
                    'order_status' => 'cancelled',
                    'cancel_reason_id' => $this->cancelReason,
                    'cancel_reason_text' => $this->cancelReasonText,
                    'cancelled_by' => auth()->id(),
                ]);

                // Update table status
                if ($order->table_id) {
                    $table = Table::find($order->table_id);

                    if ($table) {
                        $table->update(['available_status' => 'available']);

                        // Release table session lock if exists
                        if ($table->tableSession) {
                            $table->tableSession->releaseLock();
                        }
                    }
                }


                $this->alert('success', __('messages.orderCanceled'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close'),
                ]);

                $this->confirmDeleteModal = false;
                $this->cancelReason = null;
                $this->cancelReasonText = null;

                return $this->redirect(route('pos.index'), navigate: true);
            }
        }
    }

    public function cancelOrder($id)
    {
        // Validate that a cancel reason is provided
        if (!$this->cancelReason && !$this->cancelReasonText) {
            $this->alert('error', __('modules.settings.cancelReasonRequired'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return;
        }

        $order = Order::find($id);

        if ($order) {
            $order->update([
                'status' => 'canceled',
                'order_status' => 'cancelled',
                'cancel_reason_id' => $this->cancelReason,
                'cancel_reason_text' => $this->cancelReasonText,
                'cancelled_by' => auth()->id(),
            ]);
            $order->kot()->delete();
            $order->payments()->delete();

            if ($order->table_id) {
                Table::where('id', $order->table_id)->update([
                    'available_status' => 'available',
                ]);
            }
            $this->cancelOrderModal = false;
            $this->confirmDeleteModal = false;
            $this->cancelReason = null;
            $this->cancelReasonText = null;
            $this->dispatch('showOrderDetail', id: $this->order->id);
            $this->dispatch('posOrderSuccess');
            $this->dispatch('refreshOrders');

            $this->alert('success', __('messages.orderCanceled'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            if ($this->fromPos) {
                return $this->redirect(route('pos.index'), navigate: true);
            } else {
                $this->dispatch('resetPos');
            }
        }
    }

    public function paymentReceived($orderId, $status)
    {
        $order = Order::with('payments')->find($orderId);

        if (!$order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($status === 'received') {
            $amountPaid = $order->payments->sum('amount');
            $order->update([
                'status' => 'paid',
                'amount_paid' => $amountPaid
            ]);
        } elseif ($status === 'not_received') {
            $latestPayment = $order->payments->last();
            if ($latestPayment) {
                $latestPayment->delete();
            }
            $order->update(['status' => 'payment_due']);
        }

        $this->alert('success', __('messages.statusUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('showOrderDetail', id: $this->order->id);
        $this->dispatch('refreshOrders');
        $this->dispatch('refreshPos');
    }

    public function deleteOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            $this->alert('error', __('messages.orderNotFound'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }

        if ($order->table_id) {
            Table::where('id', $order->table_id)->update(['available_status' => 'available']);
        }
        // Delete associated KOT records
        $order->kot()->delete();

        $order->delete();


        $this->deleteOrderModal = false;
        $this->showOrderDetail = false;
        $order = null;
        $this->order = null;

        $this->alert('success', __('messages.orderDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);


        if ($this->fromPos) {
            return $this->redirect(route('pos.index'), navigate: true);
        }
        else {

            $this->dispatch('refreshOrders');
            $this->dispatch('refreshPos');
            $this->dispatch('refreshKots');
        }

    }

    public function saveDeliveryExecutive()
    {
        $this->order->update(['delivery_executive_id' => $this->deliveryExecutive]);
        $this->order->fresh();
        $this->alert('success', __('messages.deliveryExecutiveAssigned'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function removeCharge($chargeId)
    {
        $charge = OrderCharge::find($chargeId);

        if ($charge) {
            $charge->delete();
            $this->order->refresh();

            // Recalculate order totals properly
            $this->recalculateOrderTotals();
        }
    }

    public function updatePaymentMethod($id, $paymentMethod)
    {
        if (!$id || !$paymentMethod || !$this->order) {
            return;
        }

        $payment = $this->order->payments()->whereId($id)->first();

        if (!$payment) {
            return;
        }

        $payment->payment_method = $paymentMethod;
        $payment->save();

        $hasPaymentDue = $this->order->payments->contains('payment_method', 'due');

        $newStatus = $hasPaymentDue ? 'payment_due' : 'paid';

        if ($this->order->status !== $newStatus) {
            $this->order->status = $newStatus;
            $this->order->save();
        }

        $this->alert('success', __('messages.statusUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->dispatch('showOrderDetail', id: $this->order->id);
        $this->dispatch('refreshOrders');
    }

    public function updatedSelectWaiter($value)
    {
        if ($this->order) {
            $this->order->update(['waiter_id' => $value ?: null]);

            $this->alert('success', __('messages.waiterUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    /**
     * Recalculate order totals including all components
     */
    public function recalculateOrderTotals()
    {
        if (!$this->order) {
            return;
        }

        // Refresh the order to get latest data
        $this->order->refresh();
        $this->order->load(['items', 'charges', 'taxes', 'payments']);

        // Reset totals
        $this->subTotal = 0;
        $this->total = 0;
        $totalTaxAmount = 0;

        // Calculate subtotal from items
        foreach ($this->order->items as $item) {
            if ($this->taxMode === 'item') {
                $isInclusive = restaurant()->tax_inclusive ?? false;
                if ($isInclusive) {
                    // For inclusive tax: subtract tax from amount to get subtotal
                    $this->subTotal += ($item->amount - ($item->tax_amount ?? 0));
                } else {
                    // For exclusive tax: amount is subtotal
                    $this->subTotal += $item->amount;
                }
            } else {
                $this->subTotal += $item->amount;
            }
        }

        // Calculate taxes
        if ($this->taxMode === 'order') {
            // Order-level taxation
            foreach ($this->order->taxes as $orderTax) {
                $taxAmount = ($orderTax->tax->tax_percent / 100) * $this->subTotal;
                $totalTaxAmount += $taxAmount;
            }
        } else {
            // Item-level taxation
            $isInclusive = restaurant()->tax_inclusive ?? false;
            foreach ($this->order->items as $item) {
                $totalTaxAmount += ($item->tax_amount ?? 0);
            }
        }

        // Start with subtotal + taxes
        $this->total = $this->subTotal + $totalTaxAmount;

        // Apply discount
        $discountAmount = 0;
        if ($this->order->discount_type === 'percent') {
            $discountAmount = round(($this->subTotal * $this->order->discount_value) / 100, 2);
        } elseif ($this->order->discount_type === 'fixed') {
            $discountAmount = min($this->order->discount_value, $this->subTotal);
        }
        $this->total -= $discountAmount;

        // Add charges (delivery fees, service charges, etc.)
        foreach ($this->order->charges as $charge) {
            $chargeAmount = $charge->charge->getAmount($this->subTotal - $discountAmount);
            $this->total += $chargeAmount;
        }

        // Add tip
        if ($this->order->tip_amount > 0) {
            $this->total += $this->order->tip_amount;
        }

        // Add delivery fee
        if ($this->order->order_type === 'delivery' && !is_null($this->order->delivery_fee)) {
            $this->total += $this->order->delivery_fee;
        }

        // Update the order in database
        $this->order->update([
            'sub_total' => $this->subTotal,
            'total' => $this->total,
            'discount_amount' => $discountAmount,
            'total_tax_amount' => $totalTaxAmount
        ]);
    }

    /**
     * Get the display price for an item (base price without tax for inclusive items)
     */
    public function getItemDisplayPrice($key)
    {
        if ($this->taxMode === 'item' && isset($this->orderItemTaxDetails[$key])) {
            return $this->orderItemTaxDetails[$key]['display_price'] ?? 0;
        }

        // Check if we have session data arrays (for active POS session)
        if (isset($this->orderItemList[$key])) {
            $basePrice = isset($this->orderItemVariation[$key]) ? $this->orderItemVariation[$key]->price : $this->orderItemList[$key]->price;
            $modifierPrice = $this->orderItemModifiersPrice[$key] ?? 0;
            return $basePrice + $modifierPrice;
        }

        // For existing order items (when viewing order details), calculate from the order item itself
        if ($this->order && isset($this->order->items[$key])) {
            $orderItem = $this->order->items[$key];
            $basePrice = !is_null($orderItem->menuItemVariation) ? $orderItem->menuItemVariation->price : $orderItem->menuItem->price;
            $modifierPrice = $orderItem->modifierOptions->sum('price');

            // If tax is inclusive, calculate the display price without tax
            if (restaurant()->tax_inclusive && restaurant()->tax_mode === 'item') {
                $menuItem = $orderItem->menuItem;
                $taxes = $menuItem->taxes ?? collect();
                $itemPriceWithModifiers = $basePrice + $modifierPrice;

                if ($taxes->isNotEmpty()) {
                    $taxPercent = $taxes->sum('tax_percent');
                    $displayPrice = $itemPriceWithModifiers / (1 + $taxPercent / 100);
                    return $displayPrice;
                }
            }

            return $basePrice + $modifierPrice;
        }

        return 0;
    }

   public function normaliseOrder($id)
    {
        $order = Order::find($id);

        if (!$order) {
            $this->alert('error', 'Commande introuvable', [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Appel AJAX vers normalize.php (domaine externe)
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'verify' => false,
            ]);
            
            $url = 'https://seagreen-squirrel-343931.hostingersite.com/normalize.php';
            
            Log::info('Envoi à normalize.php', [
                'url' => $url,
                'order_id' => $id,
                'system_code' => '123456'
            ]);
            
            $response = $client->post($url, [
                'form_params' => [
                    'sale_id' => $id,
                    'system_code' => '123456'
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            if ($result['success']) {
                // Mettre à jour la commande avec les infos MECEF
                $order->sfe_uid = $result['data']['uid'];
                $order->sfe_cid = $result['data']['codeMECeFDGI'];
                $order->sfe_nim = $result['data']['nim'];
                $order->sfe_datetime = $result['data']['dateTime'];
                $order->sfe_status = 1;
                $order->sfe_confirm_date = now();
                $order->save();

                $this->alert('success', $result['message'], [
                    'toast' => true,
                    'position' => 'top-end'
                ]);

                // 🔴 SOLUTION 1: Rafraîchir la page complètement
                return redirect()->route('orders.show', $id);
                
                // OU 🔴 SOLUTION 2: Rafraîchir le composant (si vous préférez)
                // $this->dispatch('refreshOrderDetail');
                // $this->dispatch('refreshOrders');
                
            } else {
                $this->alert('error', $result['message'], [
                    'toast' => true,
                    'position' => 'top-end'
                ]);
                
                // Réinitialiser l'état de chargement en cas d'erreur
                $this->dispatch('resetLoadingState');
            }
        } catch (\Exception $e) {
            Log::error('Erreur de normalisation: ' . $e->getMessage());
            $this->alert('error', 'Erreur de normalisation: ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end'
            ]);
            
            // Réinitialiser l'état de chargement en cas d'erreur
            $this->dispatch('resetLoadingState');
        }
    }

    public function createReturnOrder($originalOrderId)
{
    $this->openReturnModal($originalOrderId);
}

public function openReturnModal($originalOrderId)
{
    $this->returnOrder = null;
    $this->originalOrder = Order::with(['items.menuItem', 'items.menuItemVariation'])
        ->findOrFail($originalOrderId);

    $this->itemsToReturn = [];

    foreach ($this->originalOrder->items as $item) {
        $this->itemsToReturn[] = [
            'id' => $item->id,
            'order_item_id' => $item->id,
            'name' => $item->menuItem->item_name . ($item->menuItemVariation ? ' (' . $item->menuItemVariation->variation . ')' : ''),
            'quantity' => $item->quantity,
            'max_quantity' => $item->quantity,
            'price' => $item->price,
            'selected' => true,
            'return_quantity' => $item->quantity,
        ];
    }

    $this->returnReason = '';
    $this->calculateReturnTotal();
    $this->showReturnModal = true;
}

private function calculateReturnTotal()
{
    $this->returnTotal = 0;

    foreach ($this->itemsToReturn as $item) {
        $this->returnTotal += ((float) $item['price']) * ((int) $item['return_quantity']);
    }
}

public function confirmReturn()
{
    $selectedItems = collect($this->itemsToReturn)
        ->filter(fn ($item) => (int) $item['return_quantity'] > 0)
        ->values();

    if ($selectedItems->isEmpty()) {
        $this->alert('error', 'Aucun article disponible pour le retour.', [
            'toast' => true,
            'position' => 'top-end'
        ]);
        return;
    }

    if (empty($this->returnReason)) {
        $this->alert('error', 'Veuillez indiquer la raison du retour.', [
            'toast' => true,
            'position' => 'top-end'
        ]);
        return;
    }

    try {
        DB::beginTransaction();

        $returnOrder = Order::create([
            'branch_id' => $this->originalOrder->branch_id,
            'order_number' => 'FA-' . time() . '-' . $this->originalOrder->id,
            'formatted_order_number' => 'FA-' . $this->originalOrder->formatted_order_number,
            'date_time' => now(),
            'customer_id' => $this->originalOrder->customer_id,
            'waiter_id' => auth()->id(),
            'added_by' => auth()->id(),
            'status' => 'billed',
            'order_type' => $this->originalOrder->order_type,
            'order_type_id' => $this->originalOrder->order_type_id,
            'sub_total' => 0,
            'total' => 0,
            'invoice_type' => 'FA',
            'original_invoice_id' => $this->originalOrder->id,
            'note' => 'Retour sur facture #' . $this->originalOrder->formatted_order_number . ' | Raison retour: ' . $this->returnReason,
        ]);

        $total = 0;

        foreach ($selectedItems as $item) {
            $originalItem = $this->originalOrder->items->firstWhere('id', $item['id']);

            if (!$originalItem) {
                continue;
            }

            $returnQuantity = (int) $item['max_quantity'];
            $amount = (float) $item['price'] * $returnQuantity;

            OrderItem::create([
                'order_id' => $returnOrder->id,
                'menu_item_id' => $originalItem->menu_item_id,
                'menu_item_variation_id' => $originalItem->menu_item_variation_id,
                'quantity' => $returnQuantity,
                'price' => $item['price'],
                'amount' => $amount,
                'note' => 'Retour sur facture #' . $this->originalOrder->formatted_order_number,
            ]);

            $total += $amount;
        }

        $returnOrder->update([
            'sub_total' => $total,
            'total' => $total,
        ]);

        DB::commit();

        $this->closeReturnModal();

        $this->alert('success', 'Retour cree avec succes.', [
            'toast' => true,
            'position' => 'top-end'
        ]);

        $this->dispatch('showOrderDetail', id: $returnOrder->id);
    } catch (\Exception $e) {
        DB::rollBack();

        $this->alert('error', 'Erreur: ' . $e->getMessage(), [
            'toast' => true,
            'position' => 'top-end'
        ]);
    }
}

public function closeReturnModal()
{
    $this->showReturnModal = false;
    $this->returnOrder = null;
    $this->originalOrder = null;
    $this->itemsToReturn = [];
    $this->returnTotal = 0;
    $this->returnReason = '';
}



    public function render()
    {
        return view('livewire.order.order-detail');
    }

}
