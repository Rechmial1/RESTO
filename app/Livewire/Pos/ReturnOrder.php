<?php

namespace App\Livewire\Pos;

use App\Models\Kot;
use App\Models\Tax;
use App\Models\Menu;
use App\Models\User;
use App\Models\Order;
use App\Models\Table;
use App\Models\KotItem;
use App\Models\Printer;
use Livewire\Component;
use App\Models\Customer;
use App\Models\KotPlace;
use App\Models\MenuItem;
use App\Models\OrderTax;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Restaurant;
use App\Models\OrderCharge;
use App\Scopes\BranchScope;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use App\Models\ItemCategory;
use App\Models\ModifierOption;
use App\Traits\PrinterSetting;
use Illuminate\Support\Carbon;
use App\Events\NewOrderCreated;
use App\Models\KotCancelReason;
use Illuminate\Validation\Rule;
use App\Models\DeliveryPlatform;
use App\Models\RestaurantCharge;
use App\Models\DeliveryExecutive;
use App\Models\MenuItemVariation;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ReturnOrder extends Component
{
    use LivewireAlert;

    public $returnOrder;
    public $originalOrder;
    public $originalOrderId;
    public $showReturnModal = false;
    public $itemsToReturn = [];
    public $selectedItems = [];
    public $totalReturn = 0;
    public $returnReason = '';

    #[On('showReturnOrderModal')]
    public function showReturnModal($orderId, $originalOrderId)
    {
        $this->returnOrder = Order::find($orderId);
        $this->originalOrder = Order::with(['items.menuItem', 'items.menuItemVariation'])
            ->find($originalOrderId);
        $this->originalOrderId = $originalOrderId;
        
        // Initialiser les articles à retourner
        foreach ($this->originalOrder->items as $item) {
            $this->itemsToReturn[] = [
                'id' => $item->id,
                'order_item_id' => $item->id,
                'name' => $item->menuItem->item_name . ($item->menuItemVariation ? ' (' . $item->menuItemVariation->variation . ')' : ''),
                'quantity' => $item->quantity,
                'max_quantity' => $item->quantity,
                'price' => $item->price,
                'selected' => false,
                'return_quantity' => 0
            ];
        }
        
        $this->showReturnModal = true;
    }

    public function updatedSelectedItems()
    {
        $this->calculateTotal();
    }

    public function updatedItemsToReturn()
    {
        $this->calculateTotal();
    }

    public function toggleItem($index)
    {
        $this->itemsToReturn[$index]['selected'] = !$this->itemsToReturn[$index]['selected'];
        if (!$this->itemsToReturn[$index]['selected']) {
            $this->itemsToReturn[$index]['return_quantity'] = 0;
        } else {
            $this->itemsToReturn[$index]['return_quantity'] = $this->itemsToReturn[$index]['max_quantity'];
        }
        $this->calculateTotal();
    }

    public function updateQuantity($index, $quantity)
    {
        $quantity = max(0, min($quantity, $this->itemsToReturn[$index]['max_quantity']));
        $this->itemsToReturn[$index]['return_quantity'] = $quantity;
        
        if ($quantity == 0) {
            $this->itemsToReturn[$index]['selected'] = false;
        } else {
            $this->itemsToReturn[$index]['selected'] = true;
        }
        
        $this->calculateTotal();
    }

    private function calculateTotal()
    {
        $this->totalReturn = 0;
        foreach ($this->itemsToReturn as $item) {
            if ($item['selected'] && $item['return_quantity'] > 0) {
                $this->totalReturn += $item['price'] * $item['return_quantity'];
            }
        }
    }

    public function validateReturn()
    {
        $hasItems = false;
        foreach ($this->itemsToReturn as $item) {
            if ($item['selected'] && $item['return_quantity'] > 0) {
                $hasItems = true;
                break;
            }
        }

        if (!$hasItems) {
            $this->alert('error', 'Veuillez sélectionner au moins un article à retourner.', [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return false;
        }

        if (empty($this->returnReason)) {
            $this->alert('error', 'Veuillez indiquer la raison du retour.', [
                'toast' => true,
                'position' => 'top-end'
            ]);
            return false;
        }

        return true;
    }

    public function confirmReturn()
    {
        if (!$this->validateReturn()) {
            return;
        }

        try {
            \DB::beginTransaction();

            // Mettre à jour la commande de retour
            $this->returnOrder->update([
                'sub_total' => $this->totalReturn,
                'total' => $this->totalReturn,
                'status' => 'billed',
                'note' => ($this->returnOrder->note ?? '') . ' | Raison retour: ' . $this->returnReason
            ]);

            // Ajouter les articles retournés
            foreach ($this->itemsToReturn as $item) {
                if ($item['selected'] && $item['return_quantity'] > 0) {
                    OrderItem::create([
                        'order_id' => $this->returnOrder->id,
                        'menu_item_id' => $this->originalOrder->items->find($item['id'])->menu_item_id,
                        'menu_item_variation_id' => $this->originalOrder->items->find($item['id'])->menu_item_variation_id,
                        'quantity' => $item['return_quantity'],
                        'price' => $item['price'],
                        'amount' => $item['price'] * $item['return_quantity'],
                        'note' => 'Retour sur facture #' . $this->originalOrder->formatted_order_number
                    ]);
                }
            }

            \DB::commit();

            $this->alert('success', 'Retour créé avec succès. La facture d\'avoir est prête.', [
                'toast' => true,
                'position' => 'top-end'
            ]);

            $this->showReturnModal = false;
            
            // Rediriger vers la facture d'avoir
            $this->dispatch('showOrderDetail', id: $this->returnOrder->id);

        } catch (\Exception $e) {
            \DB::rollBack();
            
            $this->alert('error', 'Erreur: ' . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.pos.return-order');
    }
}