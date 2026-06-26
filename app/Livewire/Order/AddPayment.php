<?php

namespace App\Livewire\Order;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SplitOrder;
use App\Models\Table;
use App\Models\PredefinedAmount;
use App\Models\Tax; // ← IMPORT CRUCIAL AJOUTÉ
use App\Notifications\SendOrderBill;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use App\Events\SendOrderBillEvent;

class AddPayment extends Component
{
    use LivewireAlert;

    public $order;
    public $showAddPaymentModal = false;
    public $paymentMethod = 'cash';
    public $paymentAmount = 0;
    public $returnAmount = 0;
    public $balanceAmount = 0;
    public $dueAmount = 0;
    public $paidAmount = 0;
    
    // Split payment properties
    public $showSplitOptions = false;
    public $splitType = null;
    public $numberOfSplits = 2;
    public $customSplits = [];
    public $splits = [];
    public $availableItems = [];
    public $activeSplitId = 1;
    public $totalExtraCharges = 0;
    public $tipPercentage;
    public $tipAmount;
    public $tipMode = 'percentage';
    public $tipNote = '';
    public $showTipModal = false;
    public $canAddTip;
    public $predefinedAmounts = [];

    // Propriétés pour la taxe espèces (1% pour paiements > 100k FCFA)
    public $cashSurtaxAmount = 0;
    public $isCashSurtaxApplied = false;
    public $cashSurtaxRate = 1; // 1% par défaut

    // Propriétés pour le timbre fiscal
    public $timbreFiscalAmount = 0;
    public $isTimbreFiscalApplied = false;
    public $timbreFiscalRate = 1; // 1%
    public $timbreFiscalItemId = null; // ID de l'article "Timbre Fiscal"

    #[On('showPaymentModal')]
    public function showPaymentModal($id)
    {
        $this->order = Order::with([
            'items',
            'items.menuItem',
            'taxes',
            'payments',
            'splitOrders.items'
        ])->find($id);

        // Récupérer l'ID de l'article "Timbre Fiscal"
        $timbreFiscalItem = \App\Models\MenuItem::where('item_name', 'like', '%Timbre Fiscal%')->first();
        if ($timbreFiscalItem) {
            $this->timbreFiscalItemId = $timbreFiscalItem->id;
        }

        // Initialiser les propriétés de timbre fiscal
        $this->timbreFiscalAmount = $this->order->timbre_fiscal_amount ?? 0;
        $this->isTimbreFiscalApplied = (bool)($this->order->is_timbre_fiscal_applied ?? false);

        // Initialiser les propriétés de surtaxe espèces
        // $this->cashSurtaxAmount = $this->order->cash_surtax_amount ?? 0;
        // $this->isCashSurtaxApplied = (bool)($this->order->is_cash_surtax_applied ?? false);

        $this->canAddTip = restaurant()->enable_tip_pos && $this->order->status !== 'paid';

        // Load predefined amounts
        $this->predefinedAmounts = restaurant()->predefinedAmounts()->pluck('amount')->toArray();

        // If no predefined amounts exist, use defaults
        if (empty($this->predefinedAmounts)) {
            $this->predefinedAmounts = [50, 100, 500, 1000];
        }

        $totalDiscount = floatval($this->order->discount_amount ?? 0);

        $subTotal = floatval($this->order->sub_total ?? 0);
        $discountedSubTotal = max(0, $subTotal - $totalDiscount);

        $charges = $this->order->charges;
        $extraCharges = $charges->map(function ($charge) use ($discountedSubTotal) {
            $chargeAmount = $charge->charge->charge_type == 'percent'
                ? ($charge->charge->charge_value / 100) * $discountedSubTotal
                : $charge->charge->charge_value;
            return [
                'name' => $charge->charge->charge_name,
                'amount' => $chargeAmount,
                'rate' => $charge->charge->charge_value,
                'type' => $charge->charge->charge_type,
            ];
        })->toArray();
        $this->totalExtraCharges = collect($extraCharges)->sum('amount');

        $this->updateAmountDetails();
        $this->showAddPaymentModal = true;

        // Refresh available items data
        $this->refreshAvailableItems();

        $this->initializeSplits();
        $this->lockCreditNotePaymentMethod();
    }

    private function isCreditNote(): bool
{
    return $this->order && $this->order->invoice_type === 'FA';
}

private function getInitialPaymentMethod(): ?string
{
    if (!$this->order) {
        return null;
    }

    return $this->order->payments()
        ->orderBy('id')
        ->value('payment_method');
}

private function lockCreditNotePaymentMethod(): void
{
    if (!$this->isCreditNote()) {
        return;
    }

    $initialMethod = $this->getInitialPaymentMethod();

    if ($initialMethod) {
        $this->paymentMethod = $initialMethod;

        foreach ($this->splits as $splitId => $split) {
            $this->splits[$splitId]['paymentMethod'] = $initialMethod;
        }
    }

    $this->showSplitOptions = false;
    $this->splitType = null;
}


    private function refreshAvailableItems()
    {
        $totalDiscount = floatval($this->order->discount_amount ?? 0);
        $totalTip = floatval($this->order->tip_amount ?? 0);
        $totalBaseAmount = $this->order->items->sum('amount');
        $totalQuantity = $this->order->items->sum('quantity');
        $taxMode = restaurant()->tax_mode ?? 'order';

        // Get already paid item quantities from split orders
        $paidItemQuantities = $this->getPaidItemQuantities();

        $this->availableItems = $this->order->items->map(function($orderItem) use ($totalDiscount, $totalTip, $totalBaseAmount, $totalQuantity, $taxMode, $paidItemQuantities) {
            $unitBasePrice = $orderItem->amount / $orderItem->quantity;

            $itemDiscount = $totalBaseAmount > 0 ? ($orderItem->amount / $totalBaseAmount) * $totalDiscount : 0;
            $itemTip = $totalBaseAmount > 0 ? ($orderItem->amount / $totalBaseAmount) * $totalTip : 0;
            $unitDiscount = $itemDiscount / $orderItem->quantity;
            $unitTip = $itemTip / $orderItem->quantity;
            $unitBasePriceAfterDiscount = $unitBasePrice - $unitDiscount;
            $itemExtraCharges = $this->totalExtraCharges / $totalQuantity;

            $itemTaxAmount = 0;

            // Handle tax calculation based on tax mode
            if ($taxMode === 'item') {
                // For item-level tax, use the tax amount stored in the order item
                $itemTaxAmount = floatval($orderItem->tax_amount ?? 0) / $orderItem->quantity;
            } else {
                // For order-level tax, calculate proportionally
                if ($this->order->total > 0) {
                    foreach ($this->order->taxes as $tax) {
                        $itemTaxAmount += (($tax->tax->tax_percent / 100) * $unitBasePriceAfterDiscount);
                    }
                }
            }

            $unitTotalPrice = $unitBasePriceAfterDiscount + $itemTaxAmount + $itemExtraCharges + $unitTip;

            // Calculate remaining quantity (total - already paid)
            $paidQuantity = $paidItemQuantities[$orderItem->id] ?? 0;
            $remainingQuantity = $orderItem->quantity - $paidQuantity;

            return [
                'id' => $orderItem->id,
                'name' => $orderItem->menuItem->item_name,
                'quantity' => $orderItem->quantity,
                'paid_quantity' => $paidQuantity,
                'remaining' => $remainingQuantity,
                'price' => $unitTotalPrice, // per unit price including taxes, charges, discount, tip
                'base_price' => $unitBasePrice, // per unit base price BEFORE discount (for display)
                'base_price_after_discount' => $unitBasePriceAfterDiscount, // per unit base price after discount (for calculation)
                'tax_amount' => $itemTaxAmount, // per unit tax based on tax mode
                'total' => $orderItem->total,
                'extra_charges' => $itemExtraCharges, // per unit extra charges
                'discount' => $unitDiscount, // per unit discount
                'tip' => $unitTip, // per unit tip
                'order_item_id' => $orderItem->id,
                'tax_mode' => $taxMode
            ];
        })->filter(function($item) {
            // Only show items that have remaining quantity to be paid
            return $item['remaining'] > 0;
        })->values()->toArray();
    }

    public function updateAmountDetails()
    {
        if ($this->order && $this->order->split_type === 'items') {
            $totalPaidAmount = $this->order->splitOrders()
                ->where('status', 'paid')
                ->sum('amount');

            $this->dueAmount = $this->order->total - $totalPaidAmount;
            $this->paidAmount = $totalPaidAmount;
        } else {
            $totalPaidAmount = $this->order->payments
                ->where('payment_method', '!=', 'due')
                ->sum('amount');

            $this->dueAmount = $this->order->total - $totalPaidAmount;
            $this->paidAmount = $totalPaidAmount;
        }

        $this->paymentAmount = max(0, $this->dueAmount);
        $this->balanceAmount = $this->dueAmount - $this->paymentAmount;
        $this->returnAmount = 0;
    }

    private function getPaidItemQuantities()
    {
        $paidQuantities = [];

        // Get all paid split orders for this order
        $paidSplitOrders = $this->order->splitOrders()
            ->where('status', 'paid')
            ->with('items')
            ->get();

        foreach ($paidSplitOrders as $splitOrder) {
            foreach ($splitOrder->items as $splitItem) {
                $orderItemId = $splitItem->order_item_id;
                $paidQuantities[$orderItemId] = ($paidQuantities[$orderItemId] ?? 0) + $splitItem->quantity;
            }
        }

        return $paidQuantities;
    }

    private function initializeSplits()
    {
            $lockedPaymentMethod = $this->isCreditNote()
        ? ($this->getInitialPaymentMethod() ?: 'cash')
        : 'cash';
        if ($this->splitType === 'items') {
            // Refresh available items first
            $this->refreshAvailableItems();
            $lockedPaymentMethod = $this->isCreditNote() ? $this->getInitialPaymentMethod() : 'cash';

            // Check if there are unpaid items
            $hasUnpaidItems = collect($this->availableItems)->where('remaining', '>', 0)->count() > 0;

            if ($hasUnpaidItems) {
                $this->splits = [
                    1 => [
                        'id' => 1,
                        'items' => [],
                        'paymentMethod' => $lockedPaymentMethod ?: 'cash',
                        'amount' => 0,
                        'total' => 0
                    ]
                ];
                $this->activeSplitId = 1;
            } else {
                // No unpaid items, clear splits
                $this->splits = [];
                $this->activeSplitId = null;
            }
        } else {
            // For other split types
            $this->customSplits = [1, 2];

            foreach ($this->customSplits as $splitNumber) {
                $this->splits[$splitNumber] = [
                    'id' => $splitNumber,
                    'paymentMethod' => $lockedPaymentMethod ?: 'cash',
                    'amount' => 0,
                    'total' => 0  // Initialize total for consistency
                ];
            }

            if ($this->splitType === 'equal' || is_null($this->splitType)) {
                $splitAmount = $this->dueAmount / $this->numberOfSplits;
                foreach ($this->splits as $i => $split) {
                    if ($i > 0) {
                        $this->splits[$i]['amount'] = $splitAmount;
                    }
                }
            } elseif ($this->splitType === 'custom') {
                // For custom split, automatically split the unpaid amount
                $splitAmount = $this->dueAmount / count($this->customSplits);
                foreach ($this->customSplits as $splitNumber) {
                    $this->splits[$splitNumber]['amount'] = $splitAmount;
                }
            }
        }

        $this->updateBalanceAmount();
    }

    public function updatedPaymentAmount()
    {
        $paymentAmount = floatval($this->paymentAmount);

        if ($paymentAmount > $this->dueAmount) {
            // If payment is more than total, show return amount
            $this->returnAmount = $paymentAmount - $this->dueAmount;
            $this->balanceAmount = 0;
        } else {
            // If payment is less than total, show due amount
            $this->returnAmount = 0;
            $this->balanceAmount = $this->dueAmount - $paymentAmount;
        }
    }

    public function setPaymentMethod($method)
    {
        if ($this->isCreditNote()) {
            $initialMethod = $this->getInitialPaymentMethod();

            if ($initialMethod) {
                $this->paymentMethod = $initialMethod;
            }

            return;
        }

        $this->paymentMethod = $method;
        $this->updatedPaymentAmount();
    }


    public function quickAmount($amount)
    {
        $this->paymentAmount = floatval($amount);
        $this->updatedPaymentAmount();
    }

    public function appendNumber($number)
    {

        $currentAmount = (string) $this->paymentAmount;

        // Handle decimal point input
        if ($number === '.') {
            if (str_contains($currentAmount, '.')) {
                return;
            }
            $this->paymentAmount = $currentAmount . $number;
            return;
        }

        $this->paymentAmount = $currentAmount === '0' ? $number : $currentAmount . $number;

        $this->paymentAmount = is_numeric($this->paymentAmount) ? (float) $this->paymentAmount : $this->paymentAmount;

        $this->updatedPaymentAmount();
    }


    public function clearAmount()
    {
        $this->paymentAmount = 0;
        $this->returnAmount = 0;
        $this->balanceAmount = $this->dueAmount;
    }

public function updateSplitPayment($splitId, $method)
{
    if ($this->isCreditNote()) {
        $initialMethod = $this->getInitialPaymentMethod();

        if ($initialMethod && isset($this->splits[$splitId])) {
            $this->splits[$splitId]['paymentMethod'] = $initialMethod;
        }

        return;
    }

    if (isset($this->splits[$splitId])) {
        $this->splits[$splitId]['paymentMethod'] = $method;
    }
}


    public function processSplitPayment()
    {
        switch ($this->splitType) {
        case 'equal':
            $this->order->split_type = 'even';
            $this->order->saveQuietly();

            foreach ($this->splits as $i => $split) {
                if ($i > 0 && !empty($split['amount'])) { // Skip index 0 and empty amounts
                    SplitOrder::create([
                        'order_id' => $this->order->id,
                        'amount' => $split['amount'],
                        'payment_method' => $split['paymentMethod'],
                        'status' => 'paid'
                    ]);

                    Payment::create([
                        'order_id' => $this->order->id,
                        'payment_method' => $split['paymentMethod'],
                        'amount' => $split['amount']
                    ]);
                }
            }
                break;

        case 'custom':
            $this->order->split_type = 'custom';
            $this->order->saveQuietly();

            foreach ($this->customSplits as $index => $split) {
                $lastIndex = $index === array_key_last($this->customSplits);
                if (!empty($this->splits[$split]['amount'])) {
                    SplitOrder::create([
                        'order_id' => $this->order->id,
                        'amount' => $this->splits[$split]['amount'],
                        'payment_method' => $this->splits[$split]['paymentMethod'],
                        'status' => 'paid'
                    ]);

                    Payment::create([
                        'order_id' => $this->order->id,
                        'payment_method' => $this->splits[$split]['paymentMethod'],
                        'amount' => $this->splits[$split]['amount'],
                        'balance' => $lastIndex && $this->returnAmount ? $this->returnAmount : 0
                    ]);
                }
            }
                break;

        case 'items':
            $this->order->split_type = 'items';
            $this->order->saveQuietly();

            foreach ($this->splits as $split) {
                if (!empty($split['items'])) {

                    $splitTotal = round(collect($split['items'])->sum(function ($item) {
                        return floatval($item['price']) * intval($item['quantity']);
                    }), 2);

                    if ($splitTotal > 0) {
                        // Create split order record
                        $splitOrder = SplitOrder::create([
                            'order_id' => $this->order->id,
                            'amount' => $splitTotal,
                            'payment_method' => $split['paymentMethod'],
                            'status' => 'paid'
                        ]);

                        // Create split payment record
                        Payment::create([
                            'order_id' => $this->order->id,
                            'payment_method' => $split['paymentMethod'],
                            'amount' => $splitTotal
                        ]);

                        // Link items to split order with quantities
                        foreach ($split['items'] as $item) {
                            $splitOrder->items()->create([
                                'order_item_id' => $item['order_item_id'],
                                'quantity' => $item['quantity']
                            ]);
                        }
                    }
                }
            }

            // Refresh available items after processing payment
            $this->refreshItemsAfterPayment();
            break;
        }
    }

  public function submitForm()
{
    // MODIFICATION: Vérifier si la commande est déjà complètement payée
    if ($this->order->status === 'paid') {
        $this->alert('error', __('Cette commande a déjà été payée'), ['toast' => true]);
        $this->showAddPaymentModal = false;
        return;
    }
    
    // MODIFICATION: Vérifier si un paiement FedaPay est en cours
    $pendingFedapay = \App\Models\FedapayPayment::where('order_id', $this->order->id)
        ->where('payment_status', 'pending')
        ->exists();
        
    if ($pendingFedapay) {
        $this->alert('error', __('Un paiement est déjà en cours de traitement. Veuillez patienter.'), ['toast' => true]);
        $this->showAddPaymentModal = false;
        return;
    }
    
    // MODIFICATION: Vérifier si un paiement FedaPay a déjà été complété
    $completedFedapay = \App\Models\FedapayPayment::where('order_id', $this->order->id)
        ->where('payment_status', 'completed')
        ->exists();
        
    if ($completedFedapay && $this->order->status !== 'paid') {
        // Si FedaPay est complété mais la commande n'est pas marquée payée, on corrige
        $this->order->status = 'paid';
        $this->order->amount_paid = $this->order->total;
        $this->order->save();
        $this->alert('success', __('Le paiement a été détecté et la commande est maintenant payée'), ['toast' => true]);
        $this->showAddPaymentModal = false;
        return;
    }

    // =============================================
    // ÉTAPE 1: VÉRIFICATION ET APPLICATION DU TIMBRE FISCAL (1%)
    // =============================================
    $timbreFiscalApplied = false;
    $totalCashAmount = 0;
    if ($this->isCreditNote()) { 
        $initialMethod = $this->getInitialPaymentMethod();

        if ($initialMethod) {
            if ($this->paymentMethod !== $initialMethod) {
                $this->alert('error', "Le mode de paiement initial d'une facture avoir ne peut pas etre modifie.", [
                    'toast' => true,
                    'position' => 'top-end'
                ]);
                return;
            }

            foreach ($this->splits as $split) {
                if (($split['paymentMethod'] ?? $initialMethod) !== $initialMethod) {
                    $this->alert('error', "Le mode de paiement initial d'une facture avoir ne peut pas etre modifie.", [
                        'toast' => true,
                        'position' => 'top-end'
                    ]);
                    return;
                }
            }

            $this->paymentMethod = $initialMethod;

            foreach ($this->splits as $splitId => $split) {
                $this->splits[$splitId]['paymentMethod'] = $initialMethod;
            }

            $this->showSplitOptions = false;
            $this->splitType = null;
        }
    }

    // Déterminer le montant total payé en espèces
    if ($this->showSplitOptions && $this->splitType) {
        // Pour les paiements fractionnés
        foreach ($this->splits as $split) {
            $splitAmount = 0;
            
            if ($this->splitType === 'items') {
                $splitAmount = $split['total'] ?? 0;
            } else {
                $splitAmount = floatval($split['amount'] ?? 0);
            }
            
            if ($split['paymentMethod'] === 'cash' && $splitAmount > 0) {
                $totalCashAmount += $splitAmount;
            }
        }
    } else {
        // Paiement unique
        $totalCashAmount = ($this->paymentMethod === 'cash') ? ($this->paymentAmount - $this->returnAmount) : 0;
    }
    
    // Vérifier les conditions d'application du timbre fiscal (1% si > 100000 FCFA en espèces)
    if ($totalCashAmount > 100000 && !$this->isTimbreFiscalApplied && !$this->order->is_timbre_fiscal_applied) {
        
        // Calculer le montant du timbre fiscal (1% du montant espèces)
        $timbreAmount = $totalCashAmount * 0.01; // 1%
        
        // Récupérer ou créer l'article "Timbre Fiscal"
        if (!$this->timbreFiscalItemId) {
            $this->findOrCreateTimbreFiscalItem();
        }
        
        // Créer un nouvel item dans la commande
        $timbreOrderItem = \App\Models\OrderItem::create([
            'order_id' => $this->order->id,
            'menu_item_id' => $this->timbreFiscalItemId,
            'menu_item_variation_id' => null,
            'quantity' => 1,
            'price' => $timbreAmount,
            'amount' => $timbreAmount,
            'note' => 'Timbre fiscal 1% pour paiement espèces > 100 000 FCFA'
        ]);
        
        // Mettre à jour la commande (le total augmentera automatiquement)
        $this->order->timbre_fiscal_amount = $timbreAmount;
        $this->order->is_timbre_fiscal_applied = true;
        $this->order->total = $this->order->total + $timbreAmount;
        $this->order->saveQuietly();
        
        // Mettre à jour les propriétés locales
        $this->timbreFiscalAmount = $timbreAmount;
        $this->isTimbreFiscalApplied = true;
        
        // Journaliser
        Log::info('Timbre fiscal 1% appliqué - Article ajouté', [
            'order_id' => $this->order->id,
            'cash_amount' => $totalCashAmount,
            'timbre_amount' => $timbreAmount
        ]);
        
        $timbreFiscalApplied = true;
        
        // Mettre à jour les montants affichés
        $this->updateAmountDetails();
    }

    // =============================================
    // ÉTAPE 2: VALIDATION DES PAIEMENTS FRACTIONNÉS
    // =============================================
    if ($this->showSplitOptions && $this->splitType) {
        // Validate split by items
        if ($this->splitType === 'items') {
            $hasItemsInSplits = false;
            foreach ($this->splits as $split) {
                if (!empty($split['items']) && count($split['items']) > 0) {
                    $hasItemsInSplits = true;
                    break;
                }
            }

            if (!$hasItemsInSplits) {
                $this->alert('error', __('Please select items for payment'), ['toast' => true]);
                return;
            }
        }

        // Traiter les paiements fractionnés
        $this->processSplitPayment();

    } else {
        // =============================================
        // ÉTAPE 3: PAIEMENT UNIQUE
        // =============================================
        if ($this->paymentAmount >= 0) {
            Payment::create([
                'order_id' => $this->order->id,
                'payment_method' => $this->paymentMethod,
                'amount' => $this->paymentAmount - $this->returnAmount,
                'balance' => $this->returnAmount
            ]);
        }
    }

    // =============================================
    // ÉTAPE 4: RAFRAÎCHIR LES DONNÉES DE LA COMMANDE
    // =============================================
    $this->order = $this->order->fresh([
        'items', 
        'items.menuItem', 
        'taxes', 
        'payments', 
        'splitOrders',
        'splitOrders.items'
    ]);

    // =============================================
    // ÉTAPE 5: CALCULER LE MONTANT TOTAL PAYÉ
    // =============================================
    if ($this->order->split_type === 'items') {
        $orderPaidAmount = $this->order->splitOrders()
            ->where('status', 'paid')
            ->sum('amount');
    } else {
        $orderPaidAmount = Payment::where('order_id', $this->order->id)
            ->where('payment_method', '!=', 'due')
            ->sum('amount');
    }

    // =============================================
    // ÉTAPE 6: METTRE À JOUR LE STATUT DE LA COMMANDE
    // =============================================
    $this->order->amount_paid = $orderPaidAmount;
    $this->order->status = $orderPaidAmount >= $this->order->total ? 'paid' : 'pending';
    $this->order->status = $orderPaidAmount >= $this->order->total ? 'paid' : 'payment_due';
    $this->order->save();

    // =============================================
    // ÉTAPE 7: GÉRER LES PAIEMENTS "DÛ" (SOLDE RESTANT)
    // =============================================
    // Supprimer les anciens paiements "due"
    Payment::where('order_id', $this->order->id)
        ->where('payment_method', 'due')
        ->delete();

    // Créer un nouveau paiement "due" s'il reste un solde
    if ($orderPaidAmount < $this->order->total) {
        Payment::create([
            'order_id' => $this->order->id,
            'payment_method' => 'due',
            'amount' => $this->order->total - $orderPaidAmount
        ]);
    }

    // =============================================
    // ÉTAPE 8: METTRE À JOUR LE STATUT DE LA TABLE
    // =============================================
    $table = Table::find($this->order->table_id);
    
    if ($table) {
        $table->update(['available_status' => 'available']);
        
        // Libérer la session de la table si elle existe
        if ($table->tableSession) {
            $table->tableSession->releaseLock();
        }
    }

    // =============================================
    // ÉTAPE 9: ENVOYER LA NOTIFICATION AU CLIENT
    // =============================================
    if ($this->order->customer_id && $orderPaidAmount >= $this->order->total) {
        try {
            SendOrderBillEvent::dispatch($this->order);
        } catch (\Exception $e) {
            Log::error('Error sending notification: ' . $e->getMessage());
        }
    }

    // =============================================
    // ÉTAPE 10: MESSAGE DE SUCCÈS AVEC INFO TIMBRE FISCAL
    // =============================================
    if ($timbreFiscalApplied) {
        $this->alert('success', 'Paiement effectué avec succès. Timbre fiscal de 1% appliqué.', [
            'toast' => true,
            'position' => 'top-end'
        ]);
    } else {
        $this->alert('success', __('messages.paymentDoneSuccessfully'), [
            'toast' => true,
            'position' => 'top-end'
        ]);
    }

    // =============================================
    // ÉTAPE 11: RAFRAÎCHIR LES COMPOSANTS UI
    // =============================================
    $this->dispatch('showOrderDetail', id: $this->order->id);
    $this->dispatch('refreshOrders');
    $this->dispatch('resetPos');
    $this->dispatch('refreshPayments');
    
    // Fermer le modal
    $this->showAddPaymentModal = false;
}

/**
 * Trouve ou crée l'article "Timbre Fiscal" dans le menu
 */
private function findOrCreateTimbreFiscalItem()
{
    // Chercher d'abord si l'article existe déjà
    $timbreItem = \App\Models\MenuItem::where('item_name', 'like', '%Timbre Fiscal%')->first();
    
    if ($timbreItem) {
        $this->timbreFiscalItemId = $timbreItem->id;
        return;
    }
    
    // Créer l'article s'il n'existe pas
    $defaultMenu = \App\Models\Menu::first();
    $defaultCategory = \App\Models\ItemCategory::first();
    
    $newItem = \App\Models\MenuItem::create([
        'item_name' => 'Timbre Fiscal (1%)',
        'price' => 0, // Prix dynamique
        'type' => 'other',
        'menu_id' => $defaultMenu ? $defaultMenu->id : 1,
        'item_category_id' => $defaultCategory ? $defaultCategory->id : 1,
        'is_available' => true,
        'tax_inclusive' => false,
        'description' => 'Timbre fiscal de 1% pour les paiements espèces supérieurs à 100 000 FCFA'
    ]);
    
    $this->timbreFiscalItemId = $newItem->id;
    
    Log::info('Article Timbre Fiscal créé automatiquement avec ID: ' . $newItem->id);
}

    /**
     * Crée automatiquement l'article "Timbre Fiscal" s'il n'existe pas
     */
    private function createTimbreFiscalItem($timbreAmount)
    {
        // Créer l'article dans le menu
        $timbreItem = \App\Models\MenuItem::create([
            'item_name' => 'Timbre Fiscal (1%)',
            'price' => 0, // Le prix sera défini dynamiquement
            'type' => 'other',
            'menu_id' => 1, // À ajuster selon votre structure
            'item_category_id' => 1, // À ajuster
            'is_available' => true,
            'tax_inclusive' => false,
            'description' => 'Timbre fiscal de 1% pour les paiements espèces supérieurs à 100 000 FCFA'
        ]);
        
        $this->timbreFiscalItemId = $timbreItem->id;
        
        // Créer l'item dans la commande
        $timbreOrderItem = \App\Models\OrderItem::create([
            'order_id' => $this->order->id,
            'menu_item_id' => $this->timbreFiscalItemId,
            'quantity' => 1,
            'price' => $timbreAmount,
            'amount' => $timbreAmount,
            'note' => 'Timbre fiscal 1% pour paiement espèces > 100 000 FCFA'
        ]);
        
        $this->order->timbre_fiscal_amount = $timbreAmount;
        $this->order->is_timbre_fiscal_applied = true;
        $this->order->total += $timbreAmount;
        $this->order->save();
        
        $this->timbreFiscalAmount = $timbreAmount;
        $this->isTimbreFiscalApplied = true;
        
        Log::info('Article Timbre Fiscal créé automatiquement');
    }

    public function getTimbreFiscalDetailsProperty()
    {
        if ($this->isTimbreFiscalApplied && $this->timbreFiscalAmount > 0) {
            return [
                'amount' => $this->timbreFiscalAmount,
                'rate' => $this->timbreFiscalRate,
                'formatted' => currency_format($this->timbreFiscalAmount, restaurant()->currency_id)
            ];
        }
        return null;
    }

    public function render()
    {
        return view('livewire.order.add-payment');
    }

    public function updateSplitPaymentMethod($splitId, $method)
    {
        if ($this->isCreditNote()) {
            $initialMethod = $this->getInitialPaymentMethod();

            if ($initialMethod && isset($this->splits[$splitId])) {
                $this->splits[$splitId]['paymentMethod'] = $initialMethod;
                $this->splits = $this->splits;
            }

            return;
        }

        if (isset($this->splits[$splitId])) {
            $this->splits[$splitId]['paymentMethod'] = $method;
            $this->splits = $this->splits;
        }
    }


    public function updateBalanceAmount()
    {
        if ($this->splitType === 'custom') {
            $totalSplitAmount = collect($this->customSplits)->sum(fn($split) => floatval($this->splits[$split]['amount'] ?? 0));
            $this->balanceAmount = max(0, $this->dueAmount - $totalSplitAmount);
            $this->returnAmount = max(0, $totalSplitAmount - $this->dueAmount);
        } elseif ($this->splitType === 'equal') {
            $totalSplitAmount = collect($this->splits)->sum('amount');
            $this->balanceAmount = max(0, $this->dueAmount - $totalSplitAmount);
            $this->returnAmount = max(0, $totalSplitAmount - $this->dueAmount);
        } elseif ($this->splitType === 'items') {
            $totalSplitAmount = collect($this->splits)->sum('total');
            $this->balanceAmount = max(0, $this->dueAmount - $totalSplitAmount);
            $this->returnAmount = 0; // No return amount for item splits
        }
    }

    public function addItemToSplit($itemId, $splitId, $quantity = 1)
    {
        $itemIndex = collect($this->availableItems)->search(fn($i) => $i['id'] === $itemId);
        if ($itemIndex === false) return;

        $item = $this->availableItems[$itemIndex];
        $quantity = max(1, min($quantity, $item['remaining']));

        if ($quantity < 1 || $item['remaining'] < 1) return;

        if (!isset($this->splits[$splitId]['items'])) {
            $this->splits[$splitId]['items'] = [];
        }

        $existingItemIndex = collect($this->splits[$splitId]['items'])->search(fn($i) => $i['order_item_id'] === $item['order_item_id']);

        if ($existingItemIndex !== false) {
            // Update existing item in split
            $currentQtyInSplit = $this->splits[$splitId]['items'][$existingItemIndex]['quantity'];
            $maxAdditional = $item['remaining']; // remaining quantity available

            if ($maxAdditional >= $quantity) {
                $this->splits[$splitId]['items'][$existingItemIndex]['quantity'] += $quantity;
                $this->splits[$splitId]['items'][$existingItemIndex]['total'] = $this->splits[$splitId]['items'][$existingItemIndex]['quantity'] * $this->splits[$splitId]['items'][$existingItemIndex]['price'];
                $this->availableItems[$itemIndex]['remaining'] -= $quantity;
            }
        } else {
            // Add new item to split
            $splitItem = [
                'id' => $item['id'],
                'order_item_id' => $item['order_item_id'],
                'name' => $item['name'],
                'quantity' => $quantity,
                'price' => floatval($item['price']),
                'base_price' => floatval($item['base_price']),
                'extra_charges' => floatval($item['extra_charges']),
                'tax_amount' => floatval($item['tax_amount']),
                'discount' => floatval($item['discount']),
                'tip' => floatval($item['tip']),
                'total' => floatval($item['price']) * $quantity,
                'tax_mode' => $item['tax_mode'] ?? 'order'
            ];
            $this->splits[$splitId]['items'][] = $splitItem;
            $this->availableItems[$itemIndex]['remaining'] -= $quantity;
        }

        $this->calculateSplitTotals();
    }

    // Increment quantity of item in split (if available)
    public function incrementItemInSplit($splitId, $itemIndex)
    {
        if (!isset($this->splits[$splitId]['items'][$itemIndex])) return;
        $item = $this->splits[$splitId]['items'][$itemIndex];
        $availableIndex = collect($this->availableItems)->search(fn($i) => $i['id'] === $item['id']);
        if ($availableIndex !== false && $this->availableItems[$availableIndex]['remaining'] > 0) {
            $this->splits[$splitId]['items'][$itemIndex]['quantity']++;
            $this->splits[$splitId]['items'][$itemIndex]['total'] = $this->splits[$splitId]['items'][$itemIndex]['quantity'] * $this->splits[$splitId]['items'][$itemIndex]['price'];
            $this->availableItems[$availableIndex]['remaining']--;
            $this->calculateSplitTotals();
        }
    }

    // Decrement quantity of item in split (if > 1), or remove if quantity becomes 0
    public function decrementItemInSplit($splitId, $itemIndex)
    {
        if (!isset($this->splits[$splitId]['items'][$itemIndex])) return;
        $item = $this->splits[$splitId]['items'][$itemIndex];
        $availableIndex = collect($this->availableItems)->search(fn($i) => $i['id'] === $item['id']);
        if ($this->splits[$splitId]['items'][$itemIndex]['quantity'] > 1) {
            $this->splits[$splitId]['items'][$itemIndex]['quantity']--;
            $this->splits[$splitId]['items'][$itemIndex]['total'] = $this->splits[$splitId]['items'][$itemIndex]['quantity'] * $this->splits[$splitId]['items'][$itemIndex]['price'];
            if ($availableIndex !== false) {
                $this->availableItems[$availableIndex]['remaining']++;
            }
        } else {
            $this->removeItemFromSplit($splitId, $itemIndex);
            return;
        }
        $this->calculateSplitTotals();
    }

    // Update removeItemFromSplit to return all quantity to availableItems
    public function removeItemFromSplit($splitId, $itemIndex)
    {
        if (isset($this->splits[$splitId]['items'][$itemIndex])) {
            $item = $this->splits[$splitId]['items'][$itemIndex];
            $availableIndex = collect($this->availableItems)->search(fn($i) => $i['id'] === $item['id']);
            if ($availableIndex !== false) {
                $this->availableItems[$availableIndex]['remaining'] += $item['quantity'];
            }
            unset($this->splits[$splitId]['items'][$itemIndex]);
            $this->splits[$splitId]['items'] = array_values($this->splits[$splitId]['items']);
            $this->calculateSplitTotals();
        }
    }

    public function calculateSplitTotals()
    {
        foreach ($this->splits as &$split) {
            if (isset($split['items'])) {
                $split['total'] = collect($split['items'])->sum(function($item) {
                    return floatval($item['price']) * intval($item['quantity']);
                });

                // Calculate tax total separately for display purposes
                $split['tax_total'] = collect($split['items'])->sum(function($item) {
                    return floatval($item['tax_amount'] ?? 0) * intval($item['quantity']);
                });
            } else {
                // Ensure all splits have a numeric total, even if 0
                if (!isset($split['total'])) {
                    $split['total'] = 0;
                }
            }
        }
        
        // Update balance amount for items split
        if ($this->splitType === 'items') {
            $this->updateBalanceAmount();
        }
    }

    public function updatedSplits($value, $key)
    {
        // Update balance calculations whenever splits change
        $this->updateBalanceAmount();
    }

    public function addNewSplit()
    {
        $this->numberOfSplits++;

        // Initialize the new split with default values
        $this->splits[$this->numberOfSplits] = [
            'id' => $this->numberOfSplits,
            'paymentMethod' => $lockedPaymentMethod ?: 'cash',
            'amount' => $this->dueAmount / $this->numberOfSplits
        ];

        // Recalculate all split amounts evenly
        $splitAmount = $this->dueAmount / $this->numberOfSplits;
        foreach ($this->splits as $i => $split) {
            if ($i > 0) { // Skip index 0
                $this->splits[$i]['amount'] = $splitAmount;
            }
        }

        $this->updateBalanceAmount();
    }

    public function removeSplit($index)
    {
        if ($this->numberOfSplits > 2) {
            $this->numberOfSplits--;
            unset($this->splits[$index]);

            // Reindex the splits array
            $this->splits = array_values($this->splits);

            // Recalculate all split amounts evenly
            $splitAmount = $this->dueAmount / $this->numberOfSplits;
            foreach ($this->splits as $i => $split) {
                if ($i > 0) { // Skip index 0
                    $this->splits[$i]['amount'] = $splitAmount;
                }
            }

            $this->updateBalanceAmount();
        }
    }


    public function addNewCustomSplit()
    {
        $nextSplitNumber = max($this->customSplits) + 1;
        $this->customSplits[] = $nextSplitNumber;

        // Initialize the new split with default values
        $this->splits[$nextSplitNumber] = [
            'id' => $nextSplitNumber,
            'paymentMethod' => $lockedPaymentMethod ?: 'cash',
            'amount' => 0,
            'total' => 0  // Initialize total for consistency
        ];
        $this->updateBalanceAmount();
    }

    public function removeCustomSplit($splitNumber)
    {
        if (count($this->customSplits) > 2) {
            // Remove from customSplits array
            $this->customSplits = array_values(array_filter($this->customSplits, function($split) use ($splitNumber) {
                return $split !== $splitNumber;
            }));

            // Remove from splits array
            unset($this->splits[$splitNumber]);

            $this->updateBalanceAmount();
        }
    }

    public function addNewItemSplit()
    {
        $nextSplitId = max(array_keys($this->splits)) + 1;

        // Add new split with empty items array and its own payment method
        $this->splits[$nextSplitId] = [
            'id' => $nextSplitId,
            'items' => [],
            'paymentMethod' => $lockedPaymentMethod ?: 'cash',  // Each new split gets its own payment method
            'amount' => 0,
            'total' => 0
        ];

        $this->activeSplitId = $nextSplitId;
    }

    public function removeItemSplit($splitId)
    {
        if (count($this->splits) > 1) { // Keep at least one split
            // Return items to available pool
            if (isset($this->splits[$splitId]['items'])) {
                foreach ($this->splits[$splitId]['items'] as $item) {
                    $availableIndex = collect($this->availableItems)->search(fn($i) => $i['id'] === $item['id']);
                    if ($availableIndex !== false) {
                        $this->availableItems[$availableIndex]['remaining'] += $item['quantity'];
                    }
                }
            }

            // Remove the split
            unset($this->splits[$splitId]);

            // Reset active split to first split if removed split was active
            if ($this->activeSplitId === $splitId) {
                $this->activeSplitId = array_key_first($this->splits);
            }

            // Recalculate totals
            $this->calculateSplitTotals();
        }
    }

    public function addTipModal()
    {
        $this->tipAmount = $this->order->tip_amount ?? 0;
        $this->tipNote = $this->order->tip_note ?? '';
        $this->showTipModal = true;
    }

    public function addTip()
    {
        if (!$this->canAddTip) {
            $this->alert('error', __('messages.notHavePermission'), ['toast' => true]);
            return;
        }

        if (!$this->tipAmount || $this->tipAmount <= 0) {
            $this->tipAmount = 0;
        }

        $order = Order::find($this->order->id);

        $previousTip = floatval($order->tip_amount ?? 0);
        $newTip = floatval($this->tipAmount ?? 0);

        $order->total = floatval($order->total) - $previousTip + $newTip;
        $order->tip_amount = $newTip;
        $order->tip_note = $newTip > 0 ? $this->tipNote : null;
        $order->save();

        $this->order = $order;
        $this->showTipModal = false;

        $message = $newTip > 0 ? __('messages.tipAddedSuccessfully') : __('messages.tipRemovedSuccessfully');
        $this->alert('success', $message, ['toast' => true]);
        $this->updatedPaymentAmount();
        $this->updateAmountDetails();
    }

    public function setTip($mode, $value)
    {
        if ($mode === 'percentage') {
            $this->tipPercentage = $value;
            $this->tipAmount = ($value / 100) * $this->order->total;
        } else {
            $this->tipAmount = floatval($value);
        }
        $this->tipAmount = number_format($this->tipAmount, 2);
    }

    public function toggleTipMode()
    {
        $this->tipMode = $this->tipMode === 'percentage' ? 'amount' : 'percentage';
    }

    // Method to refresh available items after split payments
    public function refreshItemsAfterPayment()
    {
        if ($this->order && $this->order->split_type === 'items') {
            $this->refreshAvailableItems();
            $this->updateAmountDetails();
            $this->initializeSplits();
        }
    }

    public function updatedSplitType()
    {
        if ($this->isCreditNote()) {
            $this->splitType = null;
            return;
        }

        if ($this->splitType) {
            $this->initializeSplits();
        }
    }

    public function showSplitOptions($show = true)
    {
        if ($this->isCreditNote()) {
            $this->showSplitOptions = false;
            $this->splitType = null;
            return;
        }

        $this->showSplitOptions = $show;

        if ($show) {
            $this->splitType = null;
        } else {
            $this->splitType = null;
            $this->splits = [];
        }
    }


    // Handle when split amounts change via wire:model
    public function updatedSplitsAmount()
    {
        $this->updateBalanceAmount();
    }

    /**
     * Vérifie et applique la surtaxe de 1% pour les paiements en espèces > 100000 FCFA
     */
    private function applyCashSurtaxIfNeeded($paymentMethod, $paymentAmount)
    {
        // Ne pas appliquer si déjà appliquée
        if ($this->isCashSurtaxApplied) {
            return false;
        }

        // Vérifier les conditions : paiement en espèces ET montant > 100000
        if ($paymentMethod === 'cash' && $paymentAmount > 100000) {
            
            // Calculer la surtaxe (1% du montant TTC)
            $surtaxAmount = $paymentAmount * ($this->cashSurtaxRate / 100);
            
            // Mettre à jour la commande
            $this->order->cash_surtax_amount = $surtaxAmount;
            $this->order->is_cash_surtax_applied = true;
            $this->order->total += $surtaxAmount; // Ajouter au total
            
            // Mettre à jour les propriétés locales
            $this->cashSurtaxAmount = $surtaxAmount;
            $this->isCashSurtaxApplied = true;
            
            // Trouver ou créer la taxe système
            $tax = Tax::firstOrCreate(
                ['tax_name' => 'Taxe espèces (1%)'],
                [
                    'restaurant_id' => null,
                    'tax_percent' => '1.00'
                ]
            );
            
            // Lier la taxe à la commande si ce n'est pas déjà fait
            if (!$this->order->taxes()->where('tax_id', $tax->id)->exists()) {
                $this->order->taxes()->attach($tax->id);
            }
            
            // Recalculer les montants de la commande
            $this->updateAmountDetails();
            
            return true;
        }
        
        return false;
    }

    public function getCashSurtaxDetailsProperty()
    {
        if ($this->isCashSurtaxApplied && $this->cashSurtaxAmount > 0) {
            return [
                'amount' => $this->cashSurtaxAmount,
                'rate' => $this->cashSurtaxRate,
                'formatted' => currency_format($this->cashSurtaxAmount, restaurant()->currency_id)
            ];
        }
        return null;
    }

}