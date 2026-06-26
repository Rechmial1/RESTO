<?php

namespace Modules\Inventory\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\BatchProduction;
use Modules\Inventory\Entities\BatchRecipe;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class BatchProductionReport extends Component
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

    public function getProductions()
    {
        $query = BatchProduction::with(['batchRecipe.yieldUnit', 'producedBy'])
            ->where('branch_id', branch()->id)
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->batchRecipeFilter) {
            $query->where('batch_recipe_id', $this->batchRecipeFilter);
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
        $query = BatchProduction::where('branch_id', branch()->id)
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->batchRecipeFilter) {
            $query->where('batch_recipe_id', $this->batchRecipeFilter);
        }

        return [
            'total_productions' => $query->count(),
            'total_quantity' => $query->sum('quantity'),
            'total_cost' => $query->sum('total_cost'),
        ];
    }

    public function render()
    {
        return view('inventory::livewire.reports.batch-production-report', [
            'productions' => $this->getProductions(),
            'batchRecipes' => $this->getBatchRecipes(),
            'summary' => $this->getSummary(),
        ]);
    }
}

