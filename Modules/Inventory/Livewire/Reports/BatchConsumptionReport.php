<?php

namespace Modules\Inventory\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\BatchConsumption;
use Modules\Inventory\Entities\BatchRecipe;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BatchConsumptionReport extends Component
{
    use WithPagination, LivewireAlert;

    public $startDate;
    public $endDate;
    public $batchRecipeFilter = '';
    public $perPage = 20;

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'batchRecipeFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function paginationView()
    {
        return 'vendor.livewire.tailwind';
    }

    public function getConsumptions()
    {
        $query = BatchConsumption::with([
            'batchStock.batchRecipe.yieldUnit',
            'order',
            'orderItem.menuItem'
        ])
            ->where('branch_id', branch()->id)
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->batchRecipeFilter) {
            $query->whereHas('batchStock', function ($q) {
                $q->where('batch_recipe_id', $this->batchRecipeFilter);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function getBatchRecipes()
    {
        return BatchRecipe::where('branch_id', branch()->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSummary()
    {
        $query = BatchConsumption::where('branch_id', branch()->id)
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->batchRecipeFilter) {
            $query->whereHas('batchStock', function ($q) {
                $q->where('batch_recipe_id', $this->batchRecipeFilter);
            });
        }

        return [
            'total_consumptions' => $query->count(),
            'total_quantity' => $query->sum('quantity'),
            'total_cost' => $query->sum('cost'),
        ];
    }

    public function render()
    {
        return view('inventory::livewire.reports.batch-consumption-report', [
            'consumptions' => $this->getConsumptions(),
            'batchRecipes' => $this->getBatchRecipes(),
            'summary' => $this->getSummary(),
        ]);
    }
}

