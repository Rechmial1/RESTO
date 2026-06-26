<?php

namespace Modules\Inventory\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Entities\BatchStock;

class BatchWasteReport extends Component
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
        $query = BatchStock::with(['batchRecipe.yieldUnit'])
            ->where('branch_id', branch()->id)
            ->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59',
            ])
            ->where('status', 'expired');

        if ($this->batchRecipeFilter) {
            $query->where('batch_recipe_id', $this->batchRecipeFilter);
        }

        return $query->orderBy('expiry_date', 'desc')->paginate($this->perPage);
    }

    public function render()
    {
        $rows = $this->getRows();

        $summary = [
            'total_quantity' => $rows->sum('quantity'),
            'total_cost'     => $rows->sum('total_cost'),
        ];

        return view('inventory::livewire.reports.batch-waste-report', [
            'rows'    => $rows,
            'summary' => $summary,
        ]);
    }
}












