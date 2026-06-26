<?php

namespace Modules\Inventory\Livewire\BatchRecipes;

use Livewire\Component;
use Modules\Inventory\Entities\BatchRecipe;
use Modules\Inventory\Entities\BatchProduction;
use Modules\Inventory\Entities\BatchStock;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Entities\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class ProduceBatch extends Component
{
    use LivewireAlert;

    public $showModal = false;
    public $batchRecipeId = null;
    public $quantity = 1;
    public $notes = '';
    
    public $selectedBatchRecipe = null;
    public $availableBatchRecipes = [];
    public $requiredIngredients = [];
    public $insufficientStock = [];

    protected $listeners = ['showProduceBatchModal'];

    protected function rules()
    {
        return [
            'batchRecipeId' => 'required|exists:batch_recipes,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->loadBatchRecipes();
    }

    private function loadBatchRecipes()
    {
        $this->availableBatchRecipes = BatchRecipe::with(['yieldUnit', 'recipeItems.inventoryItem.unit'])
            ->where('branch_id', branch()->id)
            ->get();
    }

    public function showProduceBatchModal()
    {
        $this->resetForm();
        $this->loadBatchRecipes();
        $this->showModal = true;
    }

    private function resetForm()
    {
        $this->batchRecipeId = null;
        $this->quantity = 1;
        $this->notes = '';
        $this->selectedBatchRecipe = null;
        $this->requiredIngredients = [];
        $this->insufficientStock = [];
    }

    public function updatedBatchRecipeId($value)
    {
        if ($value) {
            $this->selectedBatchRecipe = BatchRecipe::with(['recipeItems.inventoryItem.unit', 'yieldUnit'])
                ->where('id', $value)
                ->where('branch_id', branch()->id)
                ->first();
            
            if ($this->selectedBatchRecipe) {
                $this->calculateRequiredIngredients();
            }
        } else {
            $this->selectedBatchRecipe = null;
            $this->requiredIngredients = [];
            $this->insufficientStock = [];
        }
    }

    public function updatedQuantity($value)
    {
        if ($this->selectedBatchRecipe) {
            $this->calculateRequiredIngredients();
        }
    }

    private function calculateRequiredIngredients()
    {
        if (!$this->selectedBatchRecipe || !$this->quantity) {
            return;
        }

        $this->requiredIngredients = [];
        $this->insufficientStock = [];

        foreach ($this->selectedBatchRecipe->recipeItems as $recipeItem) {
            $inventoryItem = $recipeItem->inventoryItem;
            $requiredQuantity = $recipeItem->quantity * $this->quantity;
            
            // Get current stock
            $stock = InventoryStock::where('inventory_item_id', $inventoryItem->id)
                ->where('branch_id', branch()->id)
                ->first();
            
            $availableStock = $stock ? $stock->quantity : 0;
            $isSufficient = $availableStock >= $requiredQuantity;

            $this->requiredIngredients[] = [
                'inventory_item' => $inventoryItem,
                'required_quantity' => $requiredQuantity,
                'available_stock' => $availableStock,
                'unit' => $recipeItem->unit,
                'is_sufficient' => $isSufficient,
            ];

            if (!$isSufficient) {
                $this->insufficientStock[] = [
                    'name' => $inventoryItem->name,
                    'required' => $requiredQuantity,
                    'available' => $availableStock,
                    'unit' => $recipeItem->unit->symbol,
                ];
            }
        }
    }

    public function produce()
    {
        $this->validate();

        if (count($this->insufficientStock) > 0) {
            $this->alert('error', __('inventory::modules.batchRecipe.insufficientStock'));
            return;
        }

        try {
            DB::transaction(function () {
                $batchRecipe = BatchRecipe::with(['recipeItems.inventoryItem'])->find($this->batchRecipeId);
                $totalCost = 0;
                $movements = [];

                // Deduct raw ingredients and calculate cost
                foreach ($batchRecipe->recipeItems as $recipeItem) {
                    $inventoryItem   = $recipeItem->inventoryItem;
                    $quantityNeeded  = $recipeItem->quantity * $this->quantity;

                    $stock = InventoryStock::where('inventory_item_id', $inventoryItem->id)
                        ->where('branch_id', branch()->id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock && $stock->quantity >= $quantityNeeded) {
                        // Create inventory movement for stock out (will be linked to batch production)
                        $movements[] = InventoryMovement::create([
                            'branch_id'         => branch()->id,
                            'inventory_item_id' => $inventoryItem->id,
                            'quantity'          => $quantityNeeded,
                            'transaction_type'  => InventoryMovement::TRANSACTION_TYPE_ORDER_USED,
                            'added_by'          => auth()->id(),
                        ]);

                        // Update stock
                        $stock->quantity -= $quantityNeeded;
                        $stock->save();

                        // Calculate cost
                        $totalCost += $inventoryItem->unit_purchase_price * $quantityNeeded;
                    }
                }

                // Calculate cost per unit
                $costPerUnit = $this->quantity > 0 ? $totalCost / $this->quantity : 0;

                // Create batch production record
                $batchProduction = BatchProduction::create([
                    'branch_id'       => branch()->id,
                    'batch_recipe_id' => $this->batchRecipeId,
                    'quantity'        => $this->quantity,
                    'total_cost'      => $totalCost,
                    'produced_by'     => auth()->id(),
                    'notes'           => $this->notes,
                ]);

                // Link all created movements to this batch production
                foreach ($movements as $movement) {
                    $movement->update(['batch_production_id' => $batchProduction->id]);
                }

                // Calculate expiry date if set
                $expiryDate = null;
                if ($batchRecipe->default_expiry_days) {
                    $expiryDate = now()->addDays($batchRecipe->default_expiry_days)->toDateString();
                }

                // Create batch stock entry
                BatchStock::create([
                    'branch_id' => branch()->id,
                    'batch_recipe_id' => $this->batchRecipeId,
                    'batch_production_id' => $batchProduction->id,
                    'quantity' => $this->quantity,
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => $totalCost,
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                ]);
            });

            $this->alert('success', __('inventory::modules.batchRecipe.batchProducedSuccessfully'));
            $this->dispatch('batchProduced');
            $this->showModal = false;
            $this->resetForm();
        } catch (\Exception $e) {
            \Log::error('Error producing batch: ' . $e->getMessage());
            $this->alert('error', __('inventory::modules.batchRecipe.errorProducingBatch'));
        }
    }

    public function render()
    {
        return view('inventory::livewire.batch-recipes.produce-batch');
    }
}

