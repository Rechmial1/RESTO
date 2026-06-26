<?php

namespace Modules\Inventory\Livewire\BatchRecipes;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\BatchRecipe;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BatchRecipesList extends Component
{
    use WithPagination, LivewireAlert;

    public $search = '';
    public $showAddBatchRecipe = false;
    public $isEditing = false;
    public $confirmDeleteBatchRecipe = false;
    public $batchRecipeToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected $listeners = [
        'batchRecipeUpdated' => '$refresh',
        'closeAddBatchRecipeModal' => 'closeModal'
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.tailwind';
    }

    public function addBatchRecipe()
    {
        $this->isEditing = false;
        $this->showAddBatchRecipe = true;
        $this->dispatch('showBatchRecipeForm');
    }

    public function editBatchRecipe($batchRecipeId)
    {
        $this->isEditing = true;
        $this->showAddBatchRecipe = true;
        $this->dispatch('editBatchRecipe', $batchRecipeId);
    }

    public function closeModal()
    {
        $this->showAddBatchRecipe = false;
        $this->isEditing = false;
    }

    public function showDeleteBatchRecipe($batchRecipeId)
    {
        $this->batchRecipeToDelete = $batchRecipeId;
        $this->confirmDeleteBatchRecipe = true;
    }

    public function deleteBatchRecipe()
    {
        if ($this->batchRecipeToDelete) {
            $batchRecipe = BatchRecipe::find($this->batchRecipeToDelete);
            
            if ($batchRecipe) {
                // Check if batch recipe is in use
                if ($batchRecipe->stocks()->where('status', 'active')->count() > 0) {
                    $this->alert('error', __('inventory::modules.batchRecipe.cannotDeleteActiveBatch'));
                    return;
                }
                
                $batchRecipe->delete();
                $this->alert('success', __('inventory::modules.batchRecipe.batchRecipeDeleted'));
            }
        }
        
        $this->confirmDeleteBatchRecipe = false;
        $this->batchRecipeToDelete = null;
    }

    public function render()
    {
        $batchRecipes = BatchRecipe::with(['yieldUnit', 'recipeItems.inventoryItem'])
            ->where('branch_id', branch()->id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('inventory::livewire.batch-recipes.batch-recipes-list', [
            'batchRecipes' => $batchRecipes,
        ]);
    }
}

