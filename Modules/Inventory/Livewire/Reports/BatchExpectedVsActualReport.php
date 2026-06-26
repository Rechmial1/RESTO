<?php

namespace Modules\Inventory\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\BatchRecipe;
use Modules\Inventory\Entities\BatchProduction;
use Modules\Inventory\Entities\BatchConsumption;

class BatchExpectedVsActualReport extends Component
{
    use WithPagination;

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

    public function getRows()
    {
        $recipes = BatchRecipe::with('yieldUnit')
            ->where('branch_id', branch()->id)
            ->when($this->batchRecipeFilter, function ($q) {
                $q->where('id', $this->batchRecipeFilter);
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        $recipes->getCollection()->transform(function ($recipe) {
            $productions = BatchProduction::where('batch_recipe_id', $recipe->id)
                ->where('branch_id', branch()->id)
                ->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59',
                ])
                ->get();

            $consumptions = BatchConsumption::whereHas('batchStock', function ($q) use ($recipe) {
                    $q->where('batch_recipe_id', $recipe->id)
                      ->where('branch_id', branch()->id);
                })
                ->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59',
                ])
                ->get();

            $producedQty = $productions->sum('quantity');
            $consumedQty = $consumptions->sum('quantity');

            $recipe->expected_quantity = $producedQty;
            $recipe->actual_quantity = $consumedQty;
            $recipe->variance = $producedQty - $consumedQty;

            return $recipe;
        });

    return $recipes;
    }

    public function render()
    {
        return view('inventory::livewire.reports.batch-expected-vs-actual-report', [
            'rows' => $this->getRows(),
        ]);
    }
}








