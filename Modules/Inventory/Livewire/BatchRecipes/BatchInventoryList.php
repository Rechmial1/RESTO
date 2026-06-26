<?php

namespace Modules\Inventory\Livewire\BatchRecipes;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Modules\Inventory\Entities\BatchStock;
use Modules\Inventory\Entities\BatchRecipe;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BatchInventoryList extends Component
{
    use WithPagination, LivewireAlert;

    public $search = '';
    public $batchRecipeFilter = '';
    public $statusFilter = '';
    public $perPage = 10;
    public bool $showProduceBatchModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'batchRecipeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    #[On('showProduceBatchModal')]
    public function openProduceBatchModal(): void
    {
        $this->showProduceBatchModal = true;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedBatchRecipeFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function paginationView()
    {
        return 'vendor.livewire.tailwind';
    }

    public function clearFilters()
    {
        $this->reset(['search', 'batchRecipeFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function getBatchStocks()
    {
        $query = BatchStock::with(['batchRecipe.yieldUnit', 'batchProduction.producedBy'])
            ->where('branch_id', branch()->id)
            ->whereHas('batchRecipe', function ($q) {
                if ($this->search) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                }
            });

        if ($this->batchRecipeFilter) {
            $query->where('batch_recipe_id', $this->batchRecipeFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function getAvailableBatchRecipes()
    {
        return BatchRecipe::where('branch_id', branch()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('inventory::livewire.batch-recipes.batch-inventory-list', [
            'batchStocks' => $this->getBatchStocks(),
            'batchRecipes' => $this->getAvailableBatchRecipes(),
        ]);
    }
}

