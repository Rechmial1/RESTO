<?php

namespace Modules\Inventory\Livewire\BatchRecipes;

use Livewire\Component;
use Modules\Inventory\Entities\BatchRecipe;
use Modules\Inventory\Entities\BatchRecipeItem;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\Unit;
use App\Models\MenuItem;
use App\Models\MenuItemVariation;
use Illuminate\Support\Collection;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BatchRecipeForm extends Component
{
    use LivewireAlert;

    public $showModal = false;
    public $isEditing = false;
    public $batchRecipeId;
    public $ingredients = [];

    // Form properties
    public $name = '';
    public $description = '';
    public $yieldUnitId = '';
    public $defaultBatchSize = 1;
    public $defaultExpiryDays = null;
    
    public $availableInventoryItems;
    public $availableUnits;
    public $availableMenuItems;

    // Linked menu items (batch -> menu items / variations)
    // Each row:
    // [
    //   'menu_item_id' => int,
    //   'menu_item_variation_id' => int|null,
    //   'serving_size' => float
    // ]
    public $linkedMenuItems = [];

    protected $listeners = [
        'showBatchRecipeForm',
        'editBatchRecipe',
        'batch-menu-item-selected' => 'handleBatchMenuItemSelected',
    ];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'yieldUnitId' => 'required|exists:units,id',
            'defaultBatchSize' => 'required|numeric|min:0.01',
            'defaultExpiryDays' => 'nullable|integer|min:1',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.unit_id' => 'required|exists:units,id',
        ];
    }

    public function mount()
    {
        $this->loadData();
        $this->ingredients = [$this->getEmptyIngredient()];
    }

    private function loadData()
    {
        $this->availableInventoryItems = InventoryItem::with('unit')
            ->where('branch_id', branch()->id)
            ->get(['id', 'name', 'unit_id']);
        
        $this->availableUnits = Unit::where('branch_id', branch()->id)->get(['id', 'name', 'symbol']);

        // Load menu items (with variations) for linking to this batch recipe
        $this->availableMenuItems = MenuItem::with(['variations:id,menu_item_id,variation'])
            ->where('branch_id', branch()->id)
            ->where('is_available', 1)
            ->orderBy('item_name')
            ->get(['id', 'item_name']);
    }

    public function showBatchRecipeForm()
    {
        $this->resetForm();
        $this->loadData();
        $this->showModal = true;
    }

    public function editBatchRecipe($batchRecipeId)
    {
        $this->isEditing = true;
        $this->batchRecipeId = $batchRecipeId;
        
        $batchRecipe = BatchRecipe::with(['recipeItems.inventoryItem', 'recipeItems.unit'])
            ->where('id', $batchRecipeId)
            ->where('branch_id', branch()->id)
            ->first();

        if (!$batchRecipe) {
            $this->alert('error', __('inventory::modules.batchRecipe.batchRecipeNotFound'));
            return;
        }

        $this->name = $batchRecipe->name;
        $this->description = $batchRecipe->description;
        $this->yieldUnitId = $batchRecipe->yield_unit_id;
        $this->defaultBatchSize = $batchRecipe->default_batch_size;
        $this->defaultExpiryDays = $batchRecipe->default_expiry_days;

        $this->ingredients = $batchRecipe->recipeItems->map(function ($item) {
            return [
                'inventory_item_id' => $item->inventory_item_id,
                'quantity' => $item->quantity,
                'unit_id' => $item->unit_id,
            ];
        })->toArray();

        // Load menu items currently linked to this batch recipe (base items)
        $this->linkedMenuItems = MenuItem::where('batch_recipe_id', $batchRecipe->id)
            ->where('branch_id', branch()->id)
            ->get(['id', 'item_name', 'batch_serving_size'])
            ->map(function ($item) {
                return [
                    'menu_item_id' => $item->id,
                    'menu_item_variation_id' => null,
                    'serving_size' => $item->batch_serving_size,
                ];
            })->toArray();

        // Load variations currently linked to this batch recipe
        $variationLinks = MenuItemVariation::where('batch_recipe_id', $batchRecipe->id)
            ->with('menuItem:id,branch_id')
            ->get(['id', 'menu_item_id', 'batch_serving_size']);

        foreach ($variationLinks as $variation) {
            // Only include variations whose menu item belongs to this branch
            if (!$variation->menuItem || $variation->menuItem->branch_id !== branch()->id) {
                continue;
            }

            $this->linkedMenuItems[] = [
                'menu_item_id' => $variation->menu_item_id,
                'menu_item_variation_id' => $variation->id,
                'serving_size' => $variation->batch_serving_size,
            ];
        }

        $this->loadData();
        $this->showModal = true;
    }

    private function resetForm()
    {
        $this->isEditing = false;
        $this->batchRecipeId = null;
        $this->name = '';
        $this->description = '';
        $this->yieldUnitId = '';
        $this->defaultBatchSize = 1;
        $this->defaultExpiryDays = null;
        $this->ingredients = [$this->getEmptyIngredient()];
        $this->linkedMenuItems = [];

        // Start with one empty linked menu item row for better UX
        $this->addLinkedMenuItem();
    }

    public function addIngredient()
    {
        $this->ingredients[] = $this->getEmptyIngredient();
    }

    public function removeIngredient($index)
    {
        unset($this->ingredients[$index]);
        $this->ingredients = array_values($this->ingredients);
    }

    public function save()
    {
        $this->validate();

        if ($this->isEditing) {
            $batchRecipe = BatchRecipe::find($this->batchRecipeId);
            $batchRecipe->update([
                'name' => $this->name,
                'description' => $this->description,
                'yield_unit_id' => $this->yieldUnitId,
                'default_batch_size' => $this->defaultBatchSize,
                'default_expiry_days' => $this->defaultExpiryDays,
            ]);

            // Delete existing recipe items
            $batchRecipe->recipeItems()->delete();
        } else {
            $batchRecipe = BatchRecipe::create([
                'branch_id' => branch()->id,
                'name' => $this->name,
                'description' => $this->description,
                'yield_unit_id' => $this->yieldUnitId,
                'default_batch_size' => $this->defaultBatchSize,
                'default_expiry_days' => $this->defaultExpiryDays,
            ]);
        }

        // Create recipe items
        foreach ($this->ingredients as $ingredient) {
            BatchRecipeItem::create([
                'batch_recipe_id' => $batchRecipe->id,
                'inventory_item_id' => $ingredient['inventory_item_id'],
                'quantity' => $ingredient['quantity'],
                'unit_id' => $ingredient['unit_id'],
            ]);
        }

        // --- Link menu items / variations to this batch recipe ---
        // First, clear existing links for this batch recipe
        MenuItem::where('batch_recipe_id', $batchRecipe->id)
            ->update([
                'batch_recipe_id' => null,
                'batch_serving_size' => null,
            ]);

        MenuItemVariation::where('batch_recipe_id', $batchRecipe->id)
            ->update([
                'batch_recipe_id' => null,
                'batch_serving_size' => null,
            ]);

        // Apply new links from the form
        foreach ($this->linkedMenuItems as $row) {
            if (empty($row['menu_item_id']) || empty($row['serving_size'])) {
                continue;
            }

            $servingSize = (float) $row['serving_size'];
            if ($servingSize <= 0) {
                continue;
            }

            // If a specific variation is selected, link the variation
            if (!empty($row['menu_item_variation_id'])) {
                MenuItemVariation::where('id', $row['menu_item_variation_id'])
                    ->whereHas('menuItem', function ($q) {
                        $q->where('branch_id', branch()->id);
                    })
                    ->update([
                        'batch_recipe_id' => $batchRecipe->id,
                        'batch_serving_size' => $servingSize,
                    ]);
            } else {
                // Otherwise, link the base menu item
                MenuItem::where('id', $row['menu_item_id'])
                    ->where('branch_id', branch()->id)
                    ->update([
                        'batch_recipe_id' => $batchRecipe->id,
                        'batch_serving_size' => $servingSize,
                    ]);
            }
        }

        $this->dispatch('batchRecipeUpdated');
        $this->dispatch('closeAddBatchRecipeModal');
        $this->alert('success', __('inventory::modules.batchRecipe.batchRecipeSaved'));
        $this->showModal = false;
        $this->resetForm();
    }

    private function getEmptyIngredient()
    {
        return [
            'inventory_item_id' => '',
            'quantity' => '',
            'unit_id' => '',
        ];
    }

    public function updatedIngredients($value, $key)
    {
        // Auto-set unit_id when inventory_item_id is selected
        if (str_contains($key, 'inventory_item_id')) {
            $index = explode('.', $key)[0];
            $inventoryItemId = $value;

            $inventoryItem = $this->availableInventoryItems->find($inventoryItemId);

            if ($inventoryItem) {
                $this->ingredients[$index]['unit_id'] = $inventoryItem->unit_id;
            }
        }
    }

    /**
     * Add an empty linked menu item row.
     */
    public function addLinkedMenuItem(): void
    {
        $this->linkedMenuItems[] = [
            'menu_item_id' => null,
            'menu_item_variation_id' => null,
            'serving_size' => '',
        ];
    }

    /**
     * Remove a linked menu item row by index.
     */
    public function removeLinkedMenuItem(int $index): void
    {
        unset($this->linkedMenuItems[$index]);
        $this->linkedMenuItems = array_values($this->linkedMenuItems);
    }


    /**
     * Handle selection from searchable dropdown for linked menu items.
     *
     * @param int $itemId
     * @param string $field e.g. 'linkedMenuItems.0.menu_item_id'
     */
    public function handleBatchMenuItemSelected($itemId, $field): void
    {
        if (strpos($field, 'linkedMenuItems.') !== 0) {
            return;
        }

        $parts = explode('.', $field);
        // Expecting ['linkedMenuItems', '{index}', 'menu_item_id']
        if (count($parts) < 3) {
            return;
        }

        $index = (int) $parts[1];

        if (!isset($this->linkedMenuItems[$index])) {
            $this->linkedMenuItems[$index] = [
                'menu_item_id' => null,
                'menu_item_variation_id' => null,
                'serving_size' => '',
            ];
        }

        // When changing the menu item, also reset the variation selection
        $this->linkedMenuItems[$index]['menu_item_id'] = $itemId;
        $this->linkedMenuItems[$index]['menu_item_variation_id'] = null;
    }

    public function render()
    {
        return view('inventory::livewire.batch-recipes.batch-recipe-form', [
            'inventoryItemsWithUnits' => $this->availableInventoryItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'unit_symbol' => $item->unit->symbol
                ];
            })
        ]);
    }
}

